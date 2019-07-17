Custom Field Type
=================

RollerworksSearch already comes with a ridge collection of field types.
However there are situations where you may want to create a custom field
type for a specific purpose. Or when you want enhance there existing function
functionality.

Fortunately creating your own field types, or extending existing types
(without worrying about class inheritance) is really easy.

This entire chapter is dedicated to writing your own field types, and field
type extensions, including data transformers, value comparison and unit testing.

So lets get's started!

Use-case description
--------------------

This recipe assumes you need a field definition which holds specially formatted
Client ID's, based on the existing integer field. This section explains how the
field is defined, and how you can register it for usage in your application.

The Client ID begins with a 'C' prefix, and is prepended with zeros until it's
at least 4 digits.

Example: ``C30320, C0001, C442482, C0020``.

Defining the Field Type
-----------------------

In order to create the custom field type, first you have to create the class representing the type.
In this situation the class holding the field type will be called ``ClientIdType`` and the file will be
stored in the default location for search field types, which is ``<VendorName>\\Search\\Type``.

Make sure the field extends :class:`Rollerworks\\Component\\Search\\Field\\AbstractFieldType`:

.. code-block:: php
    :linenos:

    // src/Acme/Client/Search/Type/ClientIdType.php

    namespace Acme\Client\Search\Type;

    use Acme\Client\Search\DataTransformer\ClientIdTransformer;
    use Rollerworks\Component\Search\Field\AbstractFieldType;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    class ClientIdType extends AbstractFieldType
    {
        public function buildType(FieldConfigInterface $config, array $options)
        {
            $config->addViewTransformer(new ClientIdTransformer());
        }
    }

.. tip::

    The location of this file is not important - the ``Search\\Type`` directory
    is just a recommended convention.

Note that a type can do more then shown here, but we will get to that later,
for now this is enough to create the ClientIdType.

There are three methods that are particularly important:

``buildType()``
    Each field type has a ``buildType`` method, which is where you configure
    and build any field(s).

    This method allows to set a view/norm data-transformer, value-comparator,
    and support value types.

``buildView()``
    This method is used to set any extra variables you'll
    need when rendering your field in a template. For example, in `IntegerType`_,
    a ``precision`` variable is set and used in the template to set
    the ``precision`` attribute on the ``input`` field.

``configureOptions()``
    This defines options for your field type that can be used in ``buildType()``
    and ``buildView()``. There are a lot of options common to all fields
    (see :doc:`/reference/types/field`), but you can create as many others
    as needed.

Creating the ClientIdTransformer
--------------------------------

Because the type handel's client ID's in a special format, the type needs
a :doc:`data_transformers` to transform the input back into
a regular integer.

.. code-block:: php
    :linenos:

    // src/Acme/Client/Search/DataTransformer/ClientIdTransformer.php

    namespace Acme\Client\Search\DataTransformer;

    use Rollerworks\Component\Search\DataTransformerInterface;
    use Rollerworks\Component\Search\Exception\TransformationFailedException;

    class ClientIdTransformer implements DataTransformerInterface
    {
        public function transform($value)
        {
            return sprintf('C%04d', $value);
        }

        public function reverseTransform($value)
        {
            if (null !== $value && !is_scalar($value)) {
                throw new TransformationFailedException('Expected a scalar.');
            }

            return (int) ltrim('C0');
        }
    }

You will learn more about data-transformers later on, but for now just
remember that ``reverseTransform`` always processes user-input. While
``transform`` transformers the model value to something ``reverseTransform``
can transformer. *Reverse-transform transforms towards a SearchCondition.*

Using the Field Type
--------------------

Now that the type is created, the SearchFactory needs a way to find it.

Fortunately, because this type has no constructor/setter dependencies, it
can be loaded using the Fully qualified class name (FQCN): ``Acme\Client\Search\Type\ClientIdType``.

So you can use it without having to register it anywhere, *as long as PHP
is able to load the class.*

Now ClientIdType can be used for any search field and/or field-type (as parent).

.. code-block:: php

    use Acme\Client\Search\Type\ClientIdType;
    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()->getSearchFactory();

    $fieldset = $searchFactory->createFieldSetBuilder()
        ->add('id', ClientIdType::class)
        // ...
        ->getFieldSet();

Registering the type
~~~~~~~~~~~~~~~~~~~~

When the type has constructor/setter dependencies you need to load it
using a ``SearchExtension``.

.. note::

    Framework integrations use a similar lazy loading system,
    see there implementation details for more information.

.. code-block:: php
    :linenos:

    // src/Acme/Client/Search/ClientExtension.php

    namespace Acme\Client\Search;

    use Rollerworks\Component\Search\AbstractExtension;

    class ClientExtension extends AbstractExtension
    {
        protected function loadTypes()
        {
            return array(
                new Type\ClientIdType(),
            );
        }
    }

And then register the extension in the system using the FactoryBuilder:

.. code-block:: php

    ...

    use Acme\Client\Search\ClientExtension;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new ClientExtension())
        ->getSearchFactory();

    /* ... */

    use Acme\Client\Search\ClientExtension\Type\ClientIdType;

    // Register type directly without using an search extension
    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addType(new ClientIdType())
        ->getSearchFactory();

.. note::

    For best performance it's advised to use a lazy loading
    SearchExtension like ``LazyExtension``:

    .. code-block:: php

        use Acme\Client\Search\Type\ClientIdType;
        use Rollerworks\Component\Search\Extension\LazyExtension;

        ...

        $searchFactory = new Searches::createSearchFactoryBuilder()
            ->addExtension(new LazyExtension([
                ClientIdType::class => function () {
                    return new ClientIdType();
                },
            ]))
            ->getSearchFactory();

Conclusion
----------

That's it, you now know the basics for creating custom field types,
but field types don't stop here. There's much more you can do.

Learn more about: :doc:`data_transformers`, :doc:`value_comparison`
:doc:`building_view` and how to :doc:`unit_testing`

Topics
------

.. toctree::
    :maxdepth: 2

    data_transformers
    value_comparison
    create_field_type_extension
    unit_testing

.. _`IntegerType`: https://github.com/rollerworks/RollerworksSearch/blob/master/src/Extension/Core/Type/IntegerType.php
