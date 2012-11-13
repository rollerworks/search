Doctrine ORM
============

The `Doctrine`_ component performs the searching of records using Doctrine ORM.

.. toctree::
    :maxdepth: 1

    where_builder

Introduction
------------

To enable Doctrine ORM support for the RecordFilter
add the following to your config file.

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml
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

WhereBuilder
~~~~~~~~~~~~

The WhereBuilder uses Doctrine ORM for creating SQL/DQL WHERE cases
"on the fly" based on the given FieldSet.

.. tip::

    When the FieldSets are defined in your config file its better to enable the WhereBuilderFactory
    as creating a query structure is rather expensive.

To enable DQL support add the following to your config file.

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

.. note::

    If your using multiple Entity Managers, this must be configured for all Entity Managers
    used by the RecordFilter.

WhereBuilderFactory
-------------------

The WhereBuilder Factory uses Doctrine ORM for creating WHERE cases
based in the FieldSets defined in your configuration.

To enable WhereBuilder Factory you must add the following to your config file.

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml
        rollerworks_record_filter:
            # ...

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
            'doctrine' => array(
                'orm' => array(/* ... */),
            ),
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


.. _where_builder_factory: WhereBuilder factory

.. _Doctrine: http://symfony.com/doc/current/book/doctrine.html
