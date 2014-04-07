Components Overview
===================

Most features for searching are provided by the library
using object-oriented PHP code as the interface.

In this chapter we will take a short tour of the various components, which put
together form the Search Component as a whole. You will learn key
terminology used throughout the rest of this book and will gain an
understanding of the classes you will work with as you integrate the Search Component
into your application.

This chapter is intended to prepare you for the information contained in the
subsequent chapters of this book.

Information flow
~~~~~~~~~~~~~~~~

Normally you'd accept the input, format it and then pass
it to the storage layer. The formatting ensures all
values are validated and normalized.

But you're free to build the search condition yourself,
and pass it directly to the storage layer without any formatting.

The only thing the system is mainly concerned with is the search condition, and
the configuration of the search fields.

System Requirements
-------------------

The basic requirements to use the Search Component are:

* PHP 5.3.3 or higher, with the SPL extension (standard)
* `Multibyte string extension <http://www.php.net/manual/en/mbstring.setup.php>`_, for multibyte text handling

And a list 3rd party libraries (which you can find the installation chapter).

.. note::

    When you use Composer to install and update dependencies the
    installation of these libraries will be handled for your.

Depending on your needs, you may need the following:

* `International <http://www.php.net/manual/en/book.intl.php>`_ support when using Date/Time
* `BCMath <http://php.net/manual/en/book.bc.php>`_ or `GNU Multiple Precision (GMP) <http://php.net/manual/en/book.gmp.php>`_ for handling big numbers
* `Doctrine ORM <http://www.doctrine-project.org/projects/orm.html>`_ for database support

Component Breakdown
-------------------

The Search Component is made up of many classes. Each of these classes can be grouped
into a general "component" group which describes the task it is designed to
perform.

We'll take a brief look at the components which form the Search Component as a whole,
in this section of the book.

ValuesGroup and ValuesBag
~~~~~~~~~~~~~~~~~~~~~~~~~

At the root of each search there is at least one ``ValuesGroup`` object, containing
the field names with there values (as a ``ValuesBag`` object), and optionally subgroups
(each one being a ``ValuesGroup`` object).

A ``ValuesGroup`` is 'logically' marked as AND by default, meaning that the search
condition will only be true if from each field inside the group a value is true (matching).
But it is however possible to mark a group as OR, meaning that at least one field must match and
other fields are considered optional.

.. note::

    Subgroups are always threaded as AND to the group there in, but are OR cased to
    each other.

The ``ValuesBag`` object holds all the values per type of a field.

Supported value-types are:

* Single value (any type of value)
* Excluded single value (any type of value which should not provide a positive match)
* Ranges (from - to, eg 10 - 100)
* Excluded ranges (from - to, eg 10 - 100 which is should not provide a positive match)
* Comparison value (mathematical comparison, < > >= <=)
* PatternMatch (starts with, contains, ends with, regex) (and an excluding version)

Values are stored as a normalized format en view format.
The actual transformation is handled by the ```TransformerFormatter``.

.. note::

    Either side of a Range value can be marked as exclusive.
    Meaning anything between the values except the number it self.

    In practice this is the same as using ``>20 AND <30``.
    Except that explicit ranges are much easier to optimize.

FieldSet
~~~~~~~~

The ``FieldSet`` class holds the filtering configuration of
one or multiple ``FieldConfigInterface`` instances.

Normally you`d create a fieldset based on a subject-relationship.

For example invoice search, order search, news items search, etc.

*Internally, FieldSets are used for passing filtering configuration between components.*

.. note::

    The ``FieldConfigInterface`` is an interface for your own implementation.
    The default implementation is the ``SearchField`` class.

A field is independent of the ``FieldSet`` and provides the following information.

+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| Name             | Description                                                                           | Value-type                      |
+==================+=======================================================================================+=================================+
| Name             | Name of the field. must be unique inside the fieldset.                                | ``string``                      |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| Type             | An object implementing the ``ResolvedFieldTypeInterface``.                            | ``ResolvedFieldTypeInterface``  |
|                  | Provides type-class for building the fields configuration.                            |                                 |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| AcceptRanges     | Indication if range values are accepted by the field.                                 | ``boolean``                     |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| AcceptCompares   | Indication if comparison values are accepted by the field.                            | ``boolean``                     |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| Required         | Indicates if the field must have at least one value.                                  | ``boolean``                     |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| ModelRefClass    | Model's fully qualified class-name reference.                                         | ``string``                      |
|                  | This is required for some storage engines like Doctrine2                              |                                 |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| ModelRefProperty | Model's property name reference.                                                      | ``string``                      |
|                  | This is used in combination with ModelRefClass                                        |                                 |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| ValueComparison  | ValuesComparison object used for validating and optimizing.                           | ``ValueComparisonInterface``    |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| ViewTransformers | A list of transformers for transforming from view to normalized, and reverse.         | ``DataTransformerInterface[]``  |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+
| Options          | Configured options of the field. The options handled using the Type configuration.    | ``array``                       |
+------------------+---------------------------------------------------------------------------------------+---------------------------------+

.. note::

    A ``FieldSet`` can also be generated by using the ``FieldSetBuilder``,
    which provides a much simpler interface then the low lever architecture.

Input
~~~~~

The input component process user-input to a ``SearchConditionInterface`` object.

Input can be provided as a PHP Array, JSON, XML, or using the
special :doc:`FilterQuery </input/filter_query>` format.

Formatter
~~~~~~~~~

A formatter formats the given SearchCondition,
this can include validating, transforming, optimizing, etc.

The following formatters are provided with the library.

.. note::

    Formatters are listed in order of usage.
    Transformation should take place before validating, and validating before optimizing.

+--------------------------+---------------------------------------------------------------------------+
| Name                     | Description                                                               |
+==========================+===========================================================================+
| ``Chain``                | Performs the registered formatters in the sequence they were registered.  |
+--------------------------+---------------------------------------------------------------------------+
| ``TransformerFormatter`` | Transforms the values to a normalized format and view format.             |
+--------------------------+---------------------------------------------------------------------------+
| ``ValidatorFormatter``   | Validates values using the configured validation constraints.             |
|                          | This formatter is provided by the Validator extension.                    |
+--------------------------+---------------------------------------------------------------------------+
| ``DuplicateRemove``      | Removes duplicated values inside group.                                   |
+--------------------------+---------------------------------------------------------------------------+
| ``ValuesToRange``        | Converts incremented values to inclusive ranges.                          |
+--------------------------+---------------------------------------------------------------------------+
| ``RangeOptimizer``       | Removes overlapping ranges/values and merges connected ranges.            |
+--------------------------+---------------------------------------------------------------------------+

Type
~~~~

Types are used for configuring the field, including setting the value comparison implementation,
ViewTransformers and accepted value-types.

For more information on using the Type component see :doc:`type/index`

.. note::

    Build-in types are provided as extension by the Core extension.
    You are free to extend them for more advanced support.

    Extending a type if described in :doc:`type/extending`

Doctrine
~~~~~~~~

Doctrine2 drivers for searching in the storage.

Currently only provides support for Doctrine2 ORM (both DQL and NativeSQL) and DBAL.

For more information on using the Doctrine component see :doc:`/doctrine/index`
