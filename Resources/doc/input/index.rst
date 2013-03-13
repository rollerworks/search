Input
======

The input component provides the filtering preference to use for filtering.
Only fields present in the ``FieldSet`` will be used.

Filtering preferences can be provided in a wide range of formats.

.. tip::

    To search using a formula, like you do in a spreadsheet, you can
    use the :doc:`filter_query` syntax.

Filtering preferences are provided by so called filtering pairs;
a pair consists of the field name and its values.

The values can be loose values, excludes (*not this value*), ranges and simple comparisons
like: < > >= <=

Grouping
--------

All the filters (inside a group) are applied as *and*.

So ``FilterQuery`` ``user=1; age=5;`` will be applied as ``user = 1 and age = 5``

To apply the second field as a standalone, it must be placed inside
its own group.

To use an *or* condition of one or more filtering pairs they must be
placed inside a group.

.. note::

    The grouping applies that all the pairs in a group must match.
    One or more groups can match.

For example, we want to search for all users that are either
born on 12 December 2000 or are male.

Our search ``FilterQuery`` will look something like this:

.. code-block:: none

    (birthday="2000-12-12";), (gender=male)

And this will be applied as:

.. code-block:: none

    (birthday = "2000-12-12") OR (gender = "male")

If we want narrow our searching to users that are either
born on 12 December 2000, or are male and have an active account.

We use something like this.

.. code-block:: none

    (birthday="2000-12-12";), (gender=male; status=active)

And this will be applied as:

.. code-block:: none

    (birthday = "2000-12-12") OR (gender="male" AND status="active")

Values limit
------------

By default the input is limited to 100 values per group and 30 groups in total.
*The value limit is independent of the value type.*

Changing these limits can be done by calling ``setLimitValues()`` and ``setLimitGroups()``
respectively.

Unless you want to/must support a large number of values its best to not
set this too high.

.. caution::

    Allowing users to pass a large number of values can result
    in a massive performance hit or even crashing of the application.

.. toctree::
    :maxdepth: 2

    filter_query
    json
    array
    xml
