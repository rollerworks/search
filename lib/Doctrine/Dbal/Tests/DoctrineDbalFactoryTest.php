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
use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlConditionGenerator;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @internal
 */
final class DoctrineDbalFactoryTest extends DbalTestCase
{
    /**
     * @var DoctrineDbalFactory
     */
    protected $factory;

    /** @test */
    public function create_condition_generator(): void
    {
        $connection = $this->getConnectionMock();
        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $conditionGenerator = $this->factory->createConditionGenerator($connection, $searchCondition);

        self::assertSame($searchCondition, $conditionGenerator->getSearchCondition());
    }

    /** @test */
    public function create_cache_condition_generator(): void
    {
        $connection = $this->getConnectionMock();
        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $conditionGenerator = $this->factory->createConditionGenerator($connection, $searchCondition);
        self::assertInstanceOf(SqlConditionGenerator::class, $conditionGenerator);

        $cacheConditionGenerator = $this->factory->createCachedConditionGenerator($conditionGenerator);
        self::assertInstanceOf(CachedConditionGenerator::class, $cacheConditionGenerator);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $cacheDriver = $this->getMockBuilder(Cache::class)->getMock();
        $this->factory = new DoctrineDbalFactory($cacheDriver);
    }
}
