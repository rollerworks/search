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

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Psr\SimpleCache\CacheInterface;
use Rollerworks\Component\Search\Doctrine\Orm\CachedDqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\Doctrine\Orm\QueryBuilderConditionGenerator;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @group non-functional
 *
 * @internal
 */
final class DoctrineOrmFactoryTest extends OrmTestCase
{
    /**
     * @var DoctrineOrmFactory
     */
    protected $factory;

    /** @test */
    public function create_condition_generator(): void
    {
        $condition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());
        $qb = $this->em->createQueryBuilder()
            ->select('I')
            ->from(ECommerceInvoice::class, 'I')
            ->join('I.customer', 'C');

        $conditionGenerator = $this->factory->createConditionGenerator($qb, $condition);
        self::assertInstanceOf(QueryBuilderConditionGenerator::class, $conditionGenerator);
    }

    /** @test */
    public function cached_dql_condition_generator(): void
    {
        $qb = $this->em->createQueryBuilder()
            ->select('I')
            ->from(ECommerceInvoice::class, 'I')
            ->join('I.customer', 'C');
        $searchCondition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());
        $cachedConditionGenerator = $this->factory->createCachedConditionGenerator($qb, $searchCondition);

        self::assertInstanceOf(CachedDqlConditionGenerator::class, $cachedConditionGenerator);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $cacheDriver = $this->createMock(CacheInterface::class);
        $this->factory = new DoctrineOrmFactory($cacheDriver);
    }
}
