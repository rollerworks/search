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

In most cases you accept and process the input, optimize it and then pass it to
a condition processor in the search storage layer. But its also possible to
construct the search-condition yourself, and pass it directly to the condition
processor without any optimizing.

Note the following:

* Transforming view values to a normalized version is done when processing the input.
* The optimizing process tries to produce the smallest search-condition possible,
  but is only able to do this when the system is properly configured.

The system is mainly concerned with the SearchCondition and configuration
of the search fields.

System Requirements
-------------------

The basic requirements to use RollerworksSearch are:

* PHP 5.3.3 or higher, with the SPL extension (standard)
* `Multibyte string extension <http://www.php.net/manual/en/mbstring.setup.php>`_, for multibyte text handling.
* `International <http://www.php.net/manual/en/book.intl.php>`_ support for transforming date-time values.

And a list 3rd party libraries (which you can find the installation chapter).

.. tip::

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

Each search operation starts with a SearchCondition (``SearchConditionInterface``).
A SearchCondition defines a set of requirements (conditions) for one or
more fields. And holds the configuration of these fields within in a ``FieldSet``.

A field (search field) can be compared to a form field or database column.

.. code-block:: php

    use Rollerworks\Component\Search\FieldSet;
    use Rollerworks\Component\Search\ValuesGroup;
    use Rollerworks\Component\Search\ValuesBag;
    use Rollerworks\Component\Search\Value\SingleValue;

    $fieldSet = new FieldSet('my_field_set');
    // FieldSet configuration...

    $rootValuesGroup = new ValuesGroup();

    $fieldId = new ValuesBag();
    $fieldId->addSingleValue(new SingleValue(10));
    $fieldId->addSingleValue(new SingleValue(20));
    $rootValuesGroup->addField('id', $fieldId);

    $fieldDate = new ValuesBag();
    $fieldDate->addSingleValue(
        new SingleValue(
            new \DateTime('2015-02-04 00:00:00', new \DateTimezone('UTC')) // normalized value
            '2015/04/24' // View in US date notation
        )
    );

    $subValuesGroup = new ValuesGroup();

    $fieldId = new ValuesBag();
    $fieldId->addSingleValue(new SingleValue(10));
    $fieldId->addSingleValue(new SingleValue(20));

    $subValuesGroup->addField('id', $fieldId);
    $rootValuesGroup->addGroup($subValuesGroup);

    $searchCondition = new SearchCondition($fieldSet, $rootValuesGroup);

At the root of each SearchCondition is a ``ValuesGroup``, containing
the values (as ``ValuesBag``) per field name and *optionally* subgroups
(each one being a ``ValuesGroup``).

A ``ValuesGroup`` is defined with a ``ValuesGroup::GROUP_LOGICAL_AND`` by default
which requires from each field inside a (sub)group that at least one of the field's
values evaluate to true (is matching).

A record is intended as a database record with one or fields. A single User with
an id, registration-date and username is considered one record.

Taken you will only get a result when the id is e.g. 10 or 20 **and** the
date is "2015-02-04". If date is anything else then "2015-02-04" the record
is not matching.

But it's also possible to set a group with ``ValuesGroup::GROUP_LOGICAL_OR``,
which removes the requirement that *all** fields must match. If any of the fields
matches the record is considered matching.

.. note::

    Subgroups function as a list of fields with ``ValuesGroup::GROUP_LOGICAL_AND``
    even when the parent group is set with  ``ValuesGroup::GROUP_LOGICAL_OR``.

    Only when fields within the parent group gave a positive match the subgroup
    will be evaluated.

    When there are multiple subgroups these are OR'ed to each other,
    Meaning at least one (or more) subgroup(s) in the group must match.

Learn more about creating conditions at :doc:`searching_in_practice`.

A ``ValuesBag`` holds all the values of a field per type.

Supported value-types are:

* Single value (any type of value)
* Excluded single value (any type of value which should not provide a positive match)
* Ranges (from - to, e.g. 10 - 100)
* Excluded ranges (from - to, e.g. 10 - 100 which is should not provide a positive match)
* Comparison value (mathematical comparison: <, >, >=, <=)
* PatternMatch (text based pattern matching, starts with, contains, ends with, regex),
  and supports excluding (e.g. not starts with) and case optional insensitive.

Values are stored in a normalized and view format. The actual transformation is
handled by the DataTransformers registered on the search field configuration.

