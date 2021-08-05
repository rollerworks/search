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

use Elastica\Client as ElasticaClient;
use JoliCode\Elastically\Client as ElasticallyClient;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ElasticaClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('fos_elastica.client')) {
            $container->setAlias('rollerworks_search.elasticsearch.client', 'fos_elastica.client');

            return;
        }

        if ($container->has(ElasticaClient::class)) {
            $container->setAlias('rollerworks_search.elasticsearch.client', ElasticaClient::class);

            return;
        }

        if ($container->has(ElasticallyClient::class)) {
            $container->setAlias('rollerworks_search.elasticsearch.client', ElasticallyClient::class);
        }
    }
}
