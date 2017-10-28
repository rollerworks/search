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

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler;

use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\ApiPlatform\Doctrine\Orm\CollectionDataProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Replace the default Doctrine CollectionDataProvider with a compatible adapter.
 *
 * This is a compatibility adapter for {@link \ApiPlatform\Core\Bridge\Doctrine\Orm\CollectionDataProvider}
 * until https://github.com/doctrine/doctrine2/pull/6359 is accepted and
 * the minimum Doctrine ORM version is bumped.
 */
final class DoctrineOrmQueryBuilderPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('rollerworks_search.api_platform.doctrine.orm.query_extension.search')) {
            return;
        }

        if (!method_exists(QueryBuilder::class, 'setHint')) {
            $container->findDefinition('api_platform.doctrine.orm.default.collection_data_provider')->setClass(
                CollectionDataProvider::class
            );
        }
    }
}
