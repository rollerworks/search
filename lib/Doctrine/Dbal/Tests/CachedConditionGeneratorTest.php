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

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Doctrine\Dbal\CachedConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\Test\QueryBuilderAssertion;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;

/**
 * @internal
 */
final class CachedConditionGeneratorTest extends DbalTestCase
{
    private QueryBuilder $query;
    private CachedConditionGenerator $conditionGenerator;

    /**
     * @var Cache&MockObject
     */
    protected $cacheDriver;

    public const CACHE_KEY = 'da80730b87c4750f8c619ac64b679586ac2f9d86c53508d1a53a7c0341b4e363';

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
                    '(((c.id = :search_0 OR c.id = :search_1)))',
                    [
                        ':search_0' => [2, 'integer'],
                        ':search_1' => [5, 'integer'],
                    ],
                ],
                60
            )
        ;

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $this->conditionGenerator,
            ' WHERE (((c.id = :search_0 OR c.id = :search_1)))',
            [':search_0' => [2, 'integer'], ':search_1' => [5, 'integer']]
        );
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

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $this->conditionGenerator,
            " WHERE me = 'foo'", [':search' => [1, 'integer']]
        );
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
            ->method('get')
            ->with(self::matchesRegularExpression('/' . self::CACHE_KEY . '|b36fdf0d3a9e9d9c9ae83797cac20d07129502ed1992675fdac841cafe3bc9bb/'))
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

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition, $this->createQuery()),
            " WHERE me = 'foo' ORDER BY i.id DESC",
            [':search' => [1, 'integer']],
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

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition, $this->createQuery()),
            " WHERE me = 'foo' ORDER BY i.id DESC",
            [':search' => [1, 'integer']],
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

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition, $this->createQuery()),
            " WHERE me = 'foo' ORDER BY i.id DESC, c.id DESC",
            [':search' => [1, 'integer']],
        );
    }

    /** @test */
    public function does_not_store_empty_condition(): void
    {
        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();

        $this->cacheDriver = $this->createMock(Cache::class);
        $this->cacheDriver
            ->expects(self::never())
            ->method('has')
        ;

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('b158149acd0ecc74e27a5d6f0387d987c01760264a03e5324202d6dc8ab49b69')
            ->willReturn(null)
        ;

        $this->cacheDriver
            ->expects(self::never())
            ->method('set')
        ;

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition),
            '',
            []
        );
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
        $cacheDriverProphecy = $this->prophesize(Cache::class);
        $cacheDriverProphecy->get('c26c585aab758c3797f9fd16f3831bcca97dde4db5cce381f43b027287495d69')->willReturn(["me = 'foo'", [':search_1' => ['duck', 'text']]])->shouldBeCalled();
        $cacheDriverProphecy->get('72356eef163c9e3f2602ba1d8d8d47954e1480f379672cbf760380c05434ae71')->willReturn(["you = 'me' AND me = 'foo'", [':search_2' => ['roll', 'text']]])->shouldBeCalled();
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
            ->primaryCondition()
                ->field('customer')
                    ->addSimpleValue(2)
                ->end()
            ->end()
        ->getSearchCondition()
        ;

        $query2 = $this->createQuery();
        $cachedConditionGenerator2 = $this->createCachedConditionGenerator($cacheDriver, $searchCondition2, $query2);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $cachedConditionGenerator,
            " WHERE me = 'foo'", [':search_1' => ['duck', 'text']],
        );
        QueryBuilderAssertion::assertQueryBuilderEquals(
            $cachedConditionGenerator2,
            " WHERE you = 'me' AND me = 'foo'", [':search_2' => ['roll', 'text']],
        );
    }

    /** @test */
    public function get_where_clause_invalid_cache(): void
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(static function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn([])
        ;

        $this->cacheDriver
            ->expects(self::once())
            ->method('set')
            ->with(
                self::callback(
                    static function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                [
                    '(((c.id = :search_0 OR c.id = :search_1)))',
                    [
                        ':search_0' => [2, 'integer'],
                        ':search_1' => [5, 'integer'],
                    ],
                ],
                60
            )
        ;

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $this->conditionGenerator,
            ' WHERE (((c.id = :search_0 OR c.id = :search_1)))',
            [':search_0' => [2, 'integer'], ':search_1' => [5, 'integer']]
        );
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

        $this->cacheDriver = $this->createMock(Cache::class);
        $this->conditionGenerator = $this->createCachedConditionGenerator($this->cacheDriver, $searchCondition);
    }

    private function createQuery(): QueryBuilder
    {
        return $this->getConnectionMock()->createQueryBuilder()
            ->select('I')
            ->from('invoice', 'I')
            ->join('I', 'customer', 'C', 'C.id = I.customer')
        ;
    }

    private function createCachedConditionGenerator(Cache $cacheDriver, SearchCondition $searchCondition, ?QueryBuilder $qb = null): CachedConditionGenerator
    {
        $conditionGenerator = new CachedConditionGenerator($qb ?? $this->query, $searchCondition, $cacheDriver, 60);
        $conditionGenerator->setField('id', 'id', 'i', 'smallint');
        $conditionGenerator->setField('@id', 'id', 'i');

        $conditionGenerator->setField('customer', 'id', 'c', 'integer');
        $conditionGenerator->setField('@customer', 'id', 'c');
        $conditionGenerator->setField('customer_name#first_name', 'firstName', 'c');
        $conditionGenerator->setField('customer_name#last_name', 'lastName', 'c');
        $conditionGenerator->setField('customer_birthday', 'birthday', 'c');

        return $conditionGenerator;
    }
}
