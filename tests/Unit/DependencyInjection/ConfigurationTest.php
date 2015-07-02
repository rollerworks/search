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

use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Configuration;

class ConfigurationTest extends AbstractConfigurationTestCase
{
    public function testDefaultsAreValid()
    {
        $this->assertProcessedConfigurationEquals([
            [],
        ], [
            'fieldsets' => [],
        ]);
    }

    public function testEmptyFieldSets()
    {
        $this->assertProcessedConfigurationEquals([
             ['fieldsets' => [
                 'field1' => [],
             ]],
        ], [
            'fieldsets' => [
                'field1' => [
                    'imports' => [],
                    'fields' => [],
                ],
            ],
        ]);
    }

    public function testFieldSets()
    {
        $this->assertProcessedConfigurationEquals([
             ['fieldsets' => [
                'field1' => [
                    'fields' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                        'name' => [
                            'type' => 'text',
                            'model_class' => 'stdClass',
                            'model_property' => 'name',
                        ],
                    ],
                 ],
             ]],
        ], [
             'fieldsets' => [
                 'field1' => [
                    'imports' => [],
                    'fields' => [
                        'id' => [
                            'type' => 'integer',
                            'required' => false,
                            'model_class' => null,
                            'model_property' => null,
                            'options' => [],
                        ],
                        'name' => [
                            'type' => 'text',
                            'required' => false,
                            'model_class' => 'stdClass',
                            'model_property' => 'name',
                            'options' => [],
                        ],
                    ],
                 ],
             ],
        ]);
    }

    public function testInvalidFieldSetWithMissingModelProperty()
    {
        $this->assertConfigurationIsInvalid([
             ['fieldsets' => [
                'field1' => [
                    'fields' => [
                        'id' => [
                            'type' => 'integer',
                            'model_class' => 'stdClass',
                        ],
                    ],
                 ],
             ]],
        ], 'When setting the model reference, both "model_class" and "model_property" must have a value.');

        $this->assertConfigurationIsInvalid([
             ['fieldsets' => [
                'field1' => [
                    'fields' => [
                        'id' => [
                            'type' => 'integer',
                            'model_property' => 'stdClass',
                        ],
                    ],
                 ],
             ]],
        ], 'When setting the model reference, both "model_class" and "model_property" must have a value.');
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }
}
