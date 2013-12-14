.. index::
   single: input

Input
=====

The input component processes a condition to a ``SearchCondition`` instance.
Only fields registered in the ``FieldSet`` will be processed.

.. warning::

    Because user-input may can contain invalid or unsupported formats,
    the input should transformed and validated before passing it to a storage layer.

The input can be provided in a wide range of formats.

.. tip::

    The :doc:`filter_query` works similar to a spreadsheet and is perfect
    single input conditions.

Values limit
------------

To prevent overloading system the input can limited a by values (per group), maximum amount
of groups and group nesting level.

By default the input is limited to 10000 values per group and 100 groups in total,
with nesting level of 100 levels deep.

Changing these limits can be done by calling ``setLimitValues()``, ``setMaxGroups``
and ``setMaxNestingLevel()`` respectively.

Unless you want to/must support a large number of values its best to not
set these values too high.

.. caution::

    Allowing users to pass a large number of values can result
    in a massive performance hit or even crashing of the application.

    Setting the nesting level to high may requires you to increase
    the ``xdebug.max_nesting_level`` value.

.. toctree::
    :maxdepth: 1

    filter_query
    json
    array
    xml
