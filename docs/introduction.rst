Introduction
============

In this chapter we'll take a short tour of the various components, which put
together provide the RollerworksSearch system. You will learn key terminology
used throughout the rest of this manual and gain an understanding of the
classes you are going to work with.

.. tip::

    RollerworksSearch can be used as both a processing library for search requests.
    And as a software client to compose search-conditions that will be provided to
    a third-party (web) API, that uses RollerworksSearch for processing.

    Some functionality is provided by extensions that you need to install
    in addition to the RollerworksSearch core library.

    See the :doc:`installation <installing>` chapter for more details.

Code Samples
------------

Code examples are used throughout the manual to clarify what is written in text.
They will sometimes be usable as-is, but they should always be taken as
outline/pseudo code only.

A code sample will look like this:

.. code-block:: php

    class AClass
    {
        ...
    }

    // A Comment
    $obj = new AClass($arg1, $arg2, ... );

    /* A note about another way of doing something
    $obj = AClass::newInstance($arg1, $arg2, ... );
    */

The presence of 3 dots ``...`` in a code sample indicates that code has been excluded,
for brevity. They are not actually part of the code.

Multi-line comments are displayed as ``/* ... */`` and show alternative ways
of achieving the same result.

You should read the code examples given and try to understand them. They are
kept concise so that you are not overwhelmed with information.

SearchCondition
---------------

Each search operation starts with a SearchCondition.

A SearchCondition consists of a set of requirements (the condition,
kept as a structured object-graph), and a **FieldSet** configuration.

*You can think of a condition as an (SQL) query condition ``WHERE (field = 'value')``.*
But the actual condition is anything a simple string, and is not limited to merely SQL.

.. tip::

    The final structure of a condition has no limits. You can compose
    any condition you want for the best possible search result.

    You can use single-values, ranges, comparisons and value matchers
    (starts/ends with or contains).

In further chapters you will learn how to compose a SearchCondition.

* :doc:`composing_search_conditions`;

* :doc:`input`;

FieldSet
--------

A :class:`Rollerworks\\Component\\Search\\FieldSet` (FieldSet configuration)
holds a set of **SearchField**'s, and optionally a set-name (for internal usage).

The FieldSet helps the search system with processing the field's values and
transforming user-input to a workable format (eg. an ``DateTime`` object).

To create a FieldSet you can use the ``FieldSetBuilder`` or create a custom
``FieldSetConfigurator``.

.. tip::

    Technically there is no difference between a FieldSet builder or a Configurator,
    except that a configurator is re-usable and helps with keeping your code organized.

    In practice a configurator uses a FieldSetBuilder.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Extension\Core\Type\TextType;
    use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;

    $userFieldSet = $searchFactory->createFieldSetBuilder()
        ->add('id', IntegerType::class)
        ->add('username', TextType::class)
        ->add('firstName', TextType::class)
        ->add('lastName', TextType::class)
        ->getFieldSet();

SearchField
-----------

A :class:`Rollerworks\\Component\\Search\\Field\\FieldConfig` consists of a number
of properties that are needed by various parts of the search system for
handling/processing field values.

While some of these configurations might seem a bit intimidating you don't really
need to know all the internals. In further chapters you will learn how to create
your own Field Type/Data transformers, etc.

So for now remember that a SearchField has a name, a type and some configuration.
You can see a SearchField as a form field.

.. note::

    The field's name must be unique within a FieldSet, registering the field
    twice will overwrite the previous one.

Field Type
~~~~~~~~~~

Field types are used for configuring SearchFields using reusable types
that make extensions as advanced as possible and reducing the amount of code
you have to duplicate.

You don't extend a Field type by extending the PHP class, but by using
an advanced field building system. Each type can have multiple extensions.

.. note::

    Build-in types are provided by the CoreExtension.

    You are free create your own field types for more advanced use-cases.
    See :doc:`cookbook/type/index` for more information.

FieldSetConfigurator
--------------------

A FieldSetConfigurator helps with making FieldSet's reusable and keeping your FieldSet
configurations in a logical place. Each configurator holds the configuration for single
FieldSet.

