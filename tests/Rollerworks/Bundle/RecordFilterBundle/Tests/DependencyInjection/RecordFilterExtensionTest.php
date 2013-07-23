<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\Reference;
use Rollerworks\Bundle\RecordFilterBundle\DependencyInjection\RollerworksRecordFilterExtension;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;

class RecordFilterExtensionTest extends TestCase
{
    public function testLoadDefaultConfiguration()
    {
        $container = $this->createContainer();
        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array());

        $container->setParameter('doctrine.default_entity_manager', 'default');
        $this->compileContainer($container);

        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.filters_namespace'));
        $this->assertEquals($container->getParameter('kernel.cache_dir') . '/record_filter', $container->getParameter('rollerworks_record_filter.filters_directory'));

        $this->assertFalse($container->getParameter('rollerworks_record_filter.factories.fieldset.auto_generate'));
        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.factories.fieldset.namespace'));
        $this->assertEquals('', $container->getParameter('rollerworks_record_filter.factories.fieldset.label_translator_prefix'));
        $this->assertEquals('filters', $container->getParameter('rollerworks_record_filter.factories.fieldset.label_translator_domain'));

        $this->assertFalse($container->hasParameter('rollerworks_record_filter.factories.doctrine.orm.wherebuilder.auto_generate'));
    }

    public function testLoadFormatter()
    {
        $container = $this->createContainer();
        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array());
        $this->compileContainer($container);

        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.filters_namespace'));
        $this->assertEquals($container->getParameter('kernel.cache_dir') . '/record_filter', $container->getParameter('rollerworks_record_filter.filters_directory'));

        $this->assertFalse($container->getParameter('rollerworks_record_filter.factories.fieldset.auto_generate'));
        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.factories.fieldset.namespace'));
        $this->assertEquals('', $container->getParameter('rollerworks_record_filter.factories.fieldset.label_translator_prefix'));
        $this->assertEquals('filters', $container->getParameter('rollerworks_record_filter.factories.fieldset.label_translator_domain'));
        $this->assertFalse($container->hasParameter('rollerworks_record_filter.factories.doctrine.orm.wherebuilder.auto_generate'));

        $this->assertEquals('rollerworks_record_filter.cache_array_driver', $container->getParameter('rollerworks_record_filter.formatter.cache_driver'));
        $this->assertEquals(0, $container->getParameter('rollerworks_record_filter.formatter.cache_lifetime'));
    }

    public function testLoadConfigurationDoctrineOrm()
    {
        $container = $this->createContainer();
        $container->setParameter('doctrine.default_entity_manager', 'default');
        $container->setParameter('doctrine.second_entity_manager', 'default');

        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array(
            'doctrine' => array('orm' => array('default_entity_manager' => 'second')),
            'factories' => array('doctrine' => array('orm' => array()))
        ));

        $this->compileContainer($container);

        $this->assertEquals('rollerworks_record_filter.cache_array_driver', $container->getParameter('rollerworks_record_filter.doctrine.orm.cache_driver'));
        $this->assertEquals(0, $container->getParameter('rollerworks_record_filter.doctrine.orm.cache_lifetime'));

        $factoryDef = $container->getDefinition('rollerworks_record_filter.doctrine.orm.wherebuilder_factory');
        $calls = $factoryDef->getMethodCalls();

        $found = false;
        foreach ($calls as $call) {
            if ('setEntityManager' === $call[0]) {
                $this->assertEquals($call[1], array(new Reference('doctrine.orm.default_entity_manager')));

                $found = true;
            }
        }

        if (!$found) {
            $this->fail('setEntityManager() is not found.');
        }
    }

    /**
     * @dataProvider provideFactories
     *
     * @param array $config
     * @param array $expected
     */
    public function testLoadConfigurationFactories($config, $expected)
    {
        $container = $this->createContainer();
        $container->setParameter('doctrine.default_entity_manager', 'default');
        $container->setParameter('doctrine.second_entity_manager', 'default');

        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array('doctrine' => array('orm' => array()), 'factories' => $config));
        $this->compileContainer($container);

        foreach ($expected as $name => $value) {
            $this->assertEquals($value, $container->getParameter('rollerworks_record_filter.factories.' . $name));
        }

        if (isset($config['doctrine']['orm']['wherebuilder']['default_entity_manager'])) {
            $factoryDef = $container->getDefinition('rollerworks_record_filter.doctrine.orm.wherebuilder_factory');
            $calls = $factoryDef->getMethodCalls();

            $found = false;
            foreach ($calls as $call) {
                if ('setEntityManager' === $call[0]) {
                    $this->assertEquals($call[1], array(new Reference(sprintf('doctrine.orm.%s_entity_manager', $config['doctrine']['orm']['wherebuilder']['default_entity_manager']))));

                    $found = true;
                }
            }

            if (!$found) {
                $this->fail('setEntityManager() is not found.');
            }
        }
    }

    public static function provideFactories()
    {
        return array(
            array(
                array('fieldset' => array(
                    'namespace' => 'MyApp\\RecordFilter',
                    'label_translator_prefix' => 'my_app',
                    'label_translator_domain' => 'search',
                    'auto_generate' => true
                )),

                array(
                    'fieldset.namespace' => 'MyApp\\RecordFilter',
                    'fieldset.label_translator_prefix' => 'my_app',
                    'fieldset.label_translator_domain' => 'search',
                    'fieldset.auto_generate' => true
                )
            ),

            array(
                array('doctrine' => array(
                    'orm' => array(
                        'wherebuilder' => array(
                            'namespace' => 'MyApp\\RecordFilter',
                            'default_entity_manager' => 'second',
                            'auto_generate' => true
                        )
                    )
                )),

                array(
                    'doctrine.orm.wherebuilder.namespace' => 'MyApp\\RecordFilter',
                    'doctrine.orm.wherebuilder.auto_generate' => true
                )
            ),

            array(
                array('doctrine' => array(
                    'orm' => array(
                        'wherebuilder' => array(
                            'namespace' => 'MyApp\\RecordFilter',
                            'default_entity_manager' => 'second',
                            'auto_generate' => true
                        )
                    )
                ),
                'fieldset' => array(
                    'namespace' => 'MyApp\\RecordFilter',
                    'label_translator_prefix' => 'my_app',
                    'label_translator_domain' => 'search',
                    'auto_generate' => false
                )),

                array(
                    'doctrine.orm.wherebuilder.namespace' => 'MyApp\\RecordFilter',
                    'doctrine.orm.wherebuilder.auto_generate' => true,

                    'fieldset.namespace' => 'MyApp\\RecordFilter',
                    'fieldset.label_translator_prefix' => 'my_app',
                    'fieldset.label_translator_domain' => 'search',
                    'fieldset.auto_generate' => false,
                )
            ),
        );
    }

    /**
     * @dataProvider provideFieldSets
     *
     * @param array  $config
     * @param string $expected
     */
    public function testLoadConfigurationFieldSets($config, $expected)
    {
        $container = $this->createContainer();
        $container->setParameter('doctrine.default_entity_manager', 'default');

        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array('fieldsets' => $config));
        $this->compileContainer($container);

        $this->assertEquals($expected, $container->getParameter('rollerworks_record_filter.fieldsets'));
    }

    public static function provideFieldSets()
    {
        return array(
            array(
                array('customer' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => array('name' => 'number', 'params' => array()),
                            'label' => null,
                            'required' => false,
                            'accept_ranges' => false,
                            'accept_compares' => false,
                        ),
                    ),
                    'import' => array(),
                )),

                serialize(array('customer' => array(
                    'import' => array(),
                    'fields' => array(
                        'id' => array(
                            'required' => false,
                            'accept_ranges' => false,
                            'accept_compares' => false,
                            'label' => null,
                            'type' => array('name' => 'number', 'params' => array()),
                        ),
                    ),
                )))
            ),
        );
    }
}
