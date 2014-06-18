<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Types\Type as DBALType;
use Rollerworks\Component\Search\Doctrine\Dbal\CacheWhereBuilder;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\ValuesGroup;

class CacheWhereBuilderTest extends DbalTestCase
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
            ->with('rw_search.doctrine.dbal.where.invoice')
            ->will($this->returnValue(false));

        $this->cacheDriver
            ->expects($this->never())
            ->method('fetch');

        $this->whereBuilder
            ->expects($this->once())
            ->method('getWhereClause')
            ->will($this->returnValue('me = :foo'));

        $this->whereBuilder
            ->expects($this->atLeastOnce())
            ->method('getParameters')
            ->will($this->returnValue(array('foo' => 1)));

        $this->whereBuilder
            ->expects($this->atLeastOnce())
            ->method('getParameterTypes')
            ->will($this->returnValue(array('foo' => DBALType::getType('integer'))));

        $this->cacheDriver
            ->expects($this->once())
            ->method('save')
            ->with('rw_search.doctrine.dbal.where.invoice', array('me = :foo', array('foo' => 1), array('foo' => 'integer')), 60);

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $this->assertEquals('me = :foo', $this->cacheWhereBuilder->getWhereClause());
        $this->assertEquals(array('foo' => 1), $this->cacheWhereBuilder->getParameters());
        $this->assertEquals(array('foo' => DBALType::getType('integer')), $this->cacheWhereBuilder->getParameterTypes());
    }

    public function testGetWhereClauseWithCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.dbal.where.invoice')
            ->will($this->returnValue(true));

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('rw_search.doctrine.dbal.where.invoice')
            ->will($this->returnValue(array('me = :foo', array('foo' => 1), array('foo' => 'integer'))));

        $this->whereBuilder
            ->expects($this->never())
            ->method('getParameters')
            ->will($this->returnValue(array('foo' => 1)));

        $this->whereBuilder
            ->expects($this->never())
            ->method('getParameterTypes')
            ->will($this->returnValue(array('foo' => DBALType::getType('integer'))));

        $this->whereBuilder
            ->expects($this->never())
            ->method('getWhereClause');

        $this->whereBuilder
            ->expects($this->never())
            ->method('getParameters');

        $this->whereBuilder
            ->expects($this->never())
            ->method('getParameterTypes');

        $this->cacheDriver
            ->expects($this->never())
            ->method('save');

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $this->assertEquals('me = :foo', $this->cacheWhereBuilder->getWhereClause());
        $this->assertEquals(array('foo' => 1), $this->cacheWhereBuilder->getParameters());
        $this->assertEquals(array('foo' => DBALType::getType('integer')), $this->cacheWhereBuilder->getParameterTypes());
    }

    public function testBindParametersWithCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.dbal.where.invoice')
            ->will($this->returnValue(true));

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('rw_search.doctrine.dbal.where.invoice')
            ->will($this->returnValue(array('me = :foo', array('foo_1' => 1), array('foo_1' => 'integer'))));

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $statement = $this->getMockBuilder('Doctrine\DBAL\Statement')->disableOriginalConstructor()->getMock();
        $statement->expects($this->once())
            ->method('bindValue')
            ->with('foo_1', 1, DBALType::getType('integer'));

        $this->assertEquals('me = :foo', $this->cacheWhereBuilder->getWhereClause());
        $this->assertEquals(array('foo_1' => 1), $this->cacheWhereBuilder->getParameters());
        $this->assertEquals(array('foo_1' => DBALType::getType('integer')), $this->cacheWhereBuilder->getParameterTypes());

        // Ensure the query is not updated again
        $this->cacheWhereBuilder->bindParameters($statement);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->cacheDriver = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->whereBuilder = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilderInterface');

        $searchCondition = new SearchCondition(new FieldSet('invoice'), new ValuesGroup());

        $this->whereBuilder->expects($this->any())->method('getSearchCondition')->will($this->returnValue($searchCondition));
        $this->cacheWhereBuilder = new CacheWhereBuilder($this->whereBuilder, $this->cacheDriver, 60);
    }
}
