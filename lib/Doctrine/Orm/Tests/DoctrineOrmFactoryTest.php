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
use Rollerworks\Component\Search\Doctrine\Orm\DqlConditionGenerator;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @group non-functional
 */
class DoctrineOrmFactoryTest extends OrmTestCase
{
    /**
     * @var DoctrineOrmFactory
     */
    protected $factory;

    public function testCreateConditionGenerator()
    {
        $condition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');

        $conditionGenerator = $this->factory->createConditionGenerator($query, $condition);
        $this->assertInstanceOf(DqlConditionGenerator::class, $conditionGenerator);
    }

    public function testCachedDqlConditionGenerator()
    {
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');
        $searchCondition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());

        $conditionGenerator = $this->factory->createConditionGenerator($query, $searchCondition);
        $cachedConditionGenerator = $this->factory->createCachedConditionGenerator($conditionGenerator);

        self::assertInstanceOf(CachedDqlConditionGenerator::class, $cachedConditionGenerator);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $cacheDriver = $this->createMock(CacheInterface::class);
        $this->factory = new DoctrineOrmFactory($cacheDriver);
    }
}
