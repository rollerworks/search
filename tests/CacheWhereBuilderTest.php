<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\Common\Cache\Cache;
use Rollerworks\Component\Search\Doctrine\Dbal\CacheWhereBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilderInterface;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

class CacheWhereBuilderTest extends DbalTestCase
{
    /**
     * @var CacheWhereBuilder
     */
    private $cacheWhereBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheDriver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $whereBuilder;

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
            ->will($this->returnValue("me = 'foo'"));

        $this->cacheDriver
            ->expects($this->once())
            ->method('save')
            ->with('rw_search.doctrine.dbal.where.invoice', "me = 'foo'", 60);

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $this->assertEquals("me = 'foo'", $this->cacheWhereBuilder->getWhereClause());
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
            ->will($this->returnValue("me = 'foo'"));

        $this->whereBuilder
            ->expects($this->never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects($this->never())
            ->method('save');

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $this->assertEquals("me = 'foo'", $this->cacheWhereBuilder->getWhereClause());
    }

    public function testGetWhereClauseWithCallbackKeyProvider()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('rw_search.doctrine.dbal.where.invoice2')
            ->will($this->returnValue(true));

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('rw_search.doctrine.dbal.where.invoice2')
            ->will($this->returnValue("me = 'foo'"));

        $this->whereBuilder
            ->expects($this->never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects($this->never())
            ->method('save');

        $test = $this;
        $mockWhereBuilder = $test->whereBuilder;

        $this->cacheWhereBuilder->setCacheKey(
            null,
            function ($whereBuilder) use ($test, $mockWhereBuilder) {
                $test->assertSame($mockWhereBuilder, $whereBuilder);

                return 'invoice2';
            }
        );

        $this->assertEquals("me = 'foo'", $this->cacheWhereBuilder->getWhereClause());
    }

    public function testGetWhereWithPrepend()
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
            ->will($this->returnValue("me = 'foo'"));

        $this->whereBuilder
            ->expects($this->never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects($this->never())
            ->method('save');

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $this->assertEquals("WHERE me = 'foo'", $this->cacheWhereBuilder->getWhereClause('WHERE '));
    }

    public function testGetEmptyWhereWithPrepend()
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
            ->will($this->returnValue(''));

        $this->whereBuilder
            ->expects($this->never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects($this->never())
            ->method('save');

        $this->cacheWhereBuilder->setCacheKey('invoice');

        $this->assertEquals('', $this->cacheWhereBuilder->getWhereClause('WHERE '));
    }

    protected function setUp()
    {
        parent::setUp();

        $this->cacheDriver = $this->getMockBuilder(Cache::class)->getMock();
        $this->whereBuilder = $this->createMock(WhereBuilderInterface::class);

        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $this->whereBuilder->expects($this->any())->method('getSearchCondition')->will($this->returnValue($searchCondition));
        $this->cacheWhereBuilder = new CacheWhereBuilder($this->whereBuilder, $this->cacheDriver, 60);
    }
}
