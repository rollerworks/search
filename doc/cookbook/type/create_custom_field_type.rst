.. index::
   single: Field; Custom field type

How to Create a Custom Search Field Type
========================================

RollerworksSearch comes with a bunch of core field types available for
building FieldSets. However there are situations where you may want to
create a custom field type for a specific purpose. This recipe assumes
you need a field definition which holds specially formatted Client ID's,
based on the existing integer field. This section explains how the field
is defined, and how you can register it for usage in your application.

The Client ID begins with a 'C' prefix, and is prepended with zeros until it's
at least 4 digits.

Example: ``C30320, C0001, C442482, C0020``.

Defining the Field Type
-----------------------

In order to create the custom field type, first you have to create the class representing the type.
In this situation the class holding the field type will be called ClientIdType and the file will be
stored in the default location for search field types, which is ``<VendorName>\\Search\\Type``.
Make sure the field extends :class:`Rollerworks\\Component\\Search\\AbstractFieldType`:

.. code-block:: php
    :linenos:

    // src/Acme/Client/Search/Type/ClientIdType.php

    namespace Acme\Client\Search\Type;

    use Acme\Client\Search\DataTransformer\ClientIdTransformer;
    use Rollerworks\Component\Search\AbstractFieldType;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    class ClientIdType extends AbstractFieldType
    {
        public function buildType(FieldConfigInterface $config, array $options)
        {
            // The integer-type configures the field by setting a DataTransformer
            // to transformer localized input to an integer.
            //
            // Our custom type only accepts input in a special format,
            // so DataTransformers set by integer are no longer needed here.
            $config->resetViewTransformers();

            $config->addViewTransformer(new ClientIdTransformer());
        }

        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefaults(
                array(
                    'precision' => 0
                )
            );
        }

        public function getName()
        {
            return 'client_id';
        }

        public function getParent()
        {
            return 'integer';
        }
    }

.. tip::

    The location of this file is not important - the ``Search\\Type`` directory
    is just a convention.

Here, the return value of the ``getParent`` function indicates that you're
extending the ``integer`` field type. This means that, by default, you inherit
all of the logic and rendering of that field type. To see some of the logic,
check out the `IntegerType`_ class. There are three methods that are particularly
important:

``buildType()``
    Each field type has a ``buildType`` method, which is where you configure
    and build any field(s).

``buildView()``
    This method is used to set any extra variables you'll
    need when rendering your field in a template. For example, in `IntegerType`_,
    a ``precision`` variable is set and used in the template to set
    the ``precision`` attribute on the ``input`` field.

``configureOptions()``
    This defines options for your field type that can be used in ``buildType()``
    and ``buildView()``. There are a lot of options common to all fields
    (see :doc:`/reference/types/field`), but you can create as any others
    as needed.

Creating the ClientIdTransformer
--------------------------------

Because the type handel's client ID's in a special format, the type needs
a :doc:`data_transformers` to transform the input back
into a regular integer.

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

            return ltrim('C0');
        }
    }

Using the Field Type
--------------------

Now that the type is created, the Search system needs a way to find it.

This can be done in to ways;

You can choose to use your custom field type immediately, simply by creating a
new instance of the type in one of your FieldSets:

.. code-block:: php
    :linenos:

    use Acme\Client\Search\Type\ClientIdType;
    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()->getSearchFactory();

    $fieldset = $searchFactory->createFieldSetBuilder('clients')
        ->add('id', new ClientIdType())
        ->add('name', 'text')
        ->getFieldSet()
    ;

Or the by registering your field type in a ``SearchExtension``.

.. tip::

    Registering the type in a ``SearchExtension`` is the recommended way
    when you want to reuse the type in multiple FieldSets or when you
    need some additional parameters to the class constructor.

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

And then register it at system using the FactoryBuilder.

.. code-block:: php

    ...

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new ClientExtension())
        ->getSearchFactory();

Now that type can be used for any field by type name the corresponds with the value
returned by the ``getName`` method defined earlier.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()->getSearchFactory();

    $fieldset = $searchFactory->createFieldSetBuilder('clients')
        ->add('id', 'client_id')
        ->add('name', 'text')
        ->getFieldSet()
    ;

Further reading
---------------

Creating a field type is fun and easy, but did you know much more is possible
than what is shown here? Learn more at: :doc:`data_transformers` and
:doc:`value_comparison` and it is also a good idea to test your types:
:doc:`unit_testing`

.. _`IntegerType`: https://github.com/rollerworks/RollerworksSearch/blob/master/src/Extension/Core/Type/IntegerType.php
.. _`FieldType`: https://github.com/rollerworks/RollerworksSearch/blob/master/src/Extension/Core/Type/FieldType.php
