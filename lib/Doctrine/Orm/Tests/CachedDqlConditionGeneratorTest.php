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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface;
use Rollerworks\Component\Search\Doctrine\Orm\CachedDqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\DqlConditionGenerator;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\SearchPrimaryCondition;

/**
 * @group non-functional
 */
class CachedDqlConditionGeneratorTest extends OrmTestCase
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var CachedDqlConditionGenerator
     */
    protected $cachedConditionGenerator;

    /**
     * @var MockObject|CacheInterface
     */
    protected $cacheDriver;

    /**
     * @var DqlConditionGenerator
     */
    protected $conditionGenerator;

    public const CACHE_KEY = 'fe836bd05eeafce1d549fcd8451f7190277f1b995420bead723e98ac721f2089';

    public function testGetWhereClauseNoCache()
    {
        $this->cacheDriver
            ->expects(self::never())
            ->method('has');

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY)
            ->willReturn(null);

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
            );

        self::assertEquals('(((C.id = :search_0 OR C.id = :search_1)))', $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals(new ArrayCollection([':search_0' => [2, Type::getType('integer')], ':search_1' => [5, Type::getType('integer')]]), $this->cachedConditionGenerator->getParameters());
    }

    public function testGetWhereClauseWithCache()
    {
        $this->cacheDriver
            ->expects(self::never())
            ->method('has');

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY)
            ->willReturn(["me = 'foo'", [':search' => [1, 'integer']]]);

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals(new ArrayCollection([':search' => [1, Type::getType('integer')]]), $this->cachedConditionGenerator->getParameters());
    }

    public function testGetWhereWithPrepend()
    {
        $this->cacheDriver
            ->expects(self::never())
            ->method('has');

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(self::CACHE_KEY)
            ->willReturn(["me = 'foo'", [':search' => [1, 'integer']]]);

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals(new ArrayCollection([':search' => [1, Type::getType('integer')]]), $this->cachedConditionGenerator->getParameters());
    }

    public function testGetEmptyWhereWithPrepend()
    {
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C');
        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();

        $this->conditionGenerator = $this->getOrmFactory()->createConditionGenerator($query, $searchCondition);
        $this->conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $this->conditionGenerator->setField('id', 'id', null, null, 'smallint');

        $this->conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
        $this->conditionGenerator->setField('customer', 'id');
        $this->conditionGenerator->setField('customer_name#first_name', 'firstName');
        $this->conditionGenerator->setField('customer_name#last_name', 'lastName');
        $this->conditionGenerator->setField('customer_birthday', 'birthday');

        $this->cacheDriver = $this->createMock(CacheInterface::class);
        $this->cachedConditionGenerator = new CachedDqlConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);

        $this->cacheDriver
            ->expects(self::never())
            ->method('has');

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('f8813fdfdea9d74adea380e30645c5e2705d3b4114dc5fbf252e63e583ea598d')
            ->willReturn(null);

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals('', $this->cachedConditionGenerator->getWhereClause('WHERE '));
        self::assertEquals(new ArrayCollection(), $this->cachedConditionGenerator->getParameters());
    }

    public function testUpdateQueryWithPrepend()
    {
        $whereCase = $this->cachedConditionGenerator->getWhereClause();
        $this->cachedConditionGenerator->updateQuery();

        $this->assertEquals('(((C.id = :search_0 OR C.id = :search_1)))', $whereCase);
        $this->assertEquals(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE (((C.id = :search_0 OR C.id = :search_1)))',
            $this->query->getDQL()
        );
        self::assertEquals(new ArrayCollection([new Parameter('search_0', 2, Type::getType('integer')), new Parameter('search_1', 5, Type::getType('integer'))]), $this->conditionGenerator->getQuery()->getParameters());
    }

    public function testUpdateQueryWithNoResult()
    {
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C');
        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();

        $this->conditionGenerator = $this->getOrmFactory()->createConditionGenerator($query, $searchCondition);
        $this->conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $this->conditionGenerator->setField('id', 'id', null, null, 'smallint');

        $this->conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
        $this->conditionGenerator->setField('customer', 'id');
        $this->conditionGenerator->setField('customer_name#first_name', 'firstName');
        $this->conditionGenerator->setField('customer_name#last_name', 'lastName');
        $this->conditionGenerator->setField('customer_birthday', 'birthday');

        $this->cacheDriver = $this->createMock(CacheInterface::class);
        $this->cachedConditionGenerator = new CachedDqlConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);

        $this->cacheDriver
            ->expects(self::never())
            ->method('has');

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('f8813fdfdea9d74adea380e30645c5e2705d3b4114dc5fbf252e63e583ea598d')
            ->willReturn(null);

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals('', $this->cachedConditionGenerator->getWhereClause('WHERE '));

        $whereCase = $this->cachedConditionGenerator->getWhereClause();
        $this->cachedConditionGenerator->updateQuery();

        $this->assertEquals('', $whereCase);
        $this->assertEquals(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C',
            $query->getDQL()
        );
    }

    public function testGetWhereClauseWithCacheAndPrimaryCond()
    {
        $cacheDriverProphecy = $this->prophesize(CacheInterface::class);
        $cacheDriverProphecy->get('7fbf724b9ed73837313684319ec3d5772a53c6c0373dbf90a880e383900e5e07')->willReturn(["me = 'foo'", ['1' => 'he']]);
        $cacheDriverProphecy->get('044d0466ebd4264c4e33c64a0e341df225657353316869049ab3c24cbba86ffa')->willReturn(["you = 'me' AND me = 'foo'", ['1' => 'he']]);
        $cacheDriver = $cacheDriverProphecy->reveal();

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();
        $cachedConditionGenerator = $this->createCachedConditionGenerator($cacheDriver, $searchCondition);

        $searchCondition2 = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $searchCondition2->setPrimaryCondition(new SearchPrimaryCondition(
            SearchConditionBuilder::create($this->getFieldSet())
                ->field('customer')
                    ->addSimpleValue(2)
                ->end()
            ->getSearchCondition()->getValuesGroup())
        );

        $cachedConditionGenerator2 = $this->createCachedConditionGenerator($cacheDriver, $searchCondition2);

        self::assertEquals("me = 'foo'", $cachedConditionGenerator->getWhereClause());
        self::assertEquals("you = 'me' AND me = 'foo'", $cachedConditionGenerator2->getWhereClause());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDriver = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();

        $this->query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C');
        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $this->conditionGenerator = $this->getOrmFactory()->createConditionGenerator($this->query, $searchCondition);
        $this->conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $this->conditionGenerator->setField('id', 'id', null, null, 'smallint');

        $this->conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
        $this->conditionGenerator->setField('customer', 'id');
        $this->conditionGenerator->setField('customer_name#first_name', 'firstName');
        $this->conditionGenerator->setField('customer_name#last_name', 'lastName');
        $this->conditionGenerator->setField('customer_birthday', 'birthday');

        $this->cacheDriver = $this->createMock(CacheInterface::class);
        $this->cachedConditionGenerator = new CachedDqlConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);
    }

    private function createCachedConditionGenerator(CacheInterface $cacheDriver, SearchCondition $searchCondition): CachedDqlConditionGenerator
    {
        $conditionGenerator = $this->getOrmFactory()->createConditionGenerator($this->query, $searchCondition);
        $conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $conditionGenerator->setField('id', 'id', null, null, 'smallint');

        $conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
        $conditionGenerator->setField('customer', 'id', null, null, 'integer');
        $conditionGenerator->setField('customer_name#first_name', 'firstName');
        $conditionGenerator->setField('customer_name#last_name', 'lastName');
        $conditionGenerator->setField('customer_birthday', 'birthday');

        return new CachedDqlConditionGenerator($conditionGenerator, $cacheDriver, 60);
    }
}
