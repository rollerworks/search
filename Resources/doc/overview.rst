Bundle Overview
===============

Most features (and more) for searching records are provided by the bundle
using object-oriented PHP code as the interface.

In this chapter we will take a short tour of the various components, which put
together form the RecordFilter as a whole. You will learn key
terminology used throughout the rest of this book and you will gain a little
understanding of the classes you will work with as you integrate the RecordFilter
into your application.

This chapter is intended to prepare you for the information contained in the
subsequent chapters of this book.

The flow of the RecordFilter is to first accept input,
format (validating/sanitizing) and then using the formatted result
for searching the storage engine like an database.

Filter configuration specifies *what* can be filtered,
filtering preference defines *the actual filtering* conditions you want.

System Requirements
-------------------

The basic requirements to use RecordFilter are.

* PHP 5.3.3 or higher, with the SPL extension (standard)

* Mbstring for multibyte text handling.

* The Symfony FrameworkBundle

Depending on your needs you may need the following.

* International support when using Date/Time.

* Bcmath or GMP for handling big numbers.

* Database support when using Record\Sql.
  This also requires Doctrine ORM to be installed.

Component Breakdown
-------------------

The RecordFilter is made up of many classes. Each of these classes can be grouped
into a general "component" group which describes the task it is designed to
perform.

We'll take a brief look at the components which form RecordFilter in this
section of the book.

FieldSet
~~~~~~~~

The FieldSet class holds the filtering configuration of one or multiple FilterField.

*Internally are FieldSets used for passing filtering configuration between components.*

    An FilterField is independent of an FieldSet it is in, and contains of the following information.

+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| Name            | Description                                                                                            | Value-type          |
+=================+========================================================================================================+=====================+
| Label           | Label of the field, this is empty when the field is only used for passing information.                 | String              |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| Type            | Optional filtering type used by the *Formatter* for validation/normalisation etc.                      | null,string,object  |
|                 | The value of this is very dependent on the context it is used in.                                      |                     |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| Required        | Indicates if the field must have an value.                                                             | Boolean             |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| AcceptRanges    | Indicates the field accepts range values. The Filtering type must support this to work properly.       | Boolean             |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+
| AcceptCompares  | Indicates the field accepts comparison values. The Filtering type must support this to work properly.  | Boolean             |
+-----------------+--------------------------------------------------------------------------------------------------------+---------------------+

    Secondly an Field can contain an property-reference for when using ORM based query-building.

An FieldSet can be created on the fly or created when warming up the cache.

See the :doc:`configuration` section for more information.

Input
~~~~~

The input component provides the input to use for filtering,
only fields present in the FieldSet will be used.

Filtering can be provided using an PHP Array or the special *FilterQuery language*.

For more information the FilterQuery language see :doc:`/input/filter_query`

Formatter
~~~~~~~~~

An formatter formats the given input by applying common operations like validation,
normalisation, etc.

The default Formatter (ModifierFormatter) works by performing registered modifiers on the input.

An modifier must implement the
``Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier\ModifierInterface``.

For inspiration of creating your own Modifier look at one of the modifiers provided by the bundle,
and register it as Service tagged "rollerworks_record_filter.formatter_modifier"
an priority (the lower the later its performed).

List provided of modifiers.

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
| CompareNormalizer | Normalizes comparisons. Changes: >=1, >1 to >=1 (as > is already covert)                               | 50        |
+-------------------+--------------------------------------------------------------------------------------------------------+-----------+
| ValueOptimizer    | Optimizes value by OptimizableInterface filter-type implementation.                                    | -128      |
+-------------------+--------------------------------------------------------------------------------------------------------+-----------+

Record
~~~~~~

Searches trough the database using the final filtering-preference.

For SQL to work, Doctrine ORM must be installed.
Both SQL and DQL are supported.

For more information on using the Record component see :doc:`/record/where_builder`

Type
~~~~

Filtering types for working with values,
each type implements its own way of handling a value including validation/sanitizing
and possible optimizing.

For more information on using the Record component see :doc:`type`

Factory
~~~~~~~

Factories can be used for creating classes based on FieldSets that are faster
then recreating structures on every call.

The factories are meanly used for CacheWarming.
