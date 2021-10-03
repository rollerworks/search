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

use Rollerworks\Component\Search\Field\FieldType;
use Rollerworks\Component\Search\Field\FieldTypeExtension;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FieldSetConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RollerworksSearchExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('input_processor.xml');
        $loader->load('condition_exporter.xml');

        $container->registerForAutoconfiguration(FieldType::class)
            ->addTag('rollerworks_search.type')
        ;
        $container->registerForAutoconfiguration(FieldTypeExtension::class)
            ->addTag('rollerworks_search.type_extension')
        ;
        $container->registerForAutoconfiguration(FieldSetConfigurator::class)
            ->addTag('rollerworks_search.fieldset')
        ;

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if ($this->isConfigEnabled($container, $config['doctrine']['dbal'])) {
            $loader->load('doctrine_dbal.xml');

            if ($this->isConfigEnabled($container, $config['doctrine']['orm'])) {
                $loader->load('doctrine_orm.xml');
                $container->setParameter(
                    'rollerworks_search.doctrine.orm.entity_managers',
                    $config['doctrine']['orm']['entity_managers'] ?: ['default']
                );
            }
        }

        if ($this->isConfigEnabled($container, $config['elasticsearch'])) {
            $loader->load('elasticsearch.xml');
        }

        if (interface_exists(ValidatorInterface::class)) {
            $loader->load('input_validator.xml');
        }

        if (class_exists(Translator::class)) {
            $loader->load('translator_alias_resolver.xml');
        }

        if ($this->isConfigEnabled($container, $config['api_platform'])) {
            $loader->load('api_platform.xml');

            if ($this->isConfigEnabled($container, $config['api_platform']['doctrine_orm'])) {
                $loader->load('api_platform_doctrine_orm.xml');
            }

            if ($this->isConfigEnabled($container, $config['api_platform']['elasticsearch'])) {
                if (! $this->isConfigEnabled($container, $config['elasticsearch'])) {
                    throw new LogicException('API Platform Elasticsearch support cannot be enabled as Elasticsearch is not enabled');
                }
                $loader->load('api_platform_elasticsearch.xml');
            }
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'cache' => [
                'pools' => [
                    'rollerworks.search_processor.cache' => [
                        'adapter' => 'cache.adapter.psr6',
                        'provider' => 'rollerworks_search.cache.adapter.array',
                    ],
                ],
            ],
        ]);

        if (class_exists(Translator::class)) {
            $container->prependExtensionConfig('framework', [
                'translator' => [
                    'paths' => [
                        \dirname((new \ReflectionClass(FieldSet::class))->getFileName()) . '/Resources/translations',
                    ],
                ],
            ]);
        }
    }
}
