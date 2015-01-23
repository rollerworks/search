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

Now the FieldSet can be referenced by its service-id `rollerworks_search.fieldset.users`.

    A FieldSet service is shared and not changeable.

```php
$fieldset = $container->get('rollerworks_search.fieldset.users');
```

Or by using the `rollerworks_search.fieldset_registry` service, which ensures
only FieldSets are returned.

```php
$fieldset = $container->get('rollerworks_search.fieldset_registry')->getFieldSet('users');
```

Input processors
----------------

An input-processor is created using the `rollerworks_search.input_factory` service.
Each input processor can be reused.

    Its also possible to create a input-processor by revering to
    `rollerworks_search.input.[processor-name]`, but this however does not guarantee
    the requested service is an input-processor, so be careful to validate the
    requested processor name!

```php
$filterQuery = $container->get('rollerworks_search.input_factory')->create('filter_query');
```

ConditionOptimizer
------------------

The 'main' condition optimizer is the `Rollerworks\Component\Search\ConditionOptimizer\ChainOptimizer`
which performs the registered optimizers in order.

Condition optimizer can be tagged with `rollerworks_search.condition_optimizer`
after which the search bundle will automatically register them.

The ChainOptimizer is available as the `rollerworks_search.chain_condition_optimizer` service.

```php
$formatter = $container->get(`rollerworks_search.chain_condition_optimizer`);
```
