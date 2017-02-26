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

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Processor\CachedSearchProcessor;
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
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('type.xml');
        $loader->load('input_processor.xml');
        $loader->load('condition_exporter.xml');
        $loader->load('condition_optimizers.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if ($this->isConfigEnabled($container, $config['processor'])) {
            $loader->load('search_processor.xml');

            $this->configureProcessor($container, $config);
        }

        if (interface_exists(ValidatorInterface::class)) {
            $loader->load('input_validator.xml');
        }

        if (class_exists(Translator::class)) {
            $loader->load('translator_alias_resolver.xml');
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function prepend(ContainerBuilder $container)
    {
        if (class_exists(CachedSearchProcessor::class)) {
            $container->prependExtensionConfig('framework', [
                'cache' => [
                    'pools' => [
                        'rollerworks.search_processor.cache' => [
                            'adapter' => 'cache.system',
                        ],
                    ],
                ],
            ]);
        }

        if (class_exists(Translator::class)) {
            $container->prependExtensionConfig('framework', [
                'translator' => [
                    'paths' => [
                        dirname((new \ReflectionClass(FieldSet::class))->getFilename()).'/Resources/translations',
                    ],
                ],
            ]);
        }
    }

    private function configureProcessor(ContainerBuilder $container, array $config)
    {
        if (!interface_exists(\Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface::class)) {
            throw new LogicException('SearchProcessor support cannot be enabled as the Symfony PsrHttpMessage Bridge is not installed.');
        }

        if (!class_exists(\Zend\Diactoros\ServerRequest::class)) {
            throw new LogicException('SearchProcessor support cannot be enabled as the Zend Diactoros component is not installed.');
        }

        if ($config['processor']['disable_cache']) {
            $container->setAlias('rollerworks_search.default_search_processor', 'rollerworks_search.psr7_search_processor');
        } else {
            $container->setAlias('rollerworks_search.default_search_processor', 'rollerworks_search.cached_search_processor');
        }
    }
}
