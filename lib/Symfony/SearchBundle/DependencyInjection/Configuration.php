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

use Rollerworks\Component\Search\ApiPlatform\EventListener\SearchConditionListener;
use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\Elasticsearch\ElasticsearchFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('rollerworks_search');
        $rootNode = $treeBuilder->getRootNode();

        $this->addDoctrineSection($rootNode);
        $this->addApiPlatformSection($rootNode);
        $this->addElasticsearchSection($rootNode);

        return $treeBuilder;
    }

    private function addDoctrineSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
             ->children()
                 ->arrayNode('doctrine')
                     ->validate()
                         ->ifTrue(
                             static function (array $nodes) {
                                 return $nodes['orm']['enabled'] && ! $nodes['dbal']['enabled'];
                             }
                         )
                         ->thenInvalid('rollerworks_search.dbal must be enabled when rollerworks_search.orm is enabled')
                     ->end()
                     ->addDefaultsIfNotSet()
                     ->children()
                         ->arrayNode('dbal')
                             ->{\class_exists(DoctrineDbalFactory::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                         ->end()
                         ->arrayNode('orm')
                             ->{\class_exists(DoctrineOrmFactory::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                             ->fixXmlConfig('entity_manager')
                             ->children()
                                 ->arrayNode('entity_managers')
                                     ->prototype('scalar')->end()
                                 ->end()
                             ->end()
                         ->end()
                     ->end()
                 ->end()
             ->end();
    }

    private function addApiPlatformSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
             ->children()
                 ->arrayNode('api_platform')
                    ->{\class_exists(SearchConditionListener::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                     ->children()
                         ->arrayNode('doctrine_orm')
                             ->{\class_exists(DoctrineOrmFactory::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                         ->end()
                        ->arrayNode('elasticsearch')
                            ->{\class_exists(ElasticsearchFactory::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                        ->end()
                     ->end()
                 ->end()
             ->end();
    }

    private function addElasticsearchSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('elasticsearch')
                    ->{\class_exists(ElasticsearchFactory::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end();
    }
}
