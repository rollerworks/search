FieldSetRegistry
----------------

A FieldSetRegistry (:class:`Rollerworks\\Component\\Search\\FieldSetRegistry`)
allows to load a :ref:`FieldSet <fieldset>` from a registry.

The default :class:`Rollerworks\\Component\\Search\\LazyFieldSetRegistry` allows
to load a FieldSet using the FQCN of a FieldSetConfigurator or by using
a `PSR-11`_ compatible container.

.. code-block:: php

    use Rollerworks\Component\Search\LazyFieldSetRegistry;

    $container = ...;
    $registry = new LazyFieldSetRegistry($container, ['fieldset-name' => 'service-id']);

The FieldSetRegistry is amongst used when unserializing a serialized SearchCondition,
so that you don't have to inject the FieldSet explicitly.

.. _`PSR-11`: http://www.php-fig.org/psr/psr-11/
