<?php

/**
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection;

use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\Factory\FieldSetFactory;
use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\ServiceLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\FileLocator;

/**
 * RollerworksSearchExtension.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RollerworksSearchExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $config);

        $serviceLoader = new ServiceLoader($container);
        $serviceLoader->loadFile('services');
        $serviceLoader->loadFile('type');
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
            $this->registerFieldSets($container, $config['fieldsets']);
        }

        if (isset($config['doctrine']['orm'])) {
            $this->registerDoctrineOrm($container, $loader, $config['doctrine']['orm']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return 'http://rollerworks.github.io/schema/dic/rollerworks-search';
    }

    private function mirrorTranslations()
    {
        $r = new \ReflectionClass('Rollerworks\Component\Search\FieldSet');
        $dir = dirname($r->getFilename()).'/Resources/translations';

        $fs = new Filesystem();
        $fs->mirror($dir, __DIR__.'/../Resources/translations', null, array('copy_on_windows' => true));
    }

    /**
     * Register the FieldSets as services.
     *
     * @param ContainerBuilder $container
     * @param array            $fieldSets
     */
    private function registerFieldSets(ContainerBuilder $container, array $fieldSets)
    {
        $factory = new FieldSetFactory(
            $container,
            $container->get('rollerworks_search.metadata_factory', ContainerBuilder::NULL_ON_INVALID_REFERENCE)
        );

        foreach ($fieldSets as $name => $fieldSetConfig) {
            $fieldSet = $factory->createFieldSetBuilder($name);

            foreach ($fieldSetConfig['imports'] as $import) {
                $fieldSet->importFromClass(
                    $import['class'],
                    $import['include_fields'],
                    $import['exclude_fields']
                );

                $r = new \ReflectionClass($import['class']);
                $container->addResource(new FileResource($r->getFileName()));
            }

            foreach ($fieldSetConfig['fields'] as $fieldName => $field) {
                $fieldSet->set(
                    $fieldName,
                    $field['type'],
                    $field['options'],
                    $field['required'],
                    $field['model_class'],
                    $field['model_property']
                );
            }

            $factory->register($fieldSet->getFieldSet());
        }
    }

    /**
     * Register the Doctrine ORM component.
     *
     * @param ContainerBuilder $container
     * @param XmlFileLoader    $loader
     * @param array            $config
     */
    private function registerDoctrineOrm(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        $loader->load('orm.xml');

        if (empty($config['entity_managers'])) {
            $config['entity_managers'] = array('default');
        }

        $container->setAlias('rollerworks_search.doctrine_orm.cache_driver', $config['cache_driver']);
        $container->setParameter('rollerworks_search.doctrine_orm.entity_managers', $config['entity_managers']);
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
        if (!class_exists('Metadata\MetadataFactory')) {
            throw new \RuntimeException('Unable to load Metadata, the "jms/metadata" package is not (properly) installed.');
        }

        $loader->load('metadata.xml');

        if ('rollerworks_search.metadata.cache_driver.file' === $config['cache_driver']) {
            $cacheDirectory = $container->getParameterBag()->resolveValue($config['cache_dir']);
            if (!is_dir($cacheDirectory)) {
                mkdir($cacheDirectory, 0777, true);
            }

            $container->findDefinition('rollerworks_search.metadata.cache_driver.file')->replaceArgument(0, $cacheDirectory);
        } elseif (null === $config['cache_driver']) {
            $container->findDefinition('rollerworks_search.metadata.metadata_reader')->removeMethodCall('setCache');
        } else {
            $container->setAlias('rollerworks_search.metadata.cache_driver', $config['cache_driver']);
        }

        $container->setParameter('rollerworks_search.metadata.directories', $this->getMetadataMappingInformation($config, $container));
    }

    /**
     * Returns the processed metadata mapping information.
     *
     * @param array            $config    A configured object manager.
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function getMetadataMappingInformation(array $config, ContainerBuilder $container)
    {
        $mappingDirectories = array();

        if ($config['auto_mapping']) {
            // automatically register bundle metadata
            foreach (array_keys($container->getParameter('kernel.bundles')) as $bundle) {
                if (!isset($config['mappings'][$bundle])) {
                    $config['mappings'][$bundle] = array(
                        'mapping' => true,
                        'is_bundle' => true,
                    );
                }
            }
        }

        foreach ($config['mappings'] as $mappingName => $mappingConfig) {
            if (null !== $mappingConfig && false === $mappingConfig['mapping']) {
                continue;
            }

            $mappingConfig = array_replace(array(
                'dir' => false,
                'prefix' => false,
            ), (array) $mappingConfig);

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

                $mappingConfig = $this->getMetadataDriverConfigDefaults($mappingConfig, $bundle, $container);
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
        $bundleDir = dirname($bundle->getFilename());

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
