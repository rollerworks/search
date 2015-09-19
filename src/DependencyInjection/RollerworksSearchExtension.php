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

use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\ServiceLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class RollerworksSearchExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $config);

        $serviceLoader = new ServiceLoader($container);
        $serviceLoader->loadFile('input_processor');
        $serviceLoader->loadFile('exporter');
        $serviceLoader->loadFile('condition_optimizers');

        $this->mirrorTranslations();

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if (isset($config['metadata'])) {
            $this->registerMetadata($container, $loader, $config['metadata']);
        }

        if (!empty($config['fieldsets'])) {
            $container->setParameter('rollerworks_search.fieldsets_configuration', $config['fieldsets']);
        }
    }

    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    public function getNamespace()
    {
        return 'http://rollerworks.github.io/schema/search/sf-dic/rollerworks-search';
    }

    private function mirrorTranslations()
    {
        $r = new \ReflectionClass('Rollerworks\Component\Search\FieldSet');
        $dir = dirname($r->getFilename()).'/Resources/translations';

        $fs = new Filesystem();
        $fs->mirror($dir, __DIR__.'/../Resources/translations', null, ['copy_on_windows' => true]);
    }

    /**
     * Register the Metadata component.
     *
     * @param ContainerBuilder $container
     * @param XmlFileLoader    $loader
     * @param array            $config
     *
     * @throws \RuntimeException
     */
    private function registerMetadata(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $loader->load('metadata.xml');

        $container->setParameter('rollerworks_search.metadata.directories', $this->getMetadataMappingInformation($config, $container));
        $container->setParameter('rollerworks_search.metadata.cache_directory', $config['cache_dir']);

        $container->setAlias('rollerworks_search.metadata.cache_driver', $config['cache_driver']);
        $container->setAlias('rollerworks_search.metadata.freshness_validator', $config['cache_freshness_validator']);
    }

    /**
     * Returns the processed metadata mapping information.
     *
     * @param array            $config    A configured object manager.
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private function getMetadataMappingInformation(array $config, ContainerBuilder $container)
    {
        $mappingDirectories = [];

        if ($config['auto_mapping']) {
            // automatically register bundle metadata
            foreach ($container->getParameter('kernel.bundles') as $bundleName => $bundleObj) {
                if (!isset($config['mappings'][$bundleName])) {
                    $config['mappings'][$bundleName] = [
                        'mapping' => true,
                        'is_bundle' => true,
                    ];
                }
            }
        }

        foreach ($config['mappings'] as $mappingName => $mappingConfig) {
            if (null !== $mappingConfig && false === $mappingConfig['mapping']) {
                continue;
            }

            $mappingConfig = array_replace([
                'dir' => false,
                'prefix' => false,
            ], (array) $mappingConfig);

            $mappingConfig['dir'] = $container->getParameterBag()->resolveValue($mappingConfig['dir']);
            // a bundle configuration is detected by realizing that the specified dir is not absolute and existing
            if (!isset($mappingConfig['is_bundle'])) {
                $mappingConfig['is_bundle'] = !is_dir($mappingConfig['dir']);
            }

            if ($mappingConfig['is_bundle']) {
                $bundle = null;

                foreach ($container->getParameter('kernel.bundles') as $name => $class) {
                    if ($mappingName === $name) {
                        $bundle = new \ReflectionClass($class);

                        break;
                    }
                }

                if (null === $bundle) {
                    throw new \InvalidArgumentException(sprintf('Bundle "%s" does not exist or it is not enabled.', $mappingName));
                }

                $mappingConfig = $this->getMetadataDriverConfigDefaults($mappingConfig, $bundle);

                if (!$mappingConfig) {
                    continue;
                }

                $container->addResource(new DirectoryResource($mappingConfig['dir']));
            }

            $this->assertValidMappingConfiguration($mappingConfig);
            $mappingDirectories[rtrim($mappingConfig['prefix'], '\\')] = $mappingConfig['dir'];
        }

        return $mappingDirectories;
    }

    /**
     * Assertion if the specified mapping information is valid.
     *
     * @param array $mappingConfig
     *
     * @throws \InvalidArgumentException
     */
    private function assertValidMappingConfiguration(array $mappingConfig)
    {
        if (!$mappingConfig['dir'] || !$mappingConfig['prefix']) {
            throw new \InvalidArgumentException(sprintf('Metadata mapping definitions require at least the "dir" and "prefix" options.'));
        }

        if (!is_dir($mappingConfig['dir'])) {
            throw new \InvalidArgumentException(sprintf('Specified non-existing directory "%s" as Metadata mapping source.', $mappingConfig['dir']));
        }
    }

    /**
     * All the missing information can be autodetected by this method.
     *
     * Returns false when autodetection failed, or an array of the completed information otherwise.
     *
     * @param array            $bundleConfig
     * @param \ReflectionClass $bundle
     *
     * @return array|false
     */
    private function getMetadataDriverConfigDefaults(array $bundleConfig, \ReflectionClass $bundle)
    {
        $bundleDir = dirname($bundle->getFileName());

        if (!$bundleConfig['dir']) {
            $bundleConfig['dir'] = $bundleDir.'/Resources/config/rollerworks_search';
        } else {
            $bundleConfig['dir'] = $bundleDir.'/'.$bundleConfig['dir'];
        }

        if (!is_dir($bundleConfig['dir'])) {
            return false;
        }

        if (!$bundleConfig['prefix']) {
            $bundleConfig['prefix'] = $bundle->getNamespaceName();
        }

        return $bundleConfig;
    }
}
