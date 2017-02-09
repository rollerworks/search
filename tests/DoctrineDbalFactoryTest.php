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
use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

class DoctrineDbalFactoryTest extends DbalTestCase
{
    /**
     * @var DoctrineDbalFactory
     */
    protected $factory;

    public function testCreateWhereBuilder()
    {
        $connection = $this->getConnectionMock();
        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($connection, $searchCondition);

        self::assertSame($searchCondition, $whereBuilder->getSearchCondition());
    }

    public function testCreateCacheWhereBuilder()
    {
        $connection = $this->getConnectionMock();
        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($connection, $searchCondition);
        $this->assertInstanceOf(WhereBuilder::class, $whereBuilder);

        $cacheWhereBuilder = $this->factory->createCacheWhereBuilder($whereBuilder);
        $this->assertInstanceOf(CacheWhereBuilder::class, $cacheWhereBuilder);
    }

    protected function setUp()
    {
        parent::setUp();

        $cacheDriver = $this->getMockBuilder(Cache::class)->getMock();
        $this->factory = new DoctrineDbalFactory($cacheDriver);
    }
}
