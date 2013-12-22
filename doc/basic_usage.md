Basic usage
===========

FieldSets
---------

FieldSets can be generated per usage (see the Rollerworks Search documentation for details)
Or by registering then as services in the Service Container.

Registering FieldSets is done using the `rollerworks_search.fieldsets.fieldSetName` configuration.

``` yaml
# app/config/config.yml
rollerworks_search:
    fieldsets:
        users:
            fields:
                id:
                    type:           integer
                    required:       false
                    model_class:    "Acme\UserBundle\Model\User"
                    model_property: id
                    options:        []
                username:
                    type:           text
                    required:       false
                    model_class:    "Acme\UserBundle\Model\User"
                    model_property: name
```

Or by importing them from the model metadata.

``` yaml
# app/config/config.yml
rollerworks_search:
    fieldsets:
        users:
            import:
                -
                    class: "Acme\UserBundle\Model\User"
                    include_fields: [id, username]
```

Now the FieldSet can be referenced to its service-id `rollerworks_search.fieldset.users`.

    A FieldSet service is shared and not changeable.

```php
$fieldset = $container->get('rollerworks_search.fieldset.users');
```

Input processors
----------------

An new input-processor is generated using the `rollerworks_search.input_factory` service.
Each processor instance is only meant to be bound to one FieldSet.

    Its also possible to create a new input-processor by revering to
    `rollerworks_search.input.[processor-name]`, but these services are defined with scope prototype
    and it require that any service that references them is also defined with scope prototype.

```php
$filterQuery = $container->get('rollerworks_search.input_factory')->create('filter_query');

// Now set the FieldSet
$filterQuery->setFieldSet(/* ... */);
```

Formatter
---------

The 'main' formatter is the `Rollerworks\Component\Search\Formatter\ChainFormatter`
which performs the registered formatters in order.

Formatters can be tagged with `rollerworks_search.formatter`
and a priority for there position, the search bundle will automatically register them.

**Warning: The `TransformFormatter` is should be run as first, never make your priority higher then 999. **

The chain formatter is available as the `rollerworks_search.chain_formatter` service.

```php
$formatter = $container->get(`rollerworks_search.chain_formatter`);
```
