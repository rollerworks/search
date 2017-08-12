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

use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Psr\SimpleCache\CacheInterface;
use Rollerworks\Component\Search\Doctrine\Orm\CachedNativeQueryConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\NativeQueryConditionGenerator;
use Rollerworks\Component\Search\SearchConditionBuilder;

/**
 * @group non-functional
 */
final class CachedNativeQueryConditionGeneratorTest extends OrmTestCase
{
    /**
     * @var NativeQuery
     */
    private $query;

    /**
     * @var CachedNativeQueryConditionGenerator
     */
    protected $cachedConditionGenerator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheDriver;

    /**
     * @var NativeQueryConditionGenerator
     */
    protected $conditionGenerator;

    const CACHE_KEY = '8c6844fc8d25be938498e078d917a8fa2e39e00c8fc91834fbd4cc557d99454c';

    public function testGetWhereClauseNoCache()
    {
        $name = $this->conn->getDatabasePlatform()->getName();

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
            ->with(self::CACHE_KEY, 'sqlite' === $name ? '((I.customer IN(2, 5)))' : "((I.customer IN('2', '5')))", 60);

        if ('sqlite' === $name) {
            self::assertEquals('((I.customer IN(2, 5)))', $this->cachedConditionGenerator->getWhereClause());
        } else {
            self::assertEquals("((I.customer IN('2', '5')))", $this->cachedConditionGenerator->getWhereClause());
        }
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
            ->willReturn("me = 'foo'");

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
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
            ->willReturn("me = 'foo'");

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("WHERE me = 'foo'", $this->cachedConditionGenerator->getWhereClause('WHERE '));
    }

    public function testGetEmptyWhereWithPrepend()
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata(Fixtures\Entity\ECommerceInvoice::class, 'I', ['id' => 'invoice_id']);
        $rsm->addJoinedEntityFromClassMetadata(Fixtures\Entity\ECommerceCustomer::class, 'C', 'I', 'customer', ['id' => 'customer_id']);

        $query = $this->em->createNativeQuery('SELECT * FROM invoice AS I JOIN customer AS C ON I.customer = C.id', $rsm);

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();

        $this->conditionGenerator = $this->getOrmFactory()->createConditionGenerator($query, $searchCondition);
        $this->conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $this->conditionGenerator->setField('id', 'id', null, null, 'smallint');
        $this->conditionGenerator->setField('customer', 'customer', null, null, 'integer');

        $this->conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
        $this->conditionGenerator->setField('customer_name#first_name', 'firstName');
        $this->conditionGenerator->setField('customer_name#last_name', 'lastName');
        $this->conditionGenerator->setField('customer_birthday', 'birthday');

        $this->cacheDriver = $this->createMock(CacheInterface::class);
        $this->cachedConditionGenerator = new CachedNativeQueryConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);

        $this->cacheDriver
            ->expects(self::never())
            ->method('has');

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('759a0d47304f47ed44320078f30e036df13f66515686051028376b1958057bf8')
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

        if ('sqlite' === $this->conn->getDatabasePlatform()->getName()) {
            self::assertEquals('((I.customer IN(2, 5)))', $this->cachedConditionGenerator->getWhereClause());
        } else {
            self::assertEquals("((I.customer IN('2', '5')))", $this->cachedConditionGenerator->getWhereClause());
        }

        $this->assertEquals(
            'SELECT * FROM invoice AS I JOIN customer AS C ON I.customer = C.id WHERE '.$whereCase,
            $this->query->getSQL()
        );
    }

    public function testUpdateQueryWithNoResult()
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata(Fixtures\Entity\ECommerceInvoice::class, 'I', ['id' => 'invoice_id']);
        $rsm->addJoinedEntityFromClassMetadata(Fixtures\Entity\ECommerceCustomer::class, 'C', 'I', 'customer', ['id' => 'customer_id']);

        $query = $this->em->createNativeQuery('SELECT * FROM invoice AS I JOIN customer AS C ON I.customer = C.id', $rsm);
        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();

        $this->conditionGenerator = $this->getOrmFactory()->createConditionGenerator($query, $searchCondition);
        $this->conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $this->conditionGenerator->setField('id', 'id', null, null, 'smallint');
        $this->conditionGenerator->setField('customer', 'customer', null, null, 'integer');

        $this->conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
        $this->conditionGenerator->setField('customer_name#first_name', 'firstName');
        $this->conditionGenerator->setField('customer_name#last_name', 'lastName');
        $this->conditionGenerator->setField('customer_birthday', 'birthday');

        $this->cacheDriver = $this->createMock(CacheInterface::class);
        $this->cachedConditionGenerator = new CachedNativeQueryConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);

        $this->cacheDriver
            ->expects(self::never())
            ->method('has');

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('759a0d47304f47ed44320078f30e036df13f66515686051028376b1958057bf8')
            ->willReturn(null);

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals('', $this->cachedConditionGenerator->getWhereClause('WHERE '));

        $whereCase = $this->cachedConditionGenerator->getWhereClause();
        $this->cachedConditionGenerator->updateQuery();

        $this->assertEquals('', $whereCase);
        $this->assertEquals(
            'SELECT * FROM invoice AS I JOIN customer AS C ON I.customer = C.id',
            $query->getSQL()
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata(Fixtures\Entity\ECommerceInvoice::class, 'I', ['id' => 'invoice_id']);
        $rsm->addJoinedEntityFromClassMetadata(Fixtures\Entity\ECommerceCustomer::class, 'C', 'I', 'customer', ['id' => 'customer_id']);

        $this->query = $this->em->createNativeQuery(
            'SELECT * FROM invoice AS I JOIN customer AS C ON I.customer = C.id',
            $rsm
        );

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $this->conditionGenerator = $this->getOrmFactory()->createConditionGenerator($this->query, $searchCondition);
        $this->conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
        $this->conditionGenerator->setField('id', 'id', null, null, 'smallint');
        $this->conditionGenerator->setField('customer', 'customer', null, null, 'integer');

        $this->conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
        $this->conditionGenerator->setField('customer_name#first_name', 'firstName');
        $this->conditionGenerator->setField('customer_name#last_name', 'lastName');
        $this->conditionGenerator->setField('customer_birthday', 'birthday');

        $this->cacheDriver = $this->createMock(CacheInterface::class);
        $this->cachedConditionGenerator = new CachedNativeQueryConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);
    }
}
