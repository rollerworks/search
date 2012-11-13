Bundle Overview
===============

Most features (and more) for searching records are provided by the bundle
using object-oriented PHP code as the interface.

In this chapter we will take a short tour of the various components, which put
together form the RecordFilter as a whole.You will learn key
terminology used throughout the rest of this book and you will gain a little
understanding of the classes you will work with as you integrate the RecordFilter
into your application.

This chapter is intended to prepare you for the information contained in the
subsequent chapters of this book.

The flow of the RecordFilter is to first accept input, format (validating/sanitizing)
and then use the formatted result for searching a storage engine - like a database.

Filter configuration specifies *what* can be filtered and *how* the system must handle it,
filtering preference defines the *actual filtering* conditions - what you are searching for.

System Requirements
-------------------

The basic requirements to use RecordFilter are.

* PHP 5.3.3 or higher, with the SPL extension (standard)

* Mbstring for multibyte text handling.

* The Symfony 2 FrameworkBundle

Depending on your needs you may need the following.

* International support when using Date/Time.

* Bcmath or GMP for handling big numbers.

* Database support when using Doctrine\Orm (or related).
  This requires Doctrine ORM to be installed.

Component Breakdown
-------------------

The RecordFilter is made up of many classes. Each of these classes can be grouped
into a general "component" group which describes the task it is designed to
perform.

We'll take a brief look at the components which form the RecordFilter in this
section of the book.

FieldSet
~~~~~~~~

The FieldSet class holds the filtering configuration of one or multiple FilterField objects.

*Internally, FieldSets are used for passing filtering configuration between components.*

A FilterField is independent of the FieldSet it is in, and contains the following information.

+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| Name            | Description                                                                                            | Value-type          |
+=================+========================================================================================================+=====================+
| Label           | Label of the field, this may be is empty when the field is only used for passing information.          | String              |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| Type            | Optional filtering type used by the *Formatter* for validation/normalisation etc.                      | null,string,object  |
|                 | The value of this is very dependent on the context it is used in.                                      |                     |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| Required        | Indicates if the field must have a value.                                                              | Boolean             |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| AcceptRanges    | Indicates the field accepts range values. The Filtering type must support this to work properly.       | Boolean             |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| AcceptCompares  | Indicates the field accepts comparison values. The Filtering type must support this to work properly.  | Boolean             |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+

    Secondly an Field can contain a property-reference to the class its mapped to.

.. note::

    FieldSets can be created 'on the fly' or created when warming up the cache.

    See :doc:`configuration` for more information.

Input
~~~~~

The input component provides the input to use for filtering,
**only fields present in the FieldSet are used**.

Filtering can be provided using a PHP Array, JSON, XML (todo) or the special :doc:`FilterQuery </input/filter_query>`.

Formatter
~~~~~~~~~

The formatter formats the given input by applying common operations like validation,
normalisation, etc.

The default Formatter (ModifierFormatter) works by performing registered
modifiers on the provided input.

    You can also add your own modifier(s), a modifier must implement the
    ``Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier\ModifierInterface``.

    And be registered in the Dependency Injection Container.

    For inspiration of creating your own Modifier, look at one of the modifiers provided by the bundle,
    and register it as Service tagged under "rollerworks_record_filter.formatter_modifier" with
    an priority (the lower the later its performed).

+-------------------+--------------------------------------------------------------------------------------------------------+-----------+
| Name              | Description                                                                                            | Priority  |
+===================+========================================================================================================+===========+
| Validator         | Validates and sanitizes the value by filtering type.                                                   | 1000      |
+-------------------+--------------------------------------------------------------------------------------------------------+-----------+
| DuplicateRemove   | Removes duplicated values.                                                                             | 500       |
+-------------------+--------------------------------------------------------------------------------------------------------+-----------+
| RangeNormalizer   | Removes overlapping ranges/values and merges connected ranges.                                         | 100       |
+-------------------+--------------------------------------------------------------------------------------------------------+-----------+
| ValuesToRange     | Converts a connected-list of values to ranges (filtering type must implement ValuesToRangeInterface).  | 80        |
+-------------------+--------------------------------------------------------------------------------------------------------+-----------+
| CompareNormalizer | Normalizes comparisons. Changes: '>=1, >1' to '>=1' (as '>' is already covert by '>=')                 | 50        |
+-------------------+--------------------------------------------------------------------------------------------------------+-----------+
| ValueOptimizer    | Optimizes value by OptimizableInterface filter-type implementation.                                    | -128      |
+-------------------+--------------------------------------------------------------------------------------------------------+-----------+

Type
~~~~

Filtering types for working with values, each type implements its own way
of handling a value including validation/sanitizing and possible optimizing.

For more information on using the Type component see :doc:`type`

Doctrine
~~~~~~~~

Searches trough the database using the final filtering-preference.
Both SQL and DQL are supported.

For more information on using the Doctrine component see :doc:`/Doctrine/index`

Factory
~~~~~~~

Factories can be used for creating classes based on FieldSets,
generated classes are faster then recreating structures every time.

The factories are meanly used for CacheWarming.
