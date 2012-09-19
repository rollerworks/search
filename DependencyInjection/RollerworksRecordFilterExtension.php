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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

use Rollerworks\Bundle\RecordFilterBundle\FieldSet;

/**
 * RecordFilter configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RollerworksRecordFilterExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $config);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('record_filter.xml');

        $cacheDirectory = $container->getParameterBag()->resolveValue($config['metadata_cache']);

        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }

        // the cache directory should be the first argument of the cache service
        $container->getDefinition('rollerworks_record_filter.metadata.cache')->replaceArgument(0, $cacheDirectory);

        $container->setParameter('rollerworks_record_filter.filters_directory', $config['filters_directory']);
        $container->setParameter('rollerworks_record_filter.filters_namespace', $config['filters_namespace']);

        $container->getDefinition('rollerworks_record_filter.doctrine.sql.where_builder')
            ->addMethodCall('setEntityManager', array(new Reference(sprintf('doctrine.orm.%s_entity_manager', $container->getParameterBag()->resolveValue($config['doctrine']['sql']['default_entity_manager'])))));

        $container->setParameter('rollerworks_record_filter.factories.fieldset.auto_generate', $config['factories']['fieldset']['auto_generate']);
        $container->setParameter('rollerworks_record_filter.factories.fieldset.namespace', $config['factories']['fieldset']['namespace']);
        $container->setParameter('rollerworks_record_filter.factories.fieldset.label_translator_prefix', $config['factories']['fieldset']['label_translator_prefix']);
        $container->setParameter('rollerworks_record_filter.factories.fieldset.label_translator_domain', $config['factories']['fieldset']['label_translator_domain']);

        $container->setParameter('rollerworks_record_filter.factories.doctrine.sql.wherebuilder.auto_generate', $config['factories']['doctrine']['sql']['wherebuilder']['auto_generate']);
        $container->setParameter('rollerworks_record_filter.factories.doctrine.sql.wherebuilder.namespace', $config['factories']['doctrine']['sql']['wherebuilder']['namespace']);

        $container->setParameter('rollerworks_record_filter.fieldsets', serialize($config['fieldsets']));

        $container->getDefinition('rollerworks_record_filter.doctrine.sql.wherebuilder_factory')
            ->addMethodCall('setEntityManager', array(new Reference(sprintf('doctrine.orm.%s_entity_manager', $container->getParameterBag()->resolveValue($config['factories']['doctrine']['sql']['wherebuilder']['default_entity_manager'])))));
    }
}
