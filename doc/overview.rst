Components Overview
===================

Most features for searching are provided by the library
using object-oriented PHP code as the interface.

In this chapter we will take a short tour of the various components, which put
together form RollerworksSearch as a whole. You will learn key
terminology used throughout the rest of this book and will gain an
understanding of the classes you will work with as you integrate
RollerworksSearch into your application.

This chapter is intended to prepare you for the information contained in the
subsequent chapters of this book.

Information flow
----------------

In most cases you accept and process the input, optimize it and then pass it to the
search storage layer. But its also possible to construct the search-condition yourself,
and pass it directly to the storage layer without any optimizing.

Note the following:

* Transforming view values to a normalized version is done when processing the input.
* The optimizing process tries to produce the smallest search-condition possible.

The only thing the system is mainly concerned with is the SearchCondition and
configuration of the search fields.

System Requirements
-------------------

The basic requirements to use RollerworksSearch are:

* PHP 5.3.3 or higher, with the SPL extension (standard)
* `Multibyte string extension <http://www.php.net/manual/en/mbstring.setup.php>`_, for multibyte text handling.
* `International <http://www.php.net/manual/en/book.intl.php>`_ support for transforming date-time values.

And a list 3rd party libraries (which you can find the installation chapter).

.. note::

    When you use Composer to install and update dependencies the
    installation of these libraries will be handled for your.

Component Breakdown
-------------------

RollerworksSearch is made up of many classes. Each of these classes can be grouped
into a general "component group" which describes the task it is designed to
perform.

We'll take a brief look at the components which form RollerworksSearch as a whole,
in this section of the book.

SearchCondition
~~~~~~~~~~~~~~~

Each search operation starts with a SearchCondition (``SearchConditionInterface``)
consisting of a ``ValuesGroup`` and ``FieldSet`` object.

At the root of each SearchCondition is a ``ValuesGroup`` object, containing
the values (as ``ValuesBag`` object) per field name and *optionally** subgroups
(each one being a ``ValuesGroup`` object).

A ``ValuesGroup`` is 'logically' marked as AND by default meaning that the search
condition will only be true if: from each field inside the group at least one value
is true (matching). But its also possible to mark a group as OR, which means that at
least one field must match and other fields are considered optional.

.. note::

    Subgroups are always threaded as AND to the head group they there in,
    but multiple groups within a group are OR cased to each other.

    Meaning that that at least one group must match.

A ``ValuesBag`` object holds all the values of a field per type.

Supported value-types are:

* Single value (any type of value)
* Excluded single value (any type of value which should not provide a positive match)
* Ranges (from - to, eg 10 - 100)
* Excluded ranges (from - to, eg 10 - 100 which is should not provide a positive match)
* Comparison value (mathematical comparison: <, >, >=, <=)
* PatternMatch (text based pattern matching, starts with, contains, ends with, regex) (and an excluding version)

Values are stored as a normalized model and view format.
The actual transformation is handled by the DataTransformers registered on the search field configuration.

.. note::

    Either side of a Range value can be marked as exclusive.
    Meaning anything between the values except the value it self.

    In practice this is the same as using ``>20 AND <30``.
    But much easier to optimize.

Normally the a SearchCondition is created when processing input.
But its also possible to build the SearchCondition manually using the
``SearchConditionBuilder`` see "Performing a manual search"
in :doc:`Performing searches </searches>` for more information.

FieldSet
~~~~~~~~

A ``FieldSet`` object holds the configuration of
one or multiple ``FieldConfigInterface`` instances.

.. tip::

    A ``FieldSet`` can also be created by using the ``FieldSetBuilder``,
    which provides a much simpler interface.

Each search field works independent from a FieldSet and may be reused in multiple FieldSets.
But the field name must be unique within the FieldSet.

