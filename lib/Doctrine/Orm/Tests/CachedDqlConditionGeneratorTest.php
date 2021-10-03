<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface;
use Rollerworks\Component\Search\Doctrine\Orm\CachedDqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\ConditionGenerator;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\SearchPrimaryCondition;
use Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice;

/**
 * @group non-functional
 *
 * @internal
 */
final class CachedDqlConditionGeneratorTest extends OrmTestCase
{
    /**
     * @var QueryBuilder
     */
    private $query;

    /**
     * @var CachedDqlConditionGenerator
     */
    protected $conditionGenerator;

    /**
     * @var CacheInterface|MockObject
     */
    protected $cacheDriver;

    public const CACHE_KEY = 'a862f6cc2c2273dd537ef9404a5cf3f6bc8c9fa8183492d13d1559339c8235bf';

    /** @test */
    public function get_where_clause_no_cache(): void
    {
        $this->cacheDriver
            ->expects(self::never())
            ->method('has')
        ;

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY)
            ->willReturn(null)
        ;

        $this->cacheDriver
            ->expects(self::once())
            ->method('set')
            ->with(
                self::CACHE_KEY,
                [
                    '(((C.id = :search_0 OR C.id = :search_1)))',
                    [
                        ':search_0' => [2, 'integer'],
                        ':search_1' => [5, 'integer'],
                    ],
                ],
                60
            )
        ;

