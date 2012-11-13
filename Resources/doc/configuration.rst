Configuration
=============

Introduction
------------

Configuring the RecordFilter is very simple and basic, everything is optional.

FieldSets
---------

The first thing you properly want configure are your FieldSets,
FieldSets can created during runtime or be a part of your app configuration.

Configuring the FieldSets allows for dumping them as PHP classes
during cache warming. But only when the fieldset factory is enabled.

.. note::

    Dumped FieldSet are marked as frozen and can't be changed.

    Only the options of the configured type can be changed.
    Each type object is unique so changing them will not affect others.

Add the following to your config file.

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml
        rollerworks_record_filter:
            factories:
                fieldset:
                    # Set auto generation to true, or else the dumping is not performed during cache warming
                    auto_generate: true

                    # The namespace created FieldSets are stored under
                    # Default is %rollerworks_record_filter.filters_namespace%
                    namespace: RecordFilter

            fieldsets:
                # Note: the set_name must be unique trough out your application
                set_name:
                    fields:
                        # The fieldname must be unique 'per fieldset'
                        field_name:
                            # Everything below is optional/can be omitted, and defaults to false and null respectively
                            required:         false
                            accept_ranges:    false
                            accept_compares:  false

                            # Label can be hardcoded or null for translatable (see below)
                            label:            null

                            # The type must be either

                            # a string referring to the filter-type name (as registered alias in the DIC) or null
                            type: null

                            # or an array when using parameters
                            type: { name: type, params: { param1: value } }

                            # Class property reference, this is needed when Doctrine or similar is used
                            # Class must be an fully qualified class name with namespace
                            ref:
                                class:    Full\Class\Name

                                # Property, NOT the filter field-name
                                property: property-name

                    # And/or you can import using the class metadata.
                    # Explicit fields defined above will overwrite imported ones.
                    import:
                        -
                            # Class must be an fully qualified class name with namespace
                            class: Full\Class\Name

                            # You can either specify fields that must be imported or fields that must be excluded.
                            # Only include_fields or exclude_fields, not both. include prevails over exclude

                            # Only import these fields (by fieldname not property-name)
                            include_fields: [ id, name ]

                            # Only import fields not present in this list
                            exclude_fields: [ id, name ]

    .. code-block:: php

        // app/config/config.php
        $container->loadFromExtension('rollerworks_record_filter', array(
            /* ... */
            'factories' => array(
                /* ... */
                'fieldset' => array(
                    // Set auto generation to true, or else the dumping is not performed during cache warming
                    'auto_generate' => true,

                    // The namespace created FieldSets are stored under
                    // Default is %rollerworks_record_filter.filters_namespace%
                    'namespace' => 'RecordFilter',

                    /* ... */
                ),
            ),

            'fieldsets' => array(
                // Note: set_name must be unique for your application
                'set_name' => array(
                    'fields' => array(
                        // The fieldname must be unique per fieldset
                        'field_name' => array(
                            // Every option is optional and defaults to false and null respectively
                            'required'        => false,
                            'accept_ranges'   => false,
                            'accept_compares' => false,

                            // Label can be hardcoded or translatable (see below)
                            'label' => null,

                            // The type must be either alias-name of the type or null
                            // Or an array when using parameters
                            'type' => array('name' => 'type', 'params' => array('param1' => 'value'))

                            // Class property reference, this is needed when Doctrine or similar is used
                            // Class must be an fully qualified class name with namespace
                            'ref' => array(
                                'class'    => 'Full\Class\Name',

                                // Property, NOT the filter field-name
                                'property' => 'property-name',
                            )
                        ),

                        // And/or you can import the class metadata.
                        // Explicit fields defined above will overwrite imported ones.
                        'import' => array(
                            array(
                                // Class must be an fully qualified class name with namespace
                                'class'    => 'Full\Class\Name',

                                // You can either specify fields that must be imported or fields that must be excluded.
                                // Only include_fields or exclude_fields, not both. include prevails over exclude

                                // Only import only these fields (by fieldname not property-name)
                                'include_fields' => array('id', 'name'),

                                // Only import only fields not present in this list
                                'exclude_fields' => array('id', 'name'),
                            ),
                        ),
                    ),
                ),
                /* ... */
            ),
        ));

.. note::

    FieldSet names must be unique trough out your application.

    Its best to prefix the name with an (optional) vendor and domain
    like you would do with our Entity classes.

    "invoice" domain in vendor "acme" would become "acme_invoice".

Label translation
~~~~~~~~~~~~~~~~~

If your field labels need to be localized, instead of hard coding them
you can use the Translator service. When no label is set or found,
the field-name is used as label.

Add the following to your config file.

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml
        rollerworks_record_filter:
            factories:
                fieldset:
                    # Prefix the translator key with this.
                    # Fieldname "id" will then look something like labels.id
                    label_translator_prefix: ""

                    # Translator domain the labels are stored in
                    label_translator_domain: filters

    .. code-block:: php

        // app/config/config.php
        $container->loadFromExtension('rollerworks_record_filter', array(
            /* ... */
            'factories' => array(
                /* ... */
                'fieldset' => array(
                    // Prefix the translator key with this.
                    // Fieldname "id" will then look something like labels.id
                    'label_translator_prefix' => '',

                    // Translator domain the labels are stored in
                    'label_translator_domain' => 'filters',
                ),
            ),
        ));
