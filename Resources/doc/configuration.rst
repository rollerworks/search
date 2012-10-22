Configuration
=============

Introduction
------------

Configuration of the RecordFilter is very simple and basic,
out of the box you don't need to configure anything to start working.

The only thing you properly want to set is the FieldSets
and auto generating of classes.

Auto generating of the classes increases performance
and allows easier configuration.

FieldSets
---------

For better performance, FieldSets can be dumped during cache warming.

To do this, you must configure the FieldSets in our application config.

.. caution::

    Dumped FieldSet are marked as frozen and can't be changed,
    except for there type configurations.

.. caution::

    The FieldSet names must be unique trough out the application.

    Its best to prefix the FieldSet name with an (optional) vendor and domain
    like you would do with our ORM Entity classes.

    "invoice" domain in vendor "acme" would become "acme_invoice".

    You can also prefix the fields in this way, but most time this is not needed.

.. configuration-block::

    .. code-block:: yaml

        rollerworks_record_filter:
            factories:
                fieldset:
                    # Set auto generation to true, or else the cache warming is not performed
                    auto_generate: true

                    # Namespace the created FieldSets are stored under
                    # Default is %rollerworks_record_filter.filters_namespace%
                    namespace: RecordFilter

            fieldsets:
                # Note: set_name must be unique for your application config
                set_name:
                    fields:
                        # The fieldname must be unique per fieldset
                        field_name:
                            # Every option is optional and defaults to false and null respectively
                            required:         false
                            accept_ranges:    false
                            accept_compares:  false

                            # Label can be hardcoded or translatable (see below)
                            label:            null

                            # The type must be either

                            # an string referring to the alias-name of the type or null
                            type: null

                            # or an array when using parameters
                            type: { name: type, params: { param1: value } }

                            # Class property reference, this is needed when Doctrine or similar is used
                            # Class must be an fully qualified class name with namespace
                            ref:
                                class:    Full\Class\Name

                                # Property, NOT the filter field-name
                                property: property-name

                    # And/or you can import the class metadata.
                    # Explicit fields defined above will overwrite imported ones.
                    import:
                        -

                            # Class must be an fully qualified class name with namespace
                            class: Full\Class\Name

                            # You can either specify fields that must be imported or fields that must be excluded.
                            # Only include_fields or exclude_fields, not both. include prevails over exclude

                            # Only import only these fields (by fieldname not property-name)
                            include_fields: [ id, name ]

                            # Only import only fields not present in this list
                            exclude_fields: [ id, name ]

    .. code-block:: php

        // app/config/config.php
        $container->loadFromExtension('rollerworks_record_filter', array(
            /* ... */
            'factories' => array(
                /* ... */
                'fieldset' => array(
                    // Set auto generation to true, or else the cache warming is not performed
                    'auto_generate' => true,

                    // Namespace the created FieldSets are stored under
                    // Default is %rollerworks_record_filter.filters_namespace%
                    'namespace' => 'RecordFilter',

                    /* ... */
                ),
            ),

            'fieldsets' => array(
                // Note: set_name must be unique for your application config
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


Translation
~~~~~~~~~~~

As field labels can be localized,
instead of hard coding them you can also choose to use the translator.

When no label can be found/set, the field name is used as label.

.. configuration-block::

    .. code-block:: yaml

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

Doctrine
--------

OrmWhereBuilder
~~~~~~~~~~~~~~~

The Doctrine\Orm\WhereBuilder uses Doctrine ORM for creating SQL/DQL WHERE cases
"on the fly" based on the given FieldSet.

.. tip::

    When the FieldSet is defined in the application configuration
    its better to enable the Doctrine OrmWhereBuilder factory as creating
    an query structure on the fly is rather expensive.

.. configuration-block::

    .. code-block:: yaml

        rollerworks_record_filter:
            doctrine:
                orm:
                    # Default Doctrine ORM entity manager, this is the entity manager "name"
                    # not the entity manager service reference.
                    default_entity_manager: %doctrine.default_entity_manager%


    .. code-block:: php

        // app/config/config.php
        $container->loadFromExtension('rollerworks_record_filter', array(
            /* ... */
            'doctrine' => array(
                'orm' => array(
                    // Default Doctrine ORM entity manager, this is the entity manager "name"
                    // not the entity manager service reference.
                    'default_entity_manager' => '%doctrine.default_entity_manager%',
                ),
            ),
        ));


If your using DQL you must add the following to your application config.

When your using multiple entity managers, they must be applied all entity
managers used by the RecordFilter.

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml
        doctrine:
            orm:
                # ...
                entity_managers:
                    default:
                        # ...
                        dql:
                            string_functions:
                                RECORD_FILTER_FIELD_CONVERSION: Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions\FilterFieldConversion
                                RECORD_FILTER_VALUE_CONVERSION: Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions\FilterValueConversion

    .. code-block:: xml

        <!-- app/config/config.xml -->
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:doctrine="http://symfony.com/schema/dic/doctrine"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                                http://symfony.com/schema/dic/doctrine http://symfony.com/schema/dic/doctrine/doctrine-1.0.xsd">

            <doctrine:config>
                <doctrine:orm>
                    <!-- ... -->
                    <doctrine:entity-manager name="default">
                        <!-- ... -->
                        <doctrine:dql>
                            <doctrine:string-function name="RECORD_FILTER_FIELD_CONVERSION">Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions\FilterFieldConversion</doctrine:string-function>
                            <doctrine:string-function name="RECORD_FILTER_VALUE_CONVERSION">Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions\FilterValueConversion</doctrine:string-function>
                        </doctrine:dql>
                    </doctrine:entity-manager>
                </doctrine:orm>
            </doctrine:config>
        </container>

    .. code-block:: php

        // app/config/config.php
        $container->loadFromExtension('doctrine', array(
            'orm' => array(
                ...,
                'entity_managers' => array(
                    'default' => array(
                        ...,
                        'dql' => array(
                            'string_functions' => array(
                                'RECORD_FILTER_FIELD_CONVERSION' => 'Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions\FilterFieldConversion',
                                'RECORD_FILTER_VALUE_CONVERSION' => 'Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions\FilterValueConversion',
                            ),
                        ),
                    ),
                ),
            ),
        ));

Factories
---------

DoctrineOrmWhereBuilder
~~~~~~~~~~~~~~~~~~~~~~~

The OrmWhereBuilder factory uses Doctrine ORM for creating WHERE cases
based in the FieldSets defined in our application configuration.

To enable this factory we must place the following in our application config.

And add the custom DQL functions as described above.

.. configuration-block::

    .. code-block:: yaml

        rollerworks_record_filter:
            factories:
                doctrine:
                    orm:
                        # Enable auto generating of classes
                        # Note: factories.fieldset.auto_generate must be enabled for this to work.
                        auto_generate: true

                        # Default Doctrine ORM entity manager, this is the entity manager "name"
                        # not the entity manager service reference.
                        default_entity_manager: %doctrine.default_entity_manager%

    .. code-block:: php

        // app/config/config.php
        $container->loadFromExtension('rollerworks_record_filter', array(
            /* ... */
            'factories' => array(
                /* ... */
                'doctrine' => array(
                    'orm' => array(
                        // Enable auto generating of classes
                        // Note: factories.fieldset.auto_generate must be enabled for this to work.
                        'auto_generate' => true,

                        // Default Doctrine ORM entity manager, this is the entity manager "name"
                        // not the entity manager service reference.
                        'default_entity_manager' => '%doctrine.default_entity_manager%',
                    ),
                ),
            ),
        ));
