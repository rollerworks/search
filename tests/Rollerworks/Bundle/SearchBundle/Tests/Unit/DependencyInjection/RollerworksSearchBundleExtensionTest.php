<?php

/*
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Unit\DependencyInjection;

use Doctrine\Common\Cache\ArrayCache;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\UserBundle;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\RollerworksSearchExtension;
use Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\InvoiceBundle;
use Symfony\Component\DependencyInjection\Definition;

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

        $fieldSetDef = new Definition('Rollerworks\Component\Search\FieldSet');
        $fieldSetDef->addArgument('customer');

        $fieldDef = new Definition();
        $fieldDef->setFactoryService('rollerworks_search.factory');
        $fieldDef->setFactoryMethod('createField');
        $fieldDef->addArgument('integer');
        $fieldDef->addArgument(array('active' => true));
        $fieldDef->addArgument(true);
        $fieldSetDef->addMethodCall('set', array('id', $fieldDef));

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

        $fieldSetDef = new Definition('Rollerworks\Component\Search\FieldSet');
        $fieldSetDef->addArgument('customer');

        $fieldDef = new Definition();
        $fieldDef->setFactoryService('rollerworks_search.factory');
        $fieldDef->setFactoryMethod('createFieldForProperty');
        $fieldDef->addArgument('Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\Model\Customer');
        $fieldDef->addArgument('id');
        $fieldDef->addArgument('customer_type');
        $fieldDef->addArgument(array());
        $fieldDef->addArgument(false);
        $fieldSetDef->addMethodCall('set', array('customer_id', $fieldDef));

        $fieldDef = new Definition();
        $fieldDef->setFactoryService('rollerworks_search.factory');
        $fieldDef->setFactoryMethod('createFieldForProperty');
        $fieldDef->addArgument('Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User');
        $fieldDef->addArgument('id');
        $fieldDef->addArgument('user_type');
        $fieldDef->addArgument(array());
        $fieldDef->addArgument(false);
        $fieldSetDef->addMethodCall('set', array('user_id', $fieldDef));

        $fieldDef = new Definition();
        $fieldDef->setFactoryService('rollerworks_search.factory');
        $fieldDef->setFactoryMethod('createField');
        $fieldDef->addArgument('integer');
        $fieldDef->addArgument(array('active' => true));
        $fieldDef->addArgument(true);
        $fieldSetDef->addMethodCall('set', array('id', $fieldDef));

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

        $fieldSetDef = new Definition('Rollerworks\Component\Search\FieldSet');
        $fieldSetDef->addArgument('customer');

        $fieldDef = new Definition();
        $fieldDef->setFactoryService('rollerworks_search.factory');
        $fieldDef->setFactoryMethod('createFieldForProperty');
        $fieldDef->addArgument('Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\UserBundle\Model\User');
        $fieldDef->addArgument('id');
        $fieldDef->addArgument('integer');
        $fieldDef->addArgument(array());
        $fieldDef->addArgument(false);
        $fieldSetDef->addMethodCall('set', array('user_id', $fieldDef));

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

        $fieldSetDef = new Definition('Rollerworks\Component\Search\FieldSet');
        $fieldSetDef->addArgument('customer');

        $fieldDef = new Definition();
        $fieldDef->setFactoryService('rollerworks_search.factory');
        $fieldDef->setFactoryMethod('createField');
        $fieldDef->addArgument('integer');
        $fieldDef->addArgument(array('active' => true));
        $fieldDef->addArgument(true);
        $fieldSetDef->addMethodCall('set', array('user_id', $fieldDef));

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

        $this->assertContainerBuilderHasService('rollerworks_search.doctrine_orm.cache_driver', 'Doctrine\Common\Cache\ArrayCache');

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
}
