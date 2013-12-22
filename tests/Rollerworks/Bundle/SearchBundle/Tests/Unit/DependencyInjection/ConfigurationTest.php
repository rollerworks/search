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

use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Configuration;

class ConfigurationTest extends AbstractConfigurationTestCase
{
    public function testDefaultsAreValid()
    {
        $this->assertProcessedConfigurationEquals(array(
            array()
        ), array(
            'fieldsets' => array()
        ));
    }

    public function testEmptyFieldSets()
    {
        $this->assertProcessedConfigurationEquals(array(
             array('fieldsets' => array(
                 'field1' => array()
             ))
        ), array(
            'fieldsets' => array(
                'field1' => array(
                    'imports' => array(),
                    'fields' => array(),
                )
            )
        ));
    }

    public function testFieldSets()
    {
        $this->assertProcessedConfigurationEquals(array(
             array('fieldsets' => array(
                'field1' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => 'integer',
                        ),
                        'name' => array(
                            'type' => 'text',
                            'model_class' => 'stdClass',
                            'model_property' => 'name',
                        ),
                    ),
                 )
             ))
        ), array(
             'fieldsets' => array(
                 'field1' => array(
                    'imports' => array(),
                    'fields' => array(
                        'id' => array(
                            'type' => 'integer',
                            'required' => false,
                            'model_class' => null,
                            'model_property' => null,
                            'options' => array()
                        ),
                        'name' => array(
                            'type' => 'text',
                            'required' => false,
                            'model_class' => 'stdClass',
                            'model_property' => 'name',
                            'options' => array()
                        ),
                    ),
                 )
             )
        ));
    }

    public function testInvalidFieldSetWithMissingModelProperty()
    {
        $this->assertConfigurationIsInvalid(array(
             array('fieldsets' => array(
                'field1' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => 'integer',
                            'model_class' => 'stdClass',
                        ),
                    ),
                 )
             ))
        ), 'When setting the model reference, both "model_class" and "model_property" must have a value.');

        $this->assertConfigurationIsInvalid(array(
             array('fieldsets' => array(
                'field1' => array(
                    'fields' => array(
                        'id' => array(
                            'type' => 'integer',
                            'model_property' => 'stdClass',
                        ),
                    ),
                 )
             ))
        ), 'When setting the model reference, both "model_class" and "model_property" must have a value.');
    }

    public function testDoctrineOrmEntityManager()
    {
        $this->assertProcessedConfigurationEquals(array(
            array(
                'doctrine' => array(
                    'orm' => array()
                )
            )
        ), array(
            'doctrine' => array(
                'orm' => array(
                    'entity_managers' => array(),
                    'cache_driver' => 'rollerworks_search.cache.array_driver',
                )
            ),
            'fieldsets' => array()
        ));
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }
}
