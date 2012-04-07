<?php

/**
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

/**
 * Records filter configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RollerworksRecordFilterExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('record_filter.xml');

        $container->setParameter('rollerworks_record_filter.filters_directory', $config['filters_directory']);
        $container->setParameter('rollerworks_record_filter.filters_namespace', $config['filters_namespace']);

        $factoriesList = array('formatter', 'sqlstruct', 'querybuilder');

        foreach ($factoriesList as $factoryName) {
            $container->setParameter(sprintf('rollerworks_record_filter.%s_factory.auto_generate', $factoryName), $config['generate_'.$factoryName.'s']);
        }
    }
}

