<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Unit\DependencyInjection;

use Doctrine\Common\Cache\ArrayCache;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\RollerworksSearchExtension;
use Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\InvoiceBundle;
use Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\UserBundle;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RollerworksSearchBundleExtensionTest extends AbstractExtensionTestCase
{
    public function testSearchFactoryIsAccessible()
    {
        $this->load();
        $this->compile();

        $this->container->get('rollerworks_search.factory');
    }

    public function testFieldsSetsGetRegistered()
    {
        $config = [
            'fieldsets' => [
                'invoice' => [
                    'fields' => [
                        'id' => ['type' => 'integer'],
                    ],
                ],
                'customer' => [
                    'fields' => [
                        'id' => ['type' => 'integer', 'required' => true, 'options' => ['active' => true]],
                    ],
                ],
            ],
        ];

        $this->load($config);
        $this->compile();

        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.invoice', 'Rollerworks\Component\Search\FieldSet');
        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.customer', 'Rollerworks\Component\Search\FieldSet');

        $fieldSetDef = $this->createFieldSet('customer');
        $this->addField($fieldSetDef, 'id', 'integer', ['active' => true], true);

        $customerDef = $this->container->findDefinition('rollerworks_search.fieldset.customer');
        $this->assertEquals($fieldSetDef, $customerDef);
    }

    public function testFieldsSetsGetRegisteredFromMetadata()
    {
        $config = [
            'metadata' => [
                'cache_driver' => null,
            ],
            'fieldsets' => [
                'customer' => [
                    'fields' => [
                        'id' => ['type' => 'integer', 'required' => true, 'options' => ['active' => true]],
                    ],
                    'import' => [
                        'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\Model\Customer',
                        [
                            'class' => 'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User',
                            'include_fields' => ['user_id'],
                        ],
                    ],
                ],
            ],
        ];

        $bundles = [
            'UserBundle' => new UserBundle(),
            'InvoiceBundle' => new InvoiceBundle(),
        ];

        $this->container->setParameter('kernel.bundles', $bundles);
        $this->container->set('annotation_reader', $this->createAnnotationReader());

        $this->load($config);
        $this->compile();

        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.customer', 'Rollerworks\Component\Search\FieldSet');

        $fieldSetDef = $this->createFieldSet('customer');
        $this->addField($fieldSetDef, 'customer_id', 'customer_type', [], false, ['Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\Model\Customer', 'id']);
        $this->addField($fieldSetDef, 'user_id', 'user_type', [], false, ['Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User', 'id']);
        $this->addField($fieldSetDef, 'id', 'integer', ['active' => true], true);

        $customerDef = $this->container->findDefinition('rollerworks_search.fieldset.customer');
        $this->assertEquals($fieldSetDef, $customerDef);
    }

    public function testFieldsSetsGetRegisteredFromMetadataExplicit()
    {
        $config = [
            'metadata' => [
                'auto_mapping' => false,
                'mappings' => [
                    'UserBundle' => [
                        'is_bundle' => true,
                        'dir' => 'Resources/config/search',
                        'prefix' => 'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model',
                    ],
                ],
                'cache_driver' => null,
            ],
            'fieldsets' => [
                'customer' => [
                    'import' => [
                        [
                            'class' => 'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User',
                            'include_fields' => ['user_id'],
                        ],
                    ],
                ],
            ],
        ];

        $bundles = [
            'UserBundle' => new UserBundle(),
            'InvoiceBundle' => new InvoiceBundle(),
        ];

        $this->container->setParameter('kernel.bundles', $bundles);
        $this->container->set('annotation_reader', $this->createAnnotationReader());

        $this->load($config);
        $this->compile();

        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.customer', 'Rollerworks\Component\Search\FieldSet');

        $fieldSetDef = $this->createFieldSet('customer');
        $this->addField($fieldSetDef, 'user_id', 'integer', [], false, ['Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User', 'id']);

        $customerDef = $this->container->findDefinition('rollerworks_search.fieldset.customer');
        $this->assertEquals($fieldSetDef, $customerDef);
    }

    public function testFieldsSetsGetRegisteredFromMetadataWithOverwrites()
    {
        $config = [
            'metadata' => [
                'cache_driver' => null,
            ],
            'fieldsets' => [
                'customer' => [
                    'fields' => [
                        'user_id' => ['type' => 'integer', 'required' => true, 'options' => ['active' => true]],
                    ],
                    'import' => [
                        [
                            'class' => 'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User',
                            'include_fields' => ['user_id'],
                        ],
                    ],
                ],
            ],
        ];

        $bundles = [
            'UserBundle' => new UserBundle(),
            'InvoiceBundle' => new InvoiceBundle(),
        ];

        $this->container->setParameter('kernel.bundles', $bundles);
        $this->container->set('annotation_reader', $this->createAnnotationReader());

        $this->load($config);
        $this->compile();

        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.customer', 'Rollerworks\Component\Search\FieldSet');

        $fieldSetDef = $this->createFieldSet('customer');
        $this->addField($fieldSetDef, 'user_id', 'integer', ['active' => true], true);

        $customerDef = $this->container->findDefinition('rollerworks_search.fieldset.customer');
        $this->assertEquals($fieldSetDef, $customerDef);
    }

    protected function getContainerExtensions()
    {
        return [
            new RollerworksSearchExtension(),
        ];
    }

    /**
     * @return \Doctrine\Common\Annotations\Reader
     */
    private function createAnnotationReader()
    {
        if (version_compare(\Doctrine\Common\Version::VERSION, '3.0.0', '>=')) {
            $reader = new \Doctrine\Common\Annotations\CachedReader(
                new \Doctrine\Common\Annotations\AnnotationReader(),
                new ArrayCache()
            );
        } else {
            // 2.3 is the minimum required version so no need to further check
            $reader = new \Doctrine\Common\Annotations\AnnotationReader();
            $reader = new \Doctrine\Common\Annotations\CachedReader($reader, new ArrayCache());
        }

        return $reader;
    }

    /**
     * @param string $name
     *
     * @return Definition
     */
    private function createFieldSet($name)
    {
        $fieldSetDef = new Definition('Rollerworks\Component\Search\FieldSet');
        $fieldSetDef->addTag('rollerworks_search.fieldset', ['name' => $name]);
        $fieldSetDef->addArgument($name);

        return $fieldSetDef;
    }

    private function addField(Definition $fieldSetDef, $name, $type, array $options = [], $required = false, array $property = [])
    {
        $fieldDef = new Definition();

        if ($property) {
            $fieldDef->addArgument($property[0]);
            $fieldDef->addArgument($property[1]);

            $this->setFactory($fieldDef, 'rollerworks_search.factory', 'createFieldForProperty');
        } else {
            $this->setFactory($fieldDef, 'rollerworks_search.factory', 'createField');
        }

        $fieldDef->addArgument($name);
        $fieldDef->addArgument($type);
        $fieldDef->addArgument($options);
        $fieldDef->addArgument($required);

        $fieldSetDef->addMethodCall('set', [$name, $fieldDef]);
    }

    private function setFactory(Definition $definition, $serviceId, $method)
    {
        if (method_exists($definition, 'setFactory')) {
            $definition->setFactory([new Reference($serviceId), $method]);
        } else {
            $definition->setFactoryService($serviceId);
            $definition->setFactoryMethod($method);
        }
    }
}
