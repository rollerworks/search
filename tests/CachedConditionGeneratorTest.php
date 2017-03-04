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
use Rollerworks\Component\Search\Doctrine\Dbal\CachedConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlConditionGenerator;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Value\ValuesGroup;

final class CachedConditionGeneratorTest extends DbalTestCase
{
    /**
     * @var CachedConditionGenerator
     */
    private $cachedConditionGenerator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheDriver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $conditionGenerator;

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

        $this->conditionGenerator
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

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
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

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
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

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("WHERE me = 'foo'", $this->cachedConditionGenerator->getWhereClause('WHERE '));
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

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals('', $this->cachedConditionGenerator->getWhereClause('WHERE '));
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

        $this->conditionGenerator = new SqlConditionGenerator($this->getConnectionMock(), $searchCondition);

        $this->cachedConditionGenerator = new CachedConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);
        $this->cachedConditionGenerator->setField('customer', 'id', 'I', 'integer');

        self::assertEquals('((I.id IN(18)))', $this->cachedConditionGenerator->getWhereClause());
    }

    protected function setUp()
    {
        parent::setUp();

        $this->cacheDriver = $this->createMock(Cache::class);
        $this->conditionGenerator = $this->createMock(ConditionGenerator::class);

        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $this->conditionGenerator->expects(self::any())->method('getSearchCondition')->willReturn($searchCondition);
        $this->cachedConditionGenerator = new CachedConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);
    }
}
