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

use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Elasticsearch\CachedConditionGenerator;
use Rollerworks\Component\Search\Elasticsearch\ElasticsearchFactory;
use Rollerworks\Component\Search\Elasticsearch\QueryConditionGenerator;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Class ElasticsearchFactoryTest.
 */
class ElasticsearchFactoryTest extends ElasticsearchTestCase
{
    /**
     * @var ElasticsearchFactory
     */
    protected $factory;

    public function testCreateConditionGenerator()
    {
        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());
        $conditionGenerator = $this->factory->createConditionGenerator($searchCondition);

        /** @noinspection PhpInternalEntityUsedInspection */
        self::assertSame($searchCondition, $conditionGenerator->getSearchCondition());
    }

    public function testCreateCacheConditionGenerator()
    {
        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $conditionGenerator = $this->factory->createConditionGenerator($searchCondition);
        $this->assertInstanceOf(QueryConditionGenerator::class, $conditionGenerator);

        $cacheConditionGenerator = $this->factory->createCachedConditionGenerator($conditionGenerator);
        $this->assertInstanceOf(CachedConditionGenerator::class, $cacheConditionGenerator);
    }

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Cache $cacheDriver */
        $cacheDriver = $this->getMockBuilder(Cache::class)->getMock();
        $this->factory = new ElasticsearchFactory($cacheDriver);
    }
}
