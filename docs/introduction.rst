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
But the actual condition is anything from a simple string, and is not limited to merely SQL.

.. tip::

    The final structure of a condition has no limits. You can compose
    any condition you want for the best possible search result.

    You can use single-values, ranges, comparisons and value matchers
    (starts/ends with or contains).

In further chapters you will learn how to produce a SearchCondition by
processing a user provided query, or by manually composing a SearchCondition
using the :class:`Rollerworks\\Component\\Search\\SearchConditionBuilder`.

* :doc:`input`;

* :doc:`composing_search_conditions`;

FieldSet
--------

A :class:`Rollerworks\\Component\\Search\\FieldSet` (FieldSet configuration)
holds a set of **SearchField**'s, and optionally a set-name (for internal usage).

The FieldSet helps the search system with processing the field's values and
transforming user-input to a workable format (eg. an ``DateTime`` object).

To create a FieldSet you can use the ``FieldSetBuilder`` or create a custom
:doc:`FieldSetConfigurator <creating_reusable_fieldsets>`.

.. note::

    Using a configurator is mandatory if you plan to serialize the condition,
    it provides a central point to (lazily) load FieldSet configurations.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Extension\Core\Type\TextType;
    use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;

    // ...

    $userFieldSet = $searchFactory->createFieldSetBuilder()
        ->add('id', IntegerType::class)
        ->add('username', TextType::class)
        ->add('firstName', TextType::class)
        ->add('lastName', TextType::class)
        ->getFieldSet();

.. _fieldset:

SearchField
-----------

A :class:`Rollerworks\\Component\\Search\\Field\\FieldConfig` consists
of a number of properties that are needed by various parts of the
search system for handling/processing field values.

While some of these configurations might seem a bit intimidating you don't really
need to know all the internals. In further chapters you will learn how to create
your own Field Type/Data transformers, etc.

So for now remember that a SearchField has a name, a type and some configuration.
You can see a SearchField as a form field configuration.

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

Input Processors
----------------

Input Processors transform the input to a ``SearchCondition``.
And ensure certain limits (either maximum number of values per field),
and value formatting constraints adhered.

Out of the box RollerworksSearch provides support for JSON and a special
user-friendly string-based input format.

Exporters
---------

While the input processors transform user-input to a SearchCondition.
The exporters do the opposite, transforming a SearchCondition to an exported
format, which can be process for their respective input processor.

Exporting a SearchCondition is very useful if you want to store the condition
on the client-side in either a cookie, URI query-parameter or hidden form input field.

Or if you plan to use RollerworksSearch as a client-side SDK.

SearchFactory
-------------

The SearchFactory forms the heart of the search system, it provides easy
access to various builders, loaders, and the :doc:`SearchConditionSerializer <serializer>`.

.. tip::

    Provided Framework integrations already configure the SearchFactory
    for you. And allow to plug-in additional extensions and field types.

    Otherwise you would rather want to use the :class:`Rollerworks\\Component\\Search\\Searches`
    class which takes care of all the boilerplate of setting up a SearchFactory.

Further reading
---------------

Now that you know the basic terms and conventions it's time to get started.
Note that some extensions are provided separate while there documentation is
kept within this manual.

Depending on your usage there are a number of dedicated chapters that help you
with integrating RollerworksSearch.

First make sure you :doc:`install <installing>` RollerworksSearch, and any extensions
you wish to use.

* :doc:`Processing search queries <processing_searches>`
* :doc:`composing_search_conditions`
* :doc:`Symfony Framework integration <integration/symfony_bundle>`
* :doc:`Using ElasticSearch with Elastica <integration/elastic_search>`
* :doc:`Doctrine DBAL/ORM integration <integration/doctrine/index>`
