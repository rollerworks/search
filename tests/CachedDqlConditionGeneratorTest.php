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

use Doctrine\ORM\Query;
use Psr\SimpleCache\CacheInterface;
use Rollerworks\Component\Search\Doctrine\Orm\CachedDqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\DqlConditionGenerator;
use Rollerworks\Component\Search\SearchConditionBuilder;

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
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheInterface
     */
    protected $cacheDriver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DqlConditionGenerator
     */
    protected $conditionGenerator;

    const CACHE_KEY = '88816f28d71f213a0b933fe002d7aa460a63f011e5d5a03ebf277d420a6d7dee';

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
            ->with(self::CACHE_KEY, ['((C.id IN(2, 5)))', []], 60);

        self::assertEquals('((C.id IN(2, 5)))', $this->cachedConditionGenerator->getWhereClause());
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
            ->willReturn(["me = 'foo'", ['1' => 'he']]);

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals(['1' => 'he'], $this->cachedConditionGenerator->getQueryHintValue()->parameters);
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
            ->willReturn(["me = 'foo'", []]);

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("WHERE me = 'foo'", $this->cachedConditionGenerator->getWhereClause('WHERE '));
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
            ->with('d59779acc5fc8cdde4c1f3c66de9f59a2de72cb884efd05224b72545cfa8ffd5')
            ->willReturn(null);

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals('', $this->cachedConditionGenerator->getWhereClause('WHERE '));
    }

    public function testUpdateQueryWithPrepend()
    {
        $whereCase = $this->cachedConditionGenerator->getWhereClause();
        $this->cachedConditionGenerator->updateQuery();

        $this->assertEquals('((C.id IN(2, 5)))', $whereCase);
        $this->assertEquals(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ((C.id IN(2, 5)))',
            $this->query->getDQL()
        );
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
            ->with('d59779acc5fc8cdde4c1f3c66de9f59a2de72cb884efd05224b72545cfa8ffd5')
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

    protected function setUp()
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
}
