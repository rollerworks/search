<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\DependencyInjection\DependencyInjection;

use Rollerworks\Bundle\RecordFilterBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The minimal, required config needed to not have any required validation
     * issues.
     *
     * @var array
     */
    protected static $minimalConfig = array();

    public function testConfigTree()
    {
        $config = array_merge(self::$minimalConfig);

        $processor = new Processor();
        $configuration = new Configuration(array());
        $config = $processor->processConfiguration($configuration, array($config));

        $this->assertEquals(array(), $config['fieldsets'], 'The fieldsets key is just an empty array');
        $this->assertEquals(array(
            'fieldset' => array(
                'namespace' => '%rollerworks_record_filter.filters_namespace%',
                'label_translator_prefix' => '',
                'label_translator_domain' => 'filters',
                'auto_generate' => false,
            ),
        ), $config['factories'], 'The factories key is set');
    }

    public function testConfigTree2()
    {
        $config = array_merge(self::$minimalConfig,
            array('doctrine' => array('orm' => array())),
            array('factories' => array('doctrine' => array('orm' => array()))
        ));

        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, array($config));

        $this->assertEquals(array(), $config['fieldsets'], 'The fieldsets key is just an empty array');
        $this->assertEquals(array(
            'fieldset' => array(
                'namespace' => '%rollerworks_record_filter.filters_namespace%',
                'label_translator_prefix' => '',
                'label_translator_domain' => 'filters',
                'auto_generate' => false,
            ),

            'doctrine' => array(
                'orm' => array(
                    'wherebuilder' => array(
                        'namespace' => '%rollerworks_record_filter.filters_namespace%',
                        'default_entity_manager' => '%doctrine.default_entity_manager%',
                        'auto_generate' => false
                    )
                )
            ),
        ), $config['factories'], 'The factories key is set');
    }

    /**
     * @dataProvider provideFieldSets
     *
     * @param array $input
     * @param array $expected
     */
    public function testConfigTreeWithFieldSets($input, $expected)
    {
        $config = array_merge(self::$minimalConfig, array('fieldsets' => $input));

        $processor = new Processor();
        $configuration = new Configuration(array());
        $config = $processor->processConfiguration($configuration, array($config));

        $this->assertEquals($expected, $config['fieldsets']);
    }

    public function testConfigTreeWithFieldSetsNoDeepMerge()
    {
        $input = array(
            'customer' => array(
                'fields' => array(
                    'id' => array(
                        'type' => 'number',
                    ),
                    'name' => array(
                        'type' => array('name' => 'text', 'params' => array()),
                        'required' => true,
                        'accept_ranges' => true,
                        'accept_compares' => true,
                    )
                )
            )
        );

        $config = array(array_merge(self::$minimalConfig, array('fieldsets' => $input)));
        $config[] = array(
            'fieldsets' => array(
                'customer' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => 'number',
                        ),
                    )
                )
            )
        );

        $processor = new Processor();
        $configuration = new Configuration(array());
        $config = $processor->processConfiguration($configuration, $config);

        $expected = array(
            'customer' => array(
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
            )
        );

        $this->assertEquals($expected, $config['fieldsets']);
    }

    public static function provideFieldSets()
    {
        return array(
            array(
                array('user' => array()),
                array('user' => array('import' => array(), 'fields' => array())),
            ),

            array(
                array('customer' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => 'number',
                        ),
                        'name' => array(
                            'type' => array('name' => 'text', 'params' => array()),
                            'label' => null,
                            'required' => true,
                            'accept_ranges' => true,
                            'accept_compares' => true,
                        )
                    )
                )),
                array(
                    'customer' => array(
                        'fields' => array(
                            'id' => array(
                                'type' => array('name' => 'number', 'params' => array()),
                                'label' => null,
                                'required' => false,
                                'accept_ranges' => false,
                                'accept_compares' => false,
                            ),
                            'name' => array(
                                'type' => array('name' => 'text', 'params' => array()),
                                'label' => null,
                                'required' => true,
                                'accept_ranges' => true,
                                'accept_compares' => true,
                            )
                        ),
                        'import' => array(),
                    )
                )
            ),

            array(
                array('customer' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => 'number',
                        ),
                        'name' => array(
                            'type' => array('name' => 'text', 'params' => array()),
                            'required' => true,
                            'accept_ranges' => true,
                            'accept_compares' => true,
                            'ref' => array('class' => 'customer', 'property' => 'name')
                        )
                    )
                )),
                array(
                    'customer' => array(
                        'fields' => array(
                            'id' => array(
                                'type' => array('name' => 'number', 'params' => array()),
                                'label' => null,
                                'required' => false,
                                'accept_ranges' => false,
                                'accept_compares' => false,
                            ),
                            'name' => array(
                                'type' => array('name' => 'text', 'params' => array()),
                                'label' => null,
                                'required' => true,
                                'accept_ranges' => true,
                                'accept_compares' => true,
                                'ref' => array('class' => 'customer', 'property' => 'name')
                            )
                        ),
                        'import' => array(),
                    )
                )
            ),

            array(
                array('customer' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => 'number',
                        ),
                        'name' => array(
                            'type' => array('name' => 'text', 'params' => array()),
                            'label' => 'real name',
                            'required' => true,
                            'accept_ranges' => true,
                            'accept_compares' => true,
                            'ref' => array('class' => 'customer', 'property' => 'name')
                        )
                    )
                )),
                array(
                    'customer' => array(
                        'fields' => array(
                            'id' => array(
                                'type' => array('name' => 'number', 'params' => array()),
                                'label' => null,
                                'required' => false,
                                'accept_ranges' => false,
                                'accept_compares' => false,
                            ),
                            'name' => array(
                                'type' => array('name' => 'text', 'params' => array()),
                                'label' => 'real name',
                                'required' => true,
                                'accept_ranges' => true,
                                'accept_compares' => true,
                                'ref' => array('class' => 'customer', 'property' => 'name')
                            )
                        ),
                        'import' => array(),
                    )
                )
            ),

            array(
                array(
                    'customer' => array(
                        'fields' => array(
                            'id' => array(
                                'type' => 'number',
                            ),
                            'name' => array(
                                'type' => array('name' => 'text', 'params' => array()),
                            )
                        )
                    ),
                    'user' => array(
                        'fields' => array(
                            'id' => array(
                                'type' => 'number',
                            ),
                            'name' => array(
                                'type' => array('name' => 'text', 'params' => array()),
                            )
                        )
                    )
                ),
                array(
                    'customer' => array(
                        'fields' => array(
                            'id' => array(
                                'type' => array('name' => 'number', 'params' => array()),
                                'label' => null,
                                'required' => false,
                                'accept_ranges' => false,
                                'accept_compares' => false,
                            ),
                            'name' => array(
                                'type' => array('name' => 'text', 'params' => array()),
                                'label' => null,
                                'required' => false,
                                'accept_ranges' => false,
                                'accept_compares' => false,
                            )
                        ),
                        'import' => array(),
                    ),
                    'user' => array(
                        'fields' => array(
                            'id' => array(
                                'type' => array('name' => 'number', 'params' => array()),
                                'label' => null,
                                'required' => false,
                                'accept_ranges' => false,
                                'accept_compares' => false,
                            ),
                            'name' => array(
                                'type' => array('name' => 'text', 'params' => array()),
                                'label' => null,
                                'required' => false,
                                'accept_ranges' => false,
                                'accept_compares' => false,
                            )
                        ),
                        'import' => array(),
                    )
                )
            ),

            array(
                array('customer' => array(
                    'import' => array(array('class' => 'CustomerEntity'))
                )),
                array(
                    'customer' => array(
                        'fields' => array(),
                        'import' => array(array('class' => 'CustomerEntity', 'include_fields' => array(), 'exclude_fields' => array())),
                    )
                )
            ),

            array(
                array('customer' => array(
                    'import' => array('CustomerEntity')
                )),
                array(
                    'customer' => array(
                        'fields' => array(),
                        'import' => array(array('class' => 'CustomerEntity', 'include_fields' => array(), 'exclude_fields' => array())),
                    )
                )
            ),

            array(
                array('customer' => array(
                    'import' => array(array('class' => 'CustomerEntity', 'include_fields' => array('id', 'email')))
                )),
                array(
                    'customer' => array(
                        'fields' => array(),
                        'import' => array(array('class' => 'CustomerEntity', 'include_fields' => array('id', 'email'), 'exclude_fields' => array())),
                    )
                )
            ),

            array(
                array('customer' => array(
                    'import' => array(array('class' => 'CustomerEntity', 'exclude_fields' => array('password')))
                )),
                array(
                    'customer' => array(
                        'fields' => array(),
                        'import' => array(array('class' => 'CustomerEntity', 'include_fields' => array(), 'exclude_fields' => array('password'))),
                    )
                )
            ),

            array(
                array('customer' => array(
                    'import' => array(array('class' => 'CustomerEntity')),
                    'fields' => array(
                        'id' => array(
                            'type' => array('name' => 'number', 'params' => array()),
                        ),
                        'name' => array(
                            'type' => array('name' => 'text', 'params' => array('accept_newline' => true, 'forbidden_chars' => array('>', '<'))),
                            'required' => true,
                            'accept_ranges' => true,
                            'accept_compares' => true,
                        )
                    )
                )),
                array(
                    'customer' => array(
                        'import' => array(array('class' => 'CustomerEntity', 'include_fields' => array(), 'exclude_fields' => array())),
                        'fields' => array(
                            'id' => array(
                                'type' => array('name' => 'number', 'params' => array()),
                                'label' => null,
                                'required' => false,
                                'accept_ranges' => false,
                                'accept_compares' => false,
                            ),
                            'name' => array(
                                'required' => true,
                                'accept_ranges' => true,
                                'accept_compares' => true,
                                'type' => array('name' => 'text', 'params' => array('accept_newline' => true, 'forbidden_chars' => array('>', '<'))),
                                'label' => null,
                            )
                        ),
                    )
                )
            ),
        );
    }
}
