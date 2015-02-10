.. index::
   single: Type; Field type extension

How to Create a Field Type Extension
====================================

:doc:`Custom search field types <create_custom_field_type>` are great when
you need field types with a specific purpose, such as a gender selector,
or a VAT number input.

But sometimes, you don't really need to add new field types - you want
to add features on top of existing types. This is where field type
extensions come in.

Field type extensions have 2 main use-cases:

#. You want to add a **generic feature to several types** (such as
   adding a "help" text to every field type);
#. You want to add a **specific feature to a single type** (such
   as adding a user friendly date picker feature to the "date" field type).

In both those cases, it might be possible to achieve your goal with custom
field rendering, or custom search field types. But using field type extensions
can be cleaner (by limiting the amount of business logic in templates)
and more flexible (you can add several type extensions to a single field
type).

Field type extensions can achieve most of what custom field types can do,
but instead of being field types of their own, **they plug into existing types**.

Imagine you want to add a range selector to the ``Number`` type,
but this requires a min and max value to select from, and maybe a step value.

You could of course do this by customizing how this field is rendered in a
template. But field type extensions allow you to do this in a nice DRY fashion.

Defining the Field Type Extension
---------------------------------

Your first task will be to create the field type extension class (called ``RangeTypeExtension``
in this article). By standard, field extensions usually live in the ``Search\Type\Extension``
directory of one of your applications/libraries.

When creating a field type extension, you can either implement the
:class:`Rollerworks\\Component\\Search\\FieldTypeExtensionInterface` interface
or extend the :class:`Rollerworks\\Component\\Search\\AbstractFieldTypeExtension`
class. In most cases, it's easier to extend the abstract class::

    // src/Acme/Client/Search/Type/Extension/RangeTypeExtension.php
    namespace Acme\Client\Search\Type\Extension;

    use Rollerworks\Component\Search\AbstractFieldTypeExtension;

    class RangeTypeExtension extends AbstractFieldTypeExtension
    {
        /**
         * Returns the name of the type being extended.
         *
         * @return string The name of the type being extended
         */
        public function getExtendedType()
        {
            return 'file';
        }
    }

The only method you **must** implement is the ``getExtendedType`` function.
It is used to indicate the name of the field type that will be extended
by your extension.

.. tip::

    The value you return in the ``getExtendedType`` method corresponds
    to the value returned by the ``getName`` method in the field type class
    you wish to extend.

In addition to the ``getExtendedType`` function, you will probably want
to override one of the following methods:

* ``buildType()``

* ``buildView()``

* ``configureOptions()``

For more information on what those methods do, you can refer to the
:doc:`Creating Custom Field Types </cookbook/field/create_custom_field_type>`
cookbook article.

Adding the extension Business Logic
-----------------------------------

The goal of your extension is to display a range slider instead of an input
field. As most JavaScript libraries already provide a simple way to trigger
the rendering the extension only needs to pass the options to view for usage.

The template rendering the view can then use the configured options to
trigger the rendering done by JavaScript.

Your field type extension class will need to do two things in order to extend
the ``number`` field type:

#. Override the ``configureOptions`` method in order to add the ``min_value``,
   ``max_value`` and ``step_value`` options;
#. Override the ```buildView`` method in order to pass the options to the view.

.. code-block:: php
    :linenos:

    // src/Acme/Client/Search/Type/Extension/RangeTypeExtension.php
    namespace Acme\Client\Search\Type\Extension;

    use Rollerworks\Component\Search\AbstractFieldTypeExtension;
    use Rollerworks\Component\Search\SearchFieldView;
    use Rollerworks\Component\Search\FieldConfigInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    class RangeTypeExtension extends AbstractFieldTypeExtension
    {
        /**
         * Returns the name of the type being extended.
         *
         * @return string The name of the type being extended
         */
        public function getExtendedType()
        {
            return 'number';
        }

        /**
         * Add the min_value, max_value, step_value options.
         *
         * @param OptionsResolver $resolver
         */
        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setOptional(array('min_value', 'max_value', 'step_value'));
        }

        /**
         * Pass the image options to the view.
         *
         * @param SearchFieldView      $view
         * @param FieldConfigInterface $field
         * @param array                $options
         */
        public function buildView(SearchFieldView $view, FieldConfigInterface $field, array $options)
        {
            foreach (array('min_value', 'max_value', 'step_value') as $key) {
                if (array_key_exists($key, $options)) {
                    $view->vars[$key] = $options[$key];
                }
            }
        }
    }

Using the Field Type
--------------------

Now the type extension is created, the Search system needs to know it exists,
just like field types this can be done in to ways;

You can choose to register the extension using the ``FactoryBuilder``

.. code-block:: php
    :linenos:

    use Acme\Client\Search\Type\Extension\RangeTypeExtension;
    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addTypeExtension(new RangeTypeExtension())
        ->getSearchFactory()
    ;

Or the by registering the type in a ``SearchExtension``.

.. code-block:: php
    :linenos:

    // src/Acme/Client/Search/ClientExtension.php

    namespace Acme\Client\Search;

    use Rollerworks\Component\Search\AbstractExtension;

    class ClientExtension extends AbstractExtension
    {
        protected function loadTypeExtensions()
        {
            return array(
                new Type\Extension\RangeTypeExtension(),
            );
        }
    }

And then register it at system using the FactoryBuilder.

.. code-block:: php

    /* ... */

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new ClientExtension())
        ->getSearchFactory();

Now that type can be used for any field by type name the corresponds with the value
returned by the ``getName`` method defined earlier.

From now on, when adding a field of type ``number`` in your field, you can
specify the ``min_value``, ``max_value`` and ``step_value`` options that
will be used to display an the range selector. For example::

    /* ... */

    $fieldset = $searchFactory->createFieldSetBuilder('products')
        ->add('name', 'text')
        ->add('size', 'number', 'min_value' => 1, 'max_value' => 100)
        ->getFieldSet()
    ;
