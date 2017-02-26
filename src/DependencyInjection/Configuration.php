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

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection;

use Rollerworks\Component\Search\Processor\Psr7SearchProcessor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rollerworks_search');

        $rootNode
            ->children()
                ->arrayNode('processor')
                    ->info('SearchProcessor configuration')
                    ->{class_exists(Psr7SearchProcessor::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('disable_cache')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
