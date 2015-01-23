<?php

/**
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Unit\DependencyInjection;

use Doctrine\Common\Cache\ArrayCache;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\UserBundle;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\RollerworksSearchExtension;
use Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\InvoiceBundle;
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
        $config = array(
            'fieldsets' => array(
                'invoice' => array(
                    'fields' => array(
                        'id' => array('type' => 'integer')
                    )
                ),
                'customer' => array(
                    'fields' => array(
                        'id' => array('type' => 'integer', 'required' => true, 'options' => array('active' => true))
                    )
                )
            )
        );

        $this->load($config);
        $this->compile();

        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.invoice', 'Rollerworks\Component\Search\FieldSet');
        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.customer', 'Rollerworks\Component\Search\FieldSet');

        $fieldSetDef = $this->createFieldSet('customer');
        $this->addField($fieldSetDef, 'id', 'integer', array('active' => true), true);

        $customerDef = $this->container->findDefinition('rollerworks_search.fieldset.customer');
        $this->assertEquals($fieldSetDef, $customerDef);
    }

    public function testFieldsSetsGetRegisteredFromMetadata()
    {
        $config = array(
            'metadata' => array(
                'cache_driver' => null,
            ),
            'fieldsets' => array(
                'customer' => array(
                    'fields' => array(
                        'id' => array('type' => 'integer', 'required' => true, 'options' => array('active' => true))
                    ),
                    'import' => array(
                        'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\Model\Customer',
                        array(
                            'class' => 'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User',
                            'include_fields' => array('user_id'),
                        ),
                    )
                )
            )
        );

        $bundles = array(
            'UserBundle' => new UserBundle(),
            'InvoiceBundle' => new InvoiceBundle(),
        );

        $this->container->setParameter('kernel.bundles', $bundles);
        $this->container->set('annotation_reader', $this->createAnnotationReader());

        $this->load($config);
        $this->compile();

        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.customer', 'Rollerworks\Component\Search\FieldSet');

        $fieldSetDef = $this->createFieldSet('customer');
        $this->addField($fieldSetDef, 'customer_id', 'customer_type', array(), false, array('Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\Model\Customer', 'id'));
        $this->addField($fieldSetDef, 'user_id', 'user_type', array(), false, array('Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User', 'id'));
        $this->addField($fieldSetDef, 'id', 'integer', array('active' => true), true);

        $customerDef = $this->container->findDefinition('rollerworks_search.fieldset.customer');
        $this->assertEquals($fieldSetDef, $customerDef);
    }

    public function testFieldsSetsGetRegisteredFromMetadataExplicit()
    {
        $config = array(
            'metadata' => array(
                'auto_mapping' => false,
                'mappings' => array(
                    'UserBundle' => array(
                        'is_bundle' => true,
                        'dir' => 'Resources/config/search',
                        'prefix' => 'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model',
                    )
                ),
                'cache_driver' => null,
            ),
            'fieldsets' => array(
                'customer' => array(
                    'import' => array(
                        array(
                            'class' => 'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User',
                            'include_fields' => array('user_id'),
                        ),
                    )
                )
            )
        );

        $bundles = array(
            'UserBundle' => new UserBundle(),
            'InvoiceBundle' => new InvoiceBundle(),
        );

        $this->container->setParameter('kernel.bundles', $bundles);
        $this->container->set('annotation_reader', $this->createAnnotationReader());

        $this->load($config);
        $this->compile();

        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.customer', 'Rollerworks\Component\Search\FieldSet');

        $fieldSetDef = $this->createFieldSet('customer');
        $this->addField($fieldSetDef, 'user_id', 'integer', array(), false, array('Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User', 'id'));

        $customerDef = $this->container->findDefinition('rollerworks_search.fieldset.customer');
        $this->assertEquals($fieldSetDef, $customerDef);
    }

    public function testFieldsSetsGetRegisteredFromMetadataWithOverwrites()
    {
        $config = array(
            'metadata' => array(
                'cache_driver' => null,
            ),
            'fieldsets' => array(
                'customer' => array(
                    'fields' => array(
                        'user_id' => array('type' => 'integer', 'required' => true, 'options' => array('active' => true))
                    ),
                    'import' => array(
                        array(
                            'class' => 'Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User',
                            'include_fields' => array('user_id'),
                        ),
                    )
                )
            )
        );

        $bundles = array(
            'UserBundle' => new UserBundle(),
            'InvoiceBundle' => new InvoiceBundle(),
        );

        $this->container->setParameter('kernel.bundles', $bundles);
        $this->container->set('annotation_reader', $this->createAnnotationReader());

        $this->load($config);
        $this->compile();

        $this->assertContainerBuilderHasService('rollerworks_search.fieldset.customer', 'Rollerworks\Component\Search\FieldSet');

        $fieldSetDef = $this->createFieldSet('customer');
        $this->addField($fieldSetDef, 'user_id', 'integer', array('active' => true), true);

        $customerDef = $this->container->findDefinition('rollerworks_search.fieldset.customer');
        $this->assertEquals($fieldSetDef, $customerDef);
    }

    public function testDoctrineOrmEnabledWithDefaults()
    {
        $config = array(
            'doctrine' => array(
                'orm' => array()
            )
        );

        $this->load($config);
        $this->compile();

        $this->assertEquals(array('default'), $this->container->getParameter('rollerworks_search.doctrine_orm.entity_managers'));

        $this->assertContainerBuilderHasService('rollerworks_search.doctrine_orm.factory', 'Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory');
        $this->assertContainerBuilderHasService('rollerworks_search.type_extension.doctrine', 'Rollerworks\Component\Search\Extension\Doctrine\Orm\DoctrineOrmExtension');
    }

    public function testDoctrineOrmEnabledWithCustom()
    {
        $config = array(
            'doctrine' => array(
                'orm' => array(
                    'entity_managers' => array(
                        'default',
                        'secure',
                    ),
                    'cache_driver' => 'acme_test.orm.cache_driver',
                )
            )
        );

        $this->container->setDefinition('acme_test.orm.cache_driver', new Definition('Doctrine\Common\Cache\PhpFileCache'));

        $this->load($config);
        $this->compile();

        $this->assertEquals(array('default', 'secure'), $this->container->getParameter('rollerworks_search.doctrine_orm.entity_managers'));
        $this->assertContainerBuilderHasService('rollerworks_search.doctrine_orm.cache_driver', 'Doctrine\Common\Cache\PhpFileCache');
    }

    protected function getContainerExtensions()
    {
        return array(
            new RollerworksSearchExtension(),
        );
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
            // 2.3 is the minimum required version so need to further check
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
        $fieldSetDef->addTag('rollerworks_search.fieldset', array('name' => $name));
        $fieldSetDef->addArgument($name);

        return $fieldSetDef;
    }

    private function addField(Definition $fieldSetDef, $name, $type, array $options = array(), $required = false, array $property = array())
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

        $fieldSetDef->addMethodCall('set', array($name, $fieldDef));
    }

    private function setFactory(Definition $definition, $serviceId, $method)
    {
        if (method_exists($definition, 'setFactory')) {
            $definition->setFactory(array(new Reference($serviceId), $method));
        } else {
            $definition->setFactoryService($serviceId);
            $definition->setFactoryMethod($method);
        }
    }
}
