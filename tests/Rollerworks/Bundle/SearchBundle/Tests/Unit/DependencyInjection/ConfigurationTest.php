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

    protected function getConfiguration()
    {
        return new Configuration();
    }
}
