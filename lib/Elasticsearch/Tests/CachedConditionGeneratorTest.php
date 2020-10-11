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
 *
 * @internal
 */
final class CachedConditionGeneratorTest extends ElasticsearchTestCase
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

    /** @test */
    public function get_query_no_cache(): void
    {
        $cacheKey = '';
        $this->cacheDriver
            ->expects(self::once())
            ->method('has')
            ->with(
                self::callback(static function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(false);

        $query = $this->mockQuery();

        $this->cacheDriver
            ->expects(self::never())
            ->method('get');
        $this->cacheDriver
            ->expects(self::once())
            ->method('set')
            ->with(
                self::callback(
                    static function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                $query,
                60
            );

        $this->conditionGenerator
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        self::assertEquals($query, $this->cachedConditionGenerator->getQuery());
    }

    /** @test */
    public function get_query_with_cache(): void
    {
        $cacheKey = '';

        $query = $this->mockQuery();

        $this->cacheDriver
            ->expects(self::once())
            ->method('has')
            ->with(
                self::callback(static function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(true);
        $this->cacheDriver
            ->expects(self::never())
            ->method('set');
        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(static function (string $key) use (&$cacheKey) {
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
