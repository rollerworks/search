<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\Query\Parameter;
use Rollerworks\Component\Search\Doctrine\Orm\CacheWhereBuilder;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\ValuesGroup;

class CacheWhereBuilderTest extends OrmTestCase
{
    /**
     * @var CacheWhereBuilder
     */
    protected $cacheWhereBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheDriver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $whereBuilder;

    public function testGetWhereClauseNoCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.orm.where.dql_invoice')
            ->will($this->returnValue(false));

        $this->cacheDriver
            ->expects($this->never())
            ->method('fetch');

        $this->whereBuilder
            ->expects($this->once())
            ->method('getWhereClause')
            ->will($this->returnValue('WHERE me = foo'));

        $this->whereBuilder
            ->expects($this->atLeastOnce())
            ->method('getParameters')
            ->will($this->returnValue(array('foo' => 1)));

        $this->cacheDriver
            ->expects($this->once())
            ->method('save')
            ->with('rw_search.doctrine.orm.where.dql_invoice', array('WHERE me = foo', array('foo' => 1)), 60);

        $this->cacheWhereBuilder->setCacheKey('invoice');
        $this->cacheWhereBuilder->getWhereClause();
    }

    public function testGetWhereClauseWithCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.orm.where.dql_invoice')
            ->will($this->returnValue(true));

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('rw_search.doctrine.orm.where.dql_invoice')
            ->will($this->returnValue(array('WHERE me = foo', array('foo' => 1))));

        $this->whereBuilder
            ->expects($this->never())
            ->method('getWhereClause');

        $this->whereBuilder
            ->expects($this->never())
            ->method('getParameters');

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
            ->with('rw_search.doctrine.orm.where.dql_invoice')
            ->will($this->returnValue(true));

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('rw_search.doctrine.orm.where.dql_invoice')
            ->will($this->returnValue(array('me = :foo', array('foo' => 1))));

        $this->whereBuilder
            ->expects($this->once())
            ->method('getQueryHintName')
            ->will($this->returnValue('where_builder'));

        $this->whereBuilder
            ->expects($this->once())
            ->method('getQueryHintValue')
            ->will($this->returnValue(function () {}));

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $query = $this->whereBuilder->getQuery();

        $query->setParameter('name', 'user');

        $this->assertEquals('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C', $query->getDQL());

        $this->cacheWhereBuilder->updateQuery(' WHERE ');
        $this->assertEquals('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE me = :foo', $query->getDQL());
        $this->assertEquals(array(new Parameter('name', 'user'), new Parameter('foo', 1)), $query->getParameters()->toArray());

        // Ensure the query is not updated again
        $this->cacheWhereBuilder->updateQuery('WHERE');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->cacheDriver = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->whereBuilder = $this->getMock('Rollerworks\Component\Search\Doctrine\Orm\WhereBuilderInterface');

        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C');
        $searchCondition = new SearchCondition(new FieldSet('invoice'), new ValuesGroup());

        $this->whereBuilder->expects($this->atLeastOnce())->method('getQuery')->will($this->returnValue($query));
        $this->whereBuilder->expects($this->any())->method('getSearchCondition')->will($this->returnValue($searchCondition));

        $this->cacheWhereBuilder = new CacheWhereBuilder($this->whereBuilder, $this->cacheDriver, 60);
    }
}