.. code-block:: php

    namespace Acme\Search\FieldSet;

    use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
    use Rollerworks\Component\Search\FieldSetBuilder;
    use Rollerworks\Component\Search\FieldSetConfigurator;

    final class UserFieldSet implements FieldSetConfigurator
    {
        public function buildFieldSet(FieldSetBuilder $builder)
        {
            $builder->add('id', Type\IntegerType::class);
            $builder->add('name', Type\TextType::class);
        }
    }

Loading a FieldSetConfigurator is done by referencing the fully qualified
class-name (FQCN) (eg. ``Acme\Search\FieldSet\UserFieldSet``).

.. tip::

    A Configurator is automatically initialized on first usage, if your
    configurator has external dependencies you can use a `PSR-11`_
    compatible Container to lazily load configurators.

    See :doc:`creating_reusable_fieldsets` for usage.

Input Processors
----------------

While composing a new **SearchCondition** object isn't hard, you properly want
want to *provide* the condition in a more user-friendly format.

Instead of doing this yourself RollerworksSearch comes pre-bundled with various
:doc:`input processors <input>` which transform the user-input into a ready-to-use
SearchCondition.

Exporters
---------

While the input component processes user-input to a SearchCondition.
The exporters do the opposite, transforming a SearchCondition to an exported
format. Ready for input processing.

Exporting a SearchCondition is very useful if you want to store the condition
on the client-side in either a cookie, URI query-parameter or hidden form input field.

Or if you need to perform a search operation on an external system that uses
RollerworksSearch.


SearchFactory
-------------

The SearchFactory forms the heart of the search system, it provides
easy access to builders, the (default) condition optimizer, and the
SearchConditionSerializer.

.. tip::

    Provided Framework integrations already configure the SearchFactory
    for you. And allow to plug-in additional extensions and field types.

    Otherwise you would rather want to use the :class:`Rollerworks\\Component\\Search\\Searches`
    class which takes care of all the boilerplate of setting up a SearchFactory.

SearchConditionSerializer
-------------------------

The :class:`Rollerworks\\Component\\Search\\SearchConditionSerializer`
class helps with (un)serializing a ``SearchCondition``.

A SearchCondition holds a condition and a FieldSet configuration.

The condition and it's values can be directly serialized, but the FieldSet is
more difficult. As a Field can have closures and/or resource reference's, it's
to complex to serialize.

Instead of serializing the FieldSet the serializer stores the FieldSet set-name,
and when unserializing it loads the FieldSet using a :class:`Rollerworks\\Component\\Search\\FieldSetRegistry`.

.. note::

    The Serializer doesn't check if the FieldSet is actually loadable
    by the FieldSetRegistry. You must ensure the FieldSet is loadable,
    else when unserializing you get an exception.

.. caution::

    Suffice to say, never store a serialized SearchCondition in the client-side!
    The Serializer still uses the PHP serialize/unserialize functions, and due to
    unpredictable values can't provide a list of trusted classes.

    Use an Exporter to store a SearchCondition in an untrusted storage.

FieldSetRegistry
----------------

A FieldSetRegistry (:class:`Rollerworks\\Component\\Search\\FieldSetRegistry`)
allows to load a FieldSet from a registry.

The :class:`Rollerworks\\Component\\Search\\LazyFieldSetRegistry` allows
to load a FieldSet using the FQCN of a FieldSetConfigurator or by using
a `PSR-11`_ compatible container.

The FieldSetRegistry is amongst used when unserializing a serialized SearchCondition,
so that you don't have to inject the FieldSet explicitly.

Further reading
---------------

Now that you know the basic terms and conventions it's time to get started.
Note that some extensions are provided separate while there documentation is
kept within this manual.

Depending on your usage there are a number of dedicated chapters that help you
with integrating RollerworksSearch.

First make sure you :doc:`install <installing>` RollerworksSearch, and any extensions
you wish to use.

* :doc:`Using the SearchProcessor <processing_searches>`
* :doc:`composing_search_conditions`
* :doc:`Symfony Framework integration <integration/symfony_bundle>`
* :doc:`Using ElasticSearch with Elastica <integration/elastic_search>` (coming soon)
* :doc:`Doctrine DBAL/ORM integration <integration/doctrine/index>`

.. _`PSR-11`: http://www.php-fig.org/psr/psr-11/
