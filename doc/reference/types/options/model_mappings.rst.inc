model_mappings
~~~~~~~~~~~~~~

**type**: ``array`` or ``null`` **default**: ``null``

Model class mappings for specific condition processors.

The Doctrine ORM processor needs to know which Entity (Model) and property,
the search field is mapped to. This can be configured directly on the field
using this option, or using the WhereBuilder configuration.

Using this option has the advantage that you only need to configure it once.

The ``model_mappings`` option expects an array with one or more
``[Entity, property]`` mappings.

.. code-block:: php

    $builder->add('name', 'some_type', [
        // ...
        'model_mappings' => [
            ['Acme\Model\Users', 'firstName'],
            ['Acme\Model\Users', 'lastName'],
        ],
    ]);

Setting more then one mapping will effectively apply the field's values on
all the mappings, in the example above the ``name`` field is applied for
both the first and last name of a ``User``.