        $this->assertQueryBuilderEquals(
            ' WHERE (((C.id = :search_0 OR C.id = :search_1)))',
            [':search_0' => [2, Type::getType('integer')], ':search_1' => [5, Type::getType('integer')]]
        );
    }

    private function assertQueryBuilderEquals(string $where, array $parameters, ?ConditionGenerator $generator = null): void
    {
        if ($generator === null) {
            $generator = $this->conditionGenerator;
        }

        $queryBuilder = $generator->getQueryBuilder();
        $baseDql = $queryBuilder->getDQL();

        $generator->apply();

        $finalDql = $queryBuilder->getDQL();

        self::assertEquals($baseDql . $where, $finalDql);
        self::assertQueryParametersEquals($parameters, $queryBuilder);
    }

    /** @test */
    public function get_where_clause_with_cache(): void
    {
        $this->cacheDriver
            ->expects(self::never())
            ->method('has')
        ;

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY)
            ->willReturn(["me = 'foo'", [':search' => [1, 'integer']]])
        ;

        $this->cacheDriver
            ->expects(self::never())
            ->method('set')
        ;

        $this->assertQueryBuilderEquals(" WHERE me = 'foo'", [':search' => [1, Type::getType('integer')]]);
    }

    /** @test */
    public function with_sorting(): void
    {
        $this->cacheDriver
            ->expects(self::never())
            ->method('has')
        ;

        // Second-key is used for (non-empty) primary-condition
        // Note: ordering doesn't change the cache-key as ordering is applied independently.
        $this->cacheDriver
            ->expects(self::any())
            ->method('get')
            ->with(self::matchesRegularExpression('/^' . self::CACHE_KEY . '|2572233a315f25e5bc6603ae17db405863603c0a61b9d780bf9f6d62a37350ce/'))
            ->willReturn(["me = 'foo'", [':search' => [1, 'integer']]])
        ;

        $this->cacheDriver
            ->expects(self::never())
            ->method('set')
        ;

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->order('@id', 'DESC')
            ->getSearchCondition()
        ;

        $this->assertQueryBuilderEquals(
            " WHERE me = 'foo' ORDER BY I.id DESC",
            [':search' => [1, Type::getType('integer')]],
            $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition, $this->createQuery())
        );

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->primaryCondition()
                ->order('@id', 'DESC')
            ->end()
            ->getSearchCondition()
        ;

        $this->assertQueryBuilderEquals(
            " WHERE me = 'foo' ORDER BY I.id DESC",
            [':search' => [1, Type::getType('integer')]],
            $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition, $this->createQuery())
        );

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->order('@customer', 'DESC')
            ->primaryCondition()
                ->order('@id', 'DESC')
            ->end()
            ->getSearchCondition()
        ;

        $this->assertQueryBuilderEquals(
            " WHERE me = 'foo' ORDER BY I.id DESC, C.id DESC",
            [':search' => [1, Type::getType('integer')]],
            $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition, $this->createQuery())
        );
    }

    /** @test */
    public function does_not_store_empty_condition(): void
    {
        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();

        $this->cacheDriver = $this->createMock(CacheInterface::class);
        $this->cacheDriver
            ->expects(self::never())
            ->method('has')
        ;

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('0bd93612c80eb441a04867c5963104aa1b4dd33cf0afa555e958bf696c14e0b0')
            ->willReturn(null)
        ;

        $this->cacheDriver
            ->expects(self::never())
            ->method('set')
        ;

        $this->conditionGenerator = $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition);

        $this->assertQueryBuilderEquals('', []);
    }

    /** @test */
    public function cannot_apply_multiple_times(): void
    {
        $this->conditionGenerator->apply();

        $this->expectWarning();
        $this->expectWarningMessage('SearchCondition was already applied. Ignoring operation.');

        $this->conditionGenerator->apply();
    }

    /** @test */
    public function with_existing_caches_and_primary_cond(): void
    {
        $cacheDriverProphecy = $this->prophesize(CacheInterface::class);
        $cacheDriverProphecy->get('94006677e4dd617091945f6f4210a5cefb9e78d84fa3292c126f501523b17b10')->willReturn(["me = 'foo'", [':search_1' => ['duck', 'text']]])->shouldBeCalled();
        $cacheDriverProphecy->get('d86dcae4d3eca451c2364158a3b0acf40b07402df80bdb42f4ed1b64c8621c67')->willReturn(["you = 'me' AND me = 'foo'", [':search_2' => ['roll', 'text']]])->shouldBeCalled();
        $cacheDriver = $cacheDriverProphecy->reveal();

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $query1 = $this->createQuery();
        $cachedConditionGenerator = $this->createCachedConditionGenerator($cacheDriver, $searchCondition, $query1);

        $searchCondition2 = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $searchCondition2->setPrimaryCondition(new SearchPrimaryCondition(
            SearchConditionBuilder::create($this->getFieldSet())
                ->field('customer')
                    ->addSimpleValue(2)
                ->end()
            ->getSearchCondition()->getValuesGroup())
        );

        $query2 = $this->createQuery();
        $cachedConditionGenerator2 = $this->createCachedConditionGenerator($cacheDriver, $searchCondition2, $query2);

        $this->assertQueryBuilderEquals(" WHERE me = 'foo'", [':search_1' => ['duck', Type::getType('text')]], $cachedConditionGenerator);
        $this->assertQueryBuilderEquals(" WHERE you = 'me' AND me = 'foo'", [':search_2' => ['roll', Type::getType('text')]], $cachedConditionGenerator2);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDriver = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();
        $this->query = $this->createQuery();

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $this->cacheDriver = $this->createMock(CacheInterface::class);
        $this->conditionGenerator = $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition);
    }

    private function createQuery(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('I')
            ->from(ECommerceInvoice::class, 'I')
            ->join('I.customer', 'C')
        ;
    }

    private function createCachedConditionGenerator(CacheInterface $cacheDriver, SearchCondition $searchCondition, ?QueryBuilder $qb = null): CachedDqlConditionGenerator
    {
        $conditionGenerator = new CachedDqlConditionGenerator(($qb ?? $this->query), $searchCondition, $cacheDriver, 60);
        $conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $conditionGenerator->setField('id', 'id', null, null, 'smallint');
        $conditionGenerator->setField('@id', 'id');

        $conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
        $conditionGenerator->setField('customer', 'id', null, null, 'integer');
        $conditionGenerator->setField('@customer', 'id');
        $conditionGenerator->setField('customer_name#first_name', 'firstName');
        $conditionGenerator->setField('customer_name#last_name', 'lastName');
        $conditionGenerator->setField('customer_birthday', 'birthday');

        return $conditionGenerator;
    }
}
