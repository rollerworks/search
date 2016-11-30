<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Orm\CacheWhereBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\FieldConfigBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder;
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
     * @var \PHPUnit_Framework_MockObject_MockObject|WhereBuilder
     */
    protected $whereBuilder;

    public function testGetWhereClauseWithNoCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.orm.where.dql.invoice')
            ->will($this->returnValue(false));

        $this->cacheDriver
            ->expects($this->never())
            ->method('fetch');

        $this->whereBuilder
            ->expects($this->once())
            ->method('getWhereClause')
            ->will($this->returnValue('me = 1'));

        $this->whereBuilder
            ->expects($this->atLeastOnce())
            ->method('getParameters')
            ->will($this->returnValue([1]));

        $this->cacheDriver
            ->expects($this->once())
            ->method('save')
            ->with('rw_search.doctrine.orm.where.dql.invoice', ['me = 1', [1]], 60);

        $this->cacheWhereBuilder->setCacheKey('invoice');
        $this->cacheWhereBuilder->getWhereClause();
    }

    public function testGetWhereClauseWithCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.orm.where.dql.invoice')
            ->will($this->returnValue(true));

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('rw_search.doctrine.orm.where.dql.invoice')
            ->will($this->returnValue(['me = foo', [0 => 1]]));

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
            ->with('rw_search.doctrine.orm.where.dql.invoice')
            ->will($this->returnValue(true));

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('rw_search.doctrine.orm.where.dql.invoice')
            ->will($this->returnValue(['me = foo', [0 => 1]]));

        $this->whereBuilder
            ->expects($this->once())
            ->method('getQueryHintName')
            ->will($this->returnValue('where_builder'));

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $query = $this->whereBuilder->getQuery();
        $this->cacheWhereBuilder->updateQuery();

        $this->assertEquals('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE me = foo', $query->getDQL());
    }

    protected function setUp()
    {
        parent::setUp();

        $fieldSet = new FieldSet('invoice');

        $this->cacheDriver = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();
        $this->whereBuilder = $this->getMockBuilder('Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder')
           ->disableOriginalConstructor()
           ->getMock()
        ;

        $config = new FieldConfigBuilder($this->em, $fieldSet);
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C');
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

        $this->whereBuilder
            ->expects($this->any())
            ->method('getFieldsConfig')
            ->will(
                $this->returnValue($config)
            );

        $this->cacheWhereBuilder = new CacheWhereBuilder($this->whereBuilder, $this->cacheDriver, 60);
    }
}
