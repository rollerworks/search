<?php

/**
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
));
