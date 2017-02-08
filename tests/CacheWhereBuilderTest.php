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

use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Doctrine\Dbal\CacheWhereBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilderInterface;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
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
        $cacheKey = '';

        $this->cacheDriver
            ->expects($this->once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(false);

        $this->cacheDriver
            ->expects($this->never())
            ->method('get');

        $this->whereBuilder
            ->expects($this->once())
            ->method('getWhereClause')
            ->willReturn("me = 'foo'");

        $this->cacheDriver
            ->expects($this->once())
            ->method('set')
            ->with(
                self::callback(
                    function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                "me = 'foo'",
                60
            );

        self::assertEquals("me = 'foo'", $this->cacheWhereBuilder->getWhereClause());
    }

    public function testGetWhereClauseWithCache()
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(true);

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    return $cacheKey === $key;
                })
            )
            ->willReturn("me = 'foo'");

        $this->whereBuilder
            ->expects(self::never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("me = 'foo'", $this->cacheWhereBuilder->getWhereClause());
    }

    public function testGetWhereWithPrepend()
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(true);

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    return $cacheKey === $key;
                })
            )
            ->willReturn("me = 'foo'");

        $this->whereBuilder
            ->expects(self::never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("WHERE me = 'foo'", $this->cacheWhereBuilder->getWhereClause('WHERE '));
    }

    public function testGetEmptyWhereWithPrepend()
    {
        $this->cacheDriver
            ->expects(self::once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(true);

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    return $cacheKey === $key;
                })
            )
            ->willReturn('');

        $this->whereBuilder
            ->expects(self::never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals('', $this->cacheWhereBuilder->getWhereClause('WHERE '));
    }

    public function testFieldMappingDelegation()
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects($this->once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(false);

        $this->cacheDriver
            ->expects($this->never())
            ->method('get');

        $this->cacheDriver
            ->expects($this->once())
            ->method('set')
            ->with(
                self::callback(
                    function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                '((I.id IN(18)))',
                60
            );

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(18)
            ->end()
        ->getSearchCondition();

        $this->whereBuilder = new WhereBuilder($this->getConnectionMock(), $searchCondition);

        $this->cacheWhereBuilder = new CacheWhereBuilder($this->whereBuilder, $this->cacheDriver, 60);
        $this->cacheWhereBuilder->setField('customer', 'id', 'I', 'integer');

        self::assertEquals('((I.id IN(18)))', $this->cacheWhereBuilder->getWhereClause());
    }

    protected function setUp()
    {
        parent::setUp();

        $this->cacheDriver = $this->createMock(Cache::class);
        $this->whereBuilder = $this->createMock(WhereBuilderInterface::class);

        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $this->whereBuilder->expects(self::any())->method('getSearchCondition')->willReturn($searchCondition);
        $this->cacheWhereBuilder = new CacheWhereBuilder($this->whereBuilder, $this->cacheDriver, 60);
    }
}
