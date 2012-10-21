<?php

/**
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
/**
 * This class contains the configuration information for the bundle.
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('rollerworks_record_filter');

        $rootNode
            ->children()
                ->scalarNode('metadata_cache')->cannotBeEmpty()->defaultValue('%kernel.cache_dir%/record_filter_metadata')->end()

                ->scalarNode('filters_directory')->cannotBeEmpty()->defaultValue('%kernel.cache_dir%/record_filter')->end()
                ->scalarNode('filters_namespace')->cannotBeEmpty()->defaultValue('RecordFilter')->end()

                ->arrayNode('doctrine')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('orm')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('default_entity_manager')->cannotBeEmpty()->defaultValue('%doctrine.default_entity_manager%')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        $this->addFieldSetsSection($rootNode);
        $this->addFactoriesSection($rootNode);

        return $treeBuilder;
    }

    private function addFactoriesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('factory')
            ->children()
                ->arrayNode('factories')
                    ->addDefaultsIfNotSet()
                    ->children()

                        ->arrayNode('fieldset')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('namespace')->cannotBeEmpty()->defaultValue('%rollerworks_record_filter.filters_namespace%')->end()
                                ->scalarNode('label_translator_prefix')->defaultValue('')->end()
                                ->scalarNode('label_translator_domain')->defaultValue('filters')->end()
                                ->booleanNode('auto_generate')->defaultFalse()->end()
                            ->end()
                        ->end()

                        ->arrayNode('doctrine')
                            ->addDefaultsIfNotSet()
                            ->children()

                                ->arrayNode('orm')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->arrayNode('wherebuilder')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('namespace')->cannotBeEmpty()->defaultValue('%rollerworks_record_filter.filters_namespace%')->end()
                                                ->scalarNode('default_entity_manager')->cannotBeEmpty()->defaultValue('%doctrine.default_entity_manager%')->end()
                                                ->booleanNode('auto_generate')->defaultFalse()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()

                            ->end()
                        ->end()

                    ->end()
                ->end()
            ->end();
    }

    private function addFieldSetsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('fieldset')
            ->children()
                ->arrayNode('fieldsets')
                    ->useAttributeAsKey('name')
                    ->performNoDeepMerging()
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->arrayNode('import')
                                ->prototype('array')
                                    ->beforeNormalization()->ifString()->then(function($v) { return array('class' => $v); })->end()
                                    ->children()
                                        ->scalarNode('class')->isRequired()->end()
                                        ->arrayNode('include_fields')->prototype('scalar')->defaultValue(array())->end()->end()
                                        ->arrayNode('exclude_fields')->prototype('scalar')->defaultValue(array())->end()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('fields')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->canBeUnset()
                                    ->children()
                                        ->booleanNode('required')->defaultFalse()->end()
                                        ->booleanNode('accept_ranges')->defaultFalse()->end()
                                        ->booleanNode('accept_compares')->defaultFalse()->end()
                                        ->scalarNode('label')->defaultValue(null)->end()
                                        ->arrayNode('type')
                                            ->beforeNormalization()->ifString()->then(function($v) { return array('name' => $v); })->end()
                                            ->children()
                                                ->scalarNode('name')->cannotBeEmpty()->end()
                                                ->arrayNode('params')->useAttributeAsKey('key')->prototype('variable')->defaultValue(array())->end()
                                            ->end()
                                        ->end()->end()
                                        ->arrayNode('ref')
                                            ->children()
                                                ->scalarNode('class')->isRequired()->end()
                                                ->scalarNode('property')->isRequired()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
