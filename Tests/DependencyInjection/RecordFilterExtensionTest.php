<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
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

        $this->assertFalse($container->getParameter('rollerworks_record_filter.factories.sql_wherebuilder.auto_generate'));
        $this->assertEquals('RecordFilter', $container->getParameter('rollerworks_record_filter.factories.sql_wherebuilder.namespace'));
    }

    public function testLoadConfigurationSql()
    {
        $container = $this->createContainer();
        $container->setParameter('doctrine.default_entity_manager', 'default');
        $container->setParameter('doctrine.second_entity_manager', 'default');

        $container->registerExtension(new RollerworksRecordFilterExtension());
        $container->loadFromExtension('rollerworks_record_filter', array('record' => array('sql' => array('default_entity_manager' => 'second'))));
        $this->compileContainer($container);

        $factoryDef = $container->getDefinition('rollerworks_record_filter.sql_wherebuilder_factory');
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
        $container->loadFromExtension('rollerworks_record_filter', array('factories' => $config));
        $this->compileContainer($container);

        foreach ($expected as $name => $value) {
            $this->assertEquals($value, $container->getParameter('rollerworks_record_filter.factories.' . $name));
        }

        if (isset($config['sql_wherebuilder']['default_entity_manager'])) {
            $factoryDef = $container->getDefinition('rollerworks_record_filter.sql_wherebuilder_factory');
            $calls = $factoryDef->getMethodCalls();

            $found = false;
            foreach ($calls as $call) {
                if ('setEntityManager' === $call[0]) {
                    $this->assertEquals($call[1], array(new Reference(sprintf('doctrine.orm.%s_entity_manager', $config['sql_wherebuilder']['default_entity_manager']))));

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
                array('sql_wherebuilder' => array(
                    'namespace' => 'MyApp\\RecordFilter',
                    'default_entity_manager' => 'second',
                    'auto_generate' => true
                )),

                array(
                    'sql_wherebuilder.namespace' => 'MyApp\\RecordFilter',
                    'sql_wherebuilder.auto_generate' => true
                )
            ),

            array(
                array('sql_wherebuilder' => array(
                    'namespace' => 'MyApp\\RecordFilter',
                    'default_entity_manager' => 'second',
                    'auto_generate' => true
                ),
                'fieldset' => array(
                    'namespace' => 'MyApp\\RecordFilter',
                    'label_translator_prefix' => 'my_app',
                    'label_translator_domain' => 'search',
                    'auto_generate' => false
                )),

                array(
                    'sql_wherebuilder.namespace' => 'MyApp\\RecordFilter',
                    'sql_wherebuilder.auto_generate' => true,

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
                            'type' => array('name' => 'number', 'params' => array()),
                        ),
                    ),
                )))
            ),
        );
    }
}
