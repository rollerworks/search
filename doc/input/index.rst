.. index::
   single: input

Input
=====

The input component processes a condition to a ``SearchCondition`` instance.

.. note::

    Only fields registered in the ``FieldSet`` will be processed,
    other fields are simple ignored.

The input can be provided in a wide range of formats.

.. tip::

    The :doc:`filter_query` works similar to a spreadsheet formula's syntax
    and is perfect single input conditions.

Values limit
------------

To prevent overloading your system the provided input can limited
a by values (per group), maximum amount of groups and group nesting level.

Configuring happens using a ``Rollerworks\Component\Search\Input\ProcessorConfig``
object instance.

By default the input is limited to 10000 values per group and 100 groups in total,
with a nesting level of 100 levels deep.

Changing these limits can be done by calling ``setLimitValues()``, ``setMaxGroups``
and ``setMaxNestingLevel()`` respectively.

Unless you want to/must support a large number of values its best to not
set these values too high.

.. caution::

    Allowing users to pass a large number of values can result
    in a massive performance hit or even crashing of the application.

    Setting the nesting level to high may require you to increase
    the ``xdebug.max_nesting_level`` value.

.. toctree::
    :maxdepth: 1

    filter_query
    json
    array
    xml
