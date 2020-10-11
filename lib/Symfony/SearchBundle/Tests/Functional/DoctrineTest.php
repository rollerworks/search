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

namespace Rollerworks\Bundle\SearchBundle\Tests\Functional;

use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;

/**
 * @internal
 */
final class DoctrineTest extends FunctionalTestCase
{
    /** @test */
    public function doctrine_dbal_factory_is_accessible(): void
    {
        if (! \class_exists(DoctrineDbalFactory::class)) {
            self::markTestSkipped('rollerworks/search-doctrine-dbal is not installed');
        }

        $client = self::newClient(['config' => 'doctrine_dbal.yml']);
        $client->getKernel()->boot();

        $container = $client->getContainer();

        self::assertInstanceOf(DoctrineDbalFactory::class, $container->get('rollerworks_search.doctrine_dbal.factory'));
    }

    /** @test */
    public function doctrine_orm_factory_is_accessible(): void
    {
        if (! \class_exists(DoctrineOrmFactory::class)) {
            self::markTestSkipped('rollerworks/search-doctrine-orm is not installed');
        }

        $client = self::newClient(['config' => 'doctrine_orm.yml']);
        $client->getKernel()->boot();

        $container = $client->getContainer();

        self::assertInstanceOf(DoctrineDbalFactory::class, $container->get('rollerworks_search.doctrine_dbal.factory'));
        self::assertInstanceOf(DoctrineOrmFactory::class, $container->get('rollerworks_search.doctrine_orm.factory'));
    }
}
