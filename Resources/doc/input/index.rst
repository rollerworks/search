Input
======

The input component provides the input to use for filtering,
only fields present in the FieldSet will be used.

Values limit
------------

By default the input is limited to 100 values per group and 30 groups in total.
*These values are independent of there value type.*

This can be changed calling ``setLimitValues()`` and ``setLimitGroups()`` respectively.

Unless you want to/must support a large number of values its best to not set this to high.
**Allowing users to pass a large number of values can result in a massive
performance hit or even crash.**

.. toctree::
    :maxdepth: 2

    filter_query
    json
    array
    xml
