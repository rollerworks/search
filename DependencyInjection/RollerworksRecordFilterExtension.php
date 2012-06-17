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
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

/**
 * RecordFilter configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RollerworksRecordFilterExtension extends Extension
{
    /**
     * {@inheritDoc}
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
    }
}
