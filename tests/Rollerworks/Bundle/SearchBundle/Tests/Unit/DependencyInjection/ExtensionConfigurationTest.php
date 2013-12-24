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

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\Configuration;
use Rollerworks\Bundle\SearchBundle\DependencyInjection\RollerworksSearchExtension;

class ExtensionConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    public function testSupportsAllConfigFormats()
    {
        $expectedConfiguration = array(
            'metadata' => array(
                'cache_driver' => 'memory',
                'cache_dir' => 'none',
                'auto_mapping' => false,
                'mappings' => array(
                   'AcmeUser' => array(
                       'dir' => 'Resource/search/',
                       'prefix' => 'Model\\',
                       'is_bundle' => true,
                       'mapping' => true,
                   )
                ),
            ),
            'fieldsets' => array(
                'field1' => array(
                    'imports' => array(
                        array(
                            'class' => 'Model\User',
                            'include_fields' => array('name', 'date'),
                            'exclude_fields' => array(),
                        )
                    ),
                    'fields' => array(
                        'id' => array(
                            'type' => 'integer',
                            'model_class' => 'stdClass',
                            'model_property' => 'id',
                            'required' => false,
                            'options' => array()
                        ),
                        'group' => array(
                            'type' => 'text',
                            'model_class' => 'stdClass',
                            'model_property' => 'group',
                            'required' => false,
                            'options' => array()
                        ),
                    ),
                ),
                'field2' => array(
                    'imports' => array(
                        array(
                            'class' => 'Model\User',
                            'include_fields' => array('name'),
                            'exclude_fields' => array(),
                        )
                    ),
                    'fields' => array(
                        'id' => array(
                            'type' => 'integer',
                            'model_class' => 'stdClass',
                            'model_property' => 'id',
                            'required' => false,
                            'options' => array()
                        ),
                        'group' => array(
                            'type' => 'text',
                            'model_class' => 'stdClass',
                            'model_property' => 'group',
                            'options' => array(
                                'max' => 10,
                                'foo' => null,
                                'bar' => array(
                                    'foo' => null,
                                    '0' => 100,
                                ),
                                'doctor' => array(
                                    'name' => 'who',
                                )
                            ),
                            'required' => false,
                        ),
                    ),
                ),
            ),
            'doctrine' => array(
                'orm' => array(
                    'entity_managers' => array('default', 'secure'),
                    'cache_driver' => null,
                )
            )
        );

        $formats = array_map(function ($path) {
            return __DIR__ . '/../../Resources/Fixtures/' . $path;
        }, array(
            'config/config.yml',
            'config/config.xml',
            'config/config.php',
        ));

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, array($format));
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