.. tip::

    Either side of a Range value can be marked as exclusive.
    Meaning anything between the values except the values them self.

    In practice this is the same as using ``>20 AND <30``.
    But much easier to optimize.

Normally a ``SearchCondition`` is created when processing input. But you can also build
the ``SearchCondition`` manually using the :class:``Rollerworks\\Component\\Search\\SearchConditionBuilder``
see :ref:`Performing a manual search <do_manual_search>` for more information.

FieldSet
~~~~~~~~

A :class:``Rollerworks\\Component\\Search\\FieldSet`` holds the configuration
of one or multiple ``FieldConfigInterface`` instances, each field is called a
search field.

.. tip::

    A ``FieldSet`` can also be created by using the ``FieldSetBuilder``,
    which provides a much simpler interface.

Each search field works independent from a FieldSet and may be reused in multiple FieldSets.
But the field's name must be unique within the FieldSet.

Normally you would create a FieldSet based on a subject-relationship.
For example invoice search, order search, news items search, etc.

.. note::

    The ``FieldConfigInterface`` is a public interface for your own implementation.
    The default implementation is a ``SearchField`` object.

SearchField
~~~~~~~~~~~

...

+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| Property             | Description                                                                        | Value-type                      |
+======================+====================================================================================+=================================+
| Name                 | Name of the search field.                                                          | ``string``                      |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| Type                 | An object implementing the ``ResolvedFieldTypeInterface``.                         | ``ResolvedFieldTypeInterface``  |
|                      | Provides a field type class for building the fields configuration.                 |                                 |
+----------------------+------------------------------------------------------------------------------------+---------------------------------+
| SupportValueType     | Indication which value-types are accepted by the field.                            | ``boolean``                     |
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

The input component processes user-input to a ``SearchCondition``.

Input can be provided as a PHP Array, JSON, XML document, or using the
:doc:`FilterQuery </input/filter_query>` format.

Exporters
~~~~~~~~~

While the input component processes user-input to a SearchCondition.
The exporters do the opposite, transforming a SearchCondition to an exported
format. Ready to be reused for input processing.

Exporting a SearchCondition is very handy if you want to store the condition
on the client-side in either a cookie, URI query-parameter or hidden form input field.

Or if you need to perform a search operation on an external system that uses RollerworksSearch.
Build-up your SearchCondition using the :doc:`SearchConditionBuilder </searches>` and export
it for usage!

FieldAliasResolver
~~~~~~~~~~~~~~~~~~

Sometimes you want to use a localized field-name rather then
the actual field-name.

For example: "factuur-nummer" (in Dutch) for "invoice-number" (original name).

For this you can use the FieldAliasResolver (``FieldAliasResolverInterface``)
which tries to resolve a field-alias to a real field-name.

RollerworksSearch comes bundled with three alias-resolvers:

* Noop: This resolver does nothing and simple returns the original input.
* Chain: This allows to chain multiple alias-resolvers, the first resolver
  which returns something else than the original input is considered the
  matching resolver.
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

SearchFactory
~~~~~~~~~~~~~

The SearchFactory forms the heart of the search system, it provides
easy access to builders and keeps track of field types.

But you would rather want to use the :class:`Rollerworks\\Component\\Search\\Searches`
class which takes care of all the boilerplate of setting up a SearchFactory.
See :doc:`searches` for information and usage.

SearchConditionSerializer
~~~~~~~~~~~~~~~~~~~~~~~~~

The :class:`Rollerworks\\Component\\Search\\SearchConditionSerializer`
class functions as a helper for serializing a ``SearchCondition``.

A SearchCondition holds a ValuesGroup (with nested ValuesBags and optionally
other nested ValuesGroup objects). But also FieldSet.

The ValuesGroup and values can be easily serialized, but the FieldSet is
a bit harder. So instead of serializing the FieldSet it stores only the
FieldSet's name, and when unserializing it loads the FieldSet using the
:class:`Rollerworks\\Component\\Search\\FieldSetRegistryInterface`.

FieldSetRegistry
~~~~~~~~~~~~~~~~

A FieldSetRegistry (:class:`Rollerworks\\Component\\Search\\FieldSetRegistryInterface`)
keeps track of all the FieldSets that you have created and registered.

The FieldSetRegistry is used when unserializing a serialized SearchCondition,
so that don't have to inject the FieldSet explicitly. But you are free to use
it whenever you find it useful.
