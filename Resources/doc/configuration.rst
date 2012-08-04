Configuration
=============

Introduction
------------

Configuration of the RecordFilter is very simple and basic.

Out of the box you don't need to configure anything start working.

The only thing you properly want to set is the FieldSets
and auto generating of classes.

FieldSets
---------

FieldSets can be dumped during cache warming for better performance.

To do this, configure the FieldSets in your application config.

.. caution ::

    The FieldSet is marked as frozen and can't be changed,
    except for type options.

.. caution ::

    The FieldSet name must be unique trough out the application.
    Its best to prefix the FieldSet name with an vendor and domain
    like you would do with ORM Entity classes.

    To ensure uniqueness of the fieldname when importing.
    Its an best practise to prefix an fieldname with an vendor and domain,
    "id" in vendor "acme" and domain "invoice" would become "acme_invoice_id".

.. configuration-block::

    .. code-block:: yaml

        rollerworks_record_filter:
            # Set auto generation to true, or else the cache warming is not performed
            factories.fieldset.auto_generate: true

            # Namespace the created FieldSets are stored under
            # Default this uses %rollerworks_record_filter.filters_namespace%
            factories.fieldset.namespace: RecordFilter

            fieldsets:
                set_name:
                    fields:
                        # The fieldname must be unique per fieldset
                        field_name:
                            # Every field is optional and defaults to false and null respectively
                            required: false
                            accept_ranges: false
                            accept_compares: false

                            # The type must be either

                            # an string referring to the alias-name of the type or null
                            type: null

                            # or an array when using parameters
                            type: { name: type, params: { param1: value } }

                            # Class property reference, this is needed when Record\WhereBuilder is used
                            # Class must be fully qualified and not an alias
                            ref:
                                class: Full\Class\Name
                                property: property-name

                    # And/or you can import the class metadata.
                    # Explicit fields defined above will overwrite imported ones.
                    import:
                        -
                            class: Full\Class\Name

                            # You can either specify fields that must be imported or fields that must be excluded.
                            # You can only use include_fields or exclude_fields, not both. include prevails over exclude

                                # Only import these fields (by fieldname not property-name)
                                include_fields: [ id, name ]

                                # Only import field not present in this list
                                exclude_fields: [ id, name ]

As the field label can be localized
its best to handle this using the translator.

When no label can be found, the fieldname is used as label.

.. configuration-block::

    .. code-block:: yaml

        rollerworks_record_filter:
            # prefix the translator key with this.
            # Fieldname "id" will then look something like labels.id
            label_translator_prefix: ""

            # Translator domain the labels are stored in
            label_translator_domain: filters

Factories
---------

SqlWhereBuilder
~~~~~~~~~~~~~~~

The SqlWhereBuilder factory uses Doctrine ORM for creating SQL WHERE cases
based in the fieldsets defined in the application configuration.

To enable this factory place the following in your application config.

.. configuration-block::

    .. code-block:: yaml

        rollerworks_record_filter:
            factories.sql_wherebuilder:

                # Enable auto generating of classes
                # Note: factories.fieldset.auto_generate must be enabled for this to work.
                auto_generate: true

                # Default Doctrine ORM entity manager, this the entity manager "name"
                # not the entity manager service reference.
                default_entity_manager: %doctrine.default_entity_manager%

SqlWhereBuilder
---------------

The SqlWhereBuilder uses Doctrine ORM for creating SQL WHERE cases
"on the fly" based on the given fieldset.

.. tip ::

    When the FieldSet is defined in the application configuration
    its better to enable the SqlWhereBuilder factory as creating
    an SQL structure on the fly is expensive.

.. configuration-block::

    .. code-block:: yaml

        rollerworks_record_filter:
            record.sql:
                # Default Doctrine ORM entity manager, this the entity manager "name"
                # not the entity manager service reference.
                default_entity_manager: %doctrine.default_entity_manager%