Normally you`d create a FieldSet based on a subject-relationship.
For example invoice search, order search, news items search, etc.

.. note::

    The ``FieldConfigInterface`` is an interface for your own implementation.
    The default implementation is a ``SearchField`` object.

SearchField
~~~~~~~~~~~

+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| Property             | Description                                                                        | Value-type                      |
+======================+====================================================================================+=================================+
| Name                 | Name of the search field.                                                          | ``string``                      |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| Type                 | An object implementing the ``ResolvedFieldTypeInterface``.                         | ``ResolvedFieldTypeInterface``  |
|                      | Provides a field type class for building the fields configuration.                 |                                 |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| RangeSupport         | Indication if range values are accepted by the field.                              | ``boolean``                     |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| CompareSupport       | Indication if comparison values are accepted by the field.                         | ``boolean``                     |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| PatternMatchSupport  | Indication if pattern matcher values are supported by the field.                   | ``boolean``                     |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| Required             | Indicates if the field must have at least one value.                               | ``boolean``                     |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| ModelRefClass        | Model's fully qualified class-name reference.                                      | ``string``                      |
|                      | This is required for certain storage engines like Doctrine ORM.                    |                                 |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| ModelRefProperty     | Model's property name reference.                                                   | ``string``                      |
|                      | This is used in combination with ModelRefClass                                     |                                 |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| ValueComparison      | ValuesComparison object used for range validating and optimizing.                  | ``ValueComparisonInterface``    |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| ViewTransformers     | A list of transformers for transforming from view to normalized, and reverse.      | ``DataTransformerInterface[]``  |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| Options              | Configured options of the field. The options handled using the Type configuration. | ``array``                       |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+

Input
~~~~~

The input component processes user-input to a
``SearchConditionInterface`` object.

Input can be provided as a PHP Array, JSON, XML document, or with the easy to use
:doc:`FilterQuery </input/filter_query>` format.

Exporters
~~~~~~~~~

While the input component processes user-input to a ``SearchConditionInterface`` object.
The exporters do the opposite, transforming a SearchCondition to an exported
format. Ready to be reused for input processing.

Exporting a SearchCondition is very handy if you want to store the condition
on the client-side in either a cookie, URI query-parameter or hidden form input field.

Or if you need to perform a search operation on an external system that uses RollerworksSearch.
Build-up your SearchCondition using the :doc:`SearchConditionBuilder </searches>` and export it for usage!

FieldAliasResolver
~~~~~~~~~~~~~~~~~~

Sometimes you want to use a localized field-name rather then
the actual field-name.

For example: "factuur-nummer" (in Dutch) for "invoice-number" (original name).

For this you can use the FieldAliasResolver (``FieldAliasResolverInterface``)
which tries to resolve a field-alias to a real field-name.

RollerworksSearch comes bundled with three alias-resolver implementations:

* Noop: This resolver does nothing and simple returns the original input.
* Chain: This allows to chain multiple alias-resolvers, the first resolver that returns
  something else the original input is considered the matching resolver.
* Array: This resolver uses a simple PHP array for keeping track of aliases.

.. note::

    If the resolving process fails the originally provided field-name is used.

Condition Optimizers
~~~~~~~~~~~~~~~~~~~~

Condition optimizers optimize SearchConditions,
by removing duplicated values, normalizing overlapping
and redundant values/conditions.

The following optimizers come already pre-bundles with RollerworksSearch.

.. note::

    For the best result optimizers should be performed in correct order,
    therefore each optimizer has a priority between -10 and 10.

    The ``ChainOptimizer`` automatically performs the optimizers in
    there correct order.

+--------------------------+------------------------------------------------------------------------+----------+
| Name                     | Description                                                            | Priority |
+==========================+========================================================================+==========+
| ``ChainOptimizer``       | Runs the registered optimizers in sequence with correct the priority.  | 0        |
+--------------------------+------------------------------------------------------------------------+----------+
| ``DuplicateRemove``      | Removes duplicated values inside a condition group.                    | 5        |
+--------------------------+------------------------------------------------------------------------+----------+
| ``ValuesToRange``        | Converts incremented values to inclusive ranges.                       | 4        |
|                          | Example values 1,2,3,4,5 are converted to range 1-5                    |          |
+--------------------------+------------------------------------------------------------------------+----------+
| ``RangeOptimizer``       | Removes overlapping ranges/values and merges connected ranges.         | -5       |
+--------------------------+------------------------------------------------------------------------+----------+

Field Type
~~~~~~~~~~

Field types are used for configuring a search field's value comparison,
ViewTransformers and accepted value-types.

For more information on using field types see :doc:`type`

.. note::

    Build-in types are provided by the Core extension.

    You are free create your own field types for more advanced use-cases.
    See :doc:`cookbook/type/create_custom_field_type` for more information.
