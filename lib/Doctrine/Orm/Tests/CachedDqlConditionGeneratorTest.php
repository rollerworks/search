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
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\SearchPreCondition;

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

    public const CACHE_KEY = '8dbca2a85403b7afbece9461df29c567cf32dfda2c10a75b5aff4d6ac44e4c84';

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
            ->with('a9c044cceecfd09b772d1190e8c2cc32b11c59d08b7d20b4b4459bab8f9b4bd6')
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
            ->with('a9c044cceecfd09b772d1190e8c2cc32b11c59d08b7d20b4b4459bab8f9b4bd6')
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

    public function testGetWhereClauseWithCacheAndPreCond()
    {
        $cacheDriverProphecy = $this->prophesize(CacheInterface::class);
        $cacheDriverProphecy->has('41329a2e34ac65573fb097e858a5b12685b0327e3e55b5bb48902e4731b42afa')->willReturn(true);
        $cacheDriverProphecy->get('41329a2e34ac65573fb097e858a5b12685b0327e3e55b5bb48902e4731b42afa')->willReturn(["me = 'foo'", ['1' => 'he']]);
        $cacheDriverProphecy->has('7f1ddddc8869a6cdd6308b790b854f2cac46f67e22e8fd978ead76ab881df323')->willReturn(true);
        $cacheDriverProphecy->get('7f1ddddc8869a6cdd6308b790b854f2cac46f67e22e8fd978ead76ab881df323')->willReturn(["you = 'me' AND me = 'foo'", ['1' => 'he']]);
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

        $searchCondition2->setPreCondition(new SearchPreCondition(
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
