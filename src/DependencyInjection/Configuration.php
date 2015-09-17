<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rollerworks_search');

        $this->addMetadataSection($rootNode);
        $this->addFieldSetsSection($rootNode);

        return $treeBuilder;
    }

    private function addMetadataSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('metadata')
                    ->children()
                        ->scalarNode('cache_driver')
                            ->cannotBeEmpty()
                            ->defaultValue('rollerworks_search.metadata.cache_driver.file')
                        ->end()
                        ->scalarNode('cache_freshness_validator')
                            ->cannotBeEmpty()
                            ->defaultValue('rollerworks_search.metadata.freshness_validator.file_tracking')
                        ->end()
                        ->scalarNode('cache_dir')
                            ->cannotBeEmpty()
                            ->defaultValue('%kernel.cache_dir%/rollerworks_search_metadata')
                        ->end()
                        ->booleanNode('auto_mapping')->defaultTrue()->end()
                    ->end()
                    ->fixXmlConfig('mapping')
                    ->children()
                        ->arrayNode('mappings')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($v) { return ['type' => $v]; })
                                ->end()
                                ->treatNullLike([])
                                ->treatFalseLike(['mapping' => false])
                                ->performNoDeepMerging()
                                ->children()
                                    ->scalarNode('mapping')->defaultValue(true)->end()
                                    ->scalarNode('dir')->end()
                                    ->scalarNode('prefix')->end()
                                    ->booleanNode('is_bundle')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addFieldSetsSection(ArrayNodeDefinition $rootNode)
    {
        $optionsNormalizer = function ($v) use (&$optionsNormalizer) {
            $options = [];

            foreach ($v as $option) {
                if (!isset($option['type'])) {
                    $option['type'] = 'string';
                }

                if ('collection' === $option['type']) {
                    $optionValue = $option['option'];

                    // Wrap inside array to preserve option-keys
                    // Even if the option-key is 0 its only kept inside 'key'
                    if (!isset($optionValue[0])) {
                        $optionValue = [$optionValue];
                    }

                    $optionValue = $optionsNormalizer($optionValue);
                } else {
                    $optionValue = $option['value'];
                }

                $options[$option['key']] = $optionValue;
            }

            return $options;
        };

        $rootNode
            ->fixXmlConfig('fieldset')
            ->children()
                ->arrayNode('fieldsets')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->fixXmlConfig('import')
                        ->children()
                            ->arrayNode('imports')
                                ->performNoDeepMerging()
                                ->prototype('array')
                                    ->beforeNormalization()->ifString()->then(function ($v) { return ['class' => $v]; })->end()
                                    ->children()
                                        ->scalarNode('class')->isRequired()->end()
                                    ->end()
                                    ->fixXmlConfig('include_field')
                                    ->children()
                                        ->arrayNode('include_fields')->prototype('scalar')->defaultValue([])->end()->end()
                                    ->end()
                                    ->fixXmlConfig('exclude_field')
                                    ->children()
                                        ->arrayNode('exclude_fields')->prototype('scalar')->defaultValue([])->end()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->fixXmlConfig('field')
                        ->children()
                            ->arrayNode('fields')
                                ->performNoDeepMerging()
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->validate()
                                        ->ifTrue(function ($v) { return empty($v['model_class']) xor empty($v['model_property']); })
                                        ->thenInvalid('When setting the model reference, both "model_class" and "model_property" must have a value.')
                                    ->end()
                                    ->children()
                                        ->scalarNode('type')->isRequired()->end()
                                        ->booleanNode('required')->defaultFalse()->end()
                                        ->scalarNode('model_class')->defaultValue(null)->end()
                                        ->scalarNode('model_property')->defaultValue(null)->end()
                                    ->end()
                                    ->fixXmlConfig('option')
                                    ->children()
                                        ->arrayNode('options')
                                            ->beforeNormalization()->ifTrue(function ($v) { return isset($v[0]); })->then($optionsNormalizer)->end()
                                            ->prototype('variable')->end()
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
