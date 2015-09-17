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

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Configuration;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\RollerworksSearchExtension;

class ExtensionConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    public function testSupportsAllConfigFormats()
    {
        $expectedConfiguration = [
            'metadata' => [
                'cache_driver' => 'memory',
                'cache_dir' => 'none',
                'cache_freshness_validator' => 'rollerworks_search.metadata.freshness_validator.file_tracking',
                'auto_mapping' => false,
                'mappings' => [
                   'AcmeUser' => [
                       'dir' => 'Resource/search/',
                       'prefix' => 'Model\\',
                       'is_bundle' => true,
                       'mapping' => true,
                   ],
                ],
            ],
            'fieldsets' => [
                'field1' => [
                    'imports' => [
                        [
                            'class' => 'Model\User',
                            'include_fields' => ['name', 'date'],
                            'exclude_fields' => [],
                        ],
                    ],
                    'fields' => [
                        'id' => [
                            'type' => 'integer',
                            'model_class' => 'stdClass',
                            'model_property' => 'id',
                            'required' => false,
                            'options' => [],
                        ],
                        'group' => [
                            'type' => 'text',
                            'model_class' => 'stdClass',
                            'model_property' => 'group',
                            'required' => false,
                            'options' => [],
                        ],
                    ],
                ],
                'field2' => [
                    'imports' => [
                        [
                            'class' => 'Model\User',
                            'include_fields' => ['name'],
                            'exclude_fields' => [],
                        ],
                    ],
                    'fields' => [
                        'id' => [
                            'type' => 'integer',
                            'model_class' => 'stdClass',
                            'model_property' => 'id',
                            'required' => false,
                            'options' => [],
                        ],
                        'group' => [
                            'type' => 'text',
                            'model_class' => 'stdClass',
                            'model_property' => 'group',
                            'options' => [
                                'max' => 10,
                                'foo' => null,
                                'bar' => [
                                    'foo' => null,
                                    '0' => 100,
                                ],
                                'doctor' => [
                                    'name' => 'who',
                                ],
                            ],
                            'required' => false,
                        ],
                    ],
                ],
            ],
        ];

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, [
            'config/config.yml',
            'config/config.xml',
            'config/config.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    protected function getContainerExtension()
    {
        return new RollerworksSearchExtension();
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }
}
