<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\CacheNativeWhereBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\NativeWhereBuilder;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\ValuesGroup;

class CacheNativeWhereBuilderTest extends OrmTestCase
{
    /**
     * @var CacheNativeWhereBuilder
     */
    protected $cacheWhereBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheDriver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|NativeWhereBuilder
     */
    protected $whereBuilder;

    public function testGetWhereClauseWithNoCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.orm.where.nat.invoice')
            ->will($this->returnValue(false));

        $this->cacheDriver
            ->expects($this->never())
            ->method('fetch');

        $this->whereBuilder
            ->expects($this->once())
            ->method('getWhereClause')
            ->will($this->returnValue('me = 1'));

        $this->cacheDriver
            ->expects($this->once())
            ->method('save')
            ->with('rw_search.doctrine.orm.where.nat.invoice', 'me = 1', 60);

        $this->cacheWhereBuilder->setCacheKey('invoice');
        $this->cacheWhereBuilder->getWhereClause();
    }

    public function testGetWhereClauseWithCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.orm.where.nat.invoice')
            ->will($this->returnValue(true));

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('rw_search.doctrine.orm.where.nat.invoice')
            ->will($this->returnValue('me = foo'));

        $this->whereBuilder
            ->expects($this->never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects($this->never())
            ->method('save');

        $this->cacheWhereBuilder->setCacheKey('invoice');
        $this->cacheWhereBuilder->getWhereClause();
    }

    public function testUpdateQueryWithCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.orm.where.nat.invoice')
            ->will($this->returnValue(true));

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('rw_search.doctrine.orm.where.nat.invoice')
            ->will($this->returnValue('me = foo'));

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $query = $this->whereBuilder->getQuery();
        $this->cacheWhereBuilder->updateQuery();

        $this->assertEquals('SELECT * FROM invoice AS I JOIN customer AS C ON I.customer = C.id WHERE me = foo', $query->getSQL());
    }

    protected function setUp()
    {
        parent::setUp();

        $fieldSet = new FieldSet('invoice');

        $this->cacheDriver = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->whereBuilder = $this->getMockBuilder('Rollerworks\Component\Search\Doctrine\Orm\NativeWhereBuilder')
           ->disableOriginalConstructor()
           ->getMock()
        ;

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice', 'I', ['id' => 'invoice_id']);
        $rsm->addJoinedEntityFromClassMetadata('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer', 'C', 'I', 'customer', ['id' => 'customer_id']);

        $query = $this->em->createNativeQuery('SELECT * FROM invoice AS I JOIN customer AS C ON I.customer = C.id', $rsm);
        $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());

        $this->whereBuilder
            ->expects($this->any())
            ->method('getQuery')
            ->will(
                $this->returnValue($query)
            );

        $this->whereBuilder
            ->expects($this->any())
            ->method('getEntityManager')
            ->will(
                $this->returnValue($this->em)
            );

        $this->whereBuilder
            ->expects($this->any())
            ->method('getSearchCondition')->will(
                $this->returnValue($searchCondition)
            );

        $this->cacheWhereBuilder = new CacheNativeWhereBuilder($this->whereBuilder, $this->cacheDriver, 60);
    }
}
