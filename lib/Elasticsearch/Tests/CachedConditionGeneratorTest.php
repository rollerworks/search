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

namespace Rollerworks\Component\Search\Tests\Elasticsearch;

use Elastica\Query;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Elasticsearch\CachedConditionGenerator;
use Rollerworks\Component\Search\Elasticsearch\ConditionGenerator;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Class CachedConditionGeneratorTest.
 *
 * @group cache
 */
class CachedConditionGeneratorTest extends ElasticsearchTestCase
{
    /**
     * @var CachedConditionGenerator
     */
    private $cachedConditionGenerator;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheDriver;

    /**
     * @var ConditionGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $conditionGenerator;

    public function testGetQueryNoCache()
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

        $query = $this->mockQuery();

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
                $query,
                60
            );

        $this->conditionGenerator
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        self::assertEquals($query, $this->cachedConditionGenerator->getQuery());
    }

    public function testGetQueryWithCache()
    {
        $cacheKey = '';

        $query = $this->mockQuery();

        $this->cacheDriver
            ->expects($this->once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(true);
        $this->cacheDriver
            ->expects($this->never())
            ->method('set');
        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    return $cacheKey === $key;
                })
            )
            ->willReturn($query);

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getQuery');

        self::assertEquals($query, $this->cachedConditionGenerator->getQuery());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());
        /** @var ConditionGenerator conditionGenerator */
        $this->conditionGenerator = $this->createMock(ConditionGenerator::class);
        $this->conditionGenerator
            ->expects(self::any())
            ->method('getSearchCondition')
            ->willReturn($searchCondition);

        /** @var Cache cacheDriver */
        $this->cacheDriver = $this->createMock(Cache::class);

        $this->cachedConditionGenerator = new CachedConditionGenerator(
            $this->conditionGenerator,
            $this->cacheDriver,
            60
        );
    }

    private function mockQuery(): Query
    {
        return $this->getMockBuilder(Query::class)
            ->getMock();
    }
}
