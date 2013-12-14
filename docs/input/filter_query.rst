.. index::
   single: input; FilterQuery

FilterQuery
===========

Processes input in the FilterQuery format.

The formats works as follow (spaced are ignored).

Every query-pair is a ``field-name: value1, value2;``.

Query-pairs can be nested inside a group ``(field-name: value1, value2;)``
Subgroups are threaded as AND-case to there parent, multiple groups inside
the same group are OR-case to each other.

By default all the query-pairs and other direct-subgroups are treated as AND-case.
To make a group OR-case (any of the fields), prefix the group with ``*``

Example: ``*(field1=values; field2=values);``

Groups are separated with a single semicolon ``;``.
If the subgroup is last in the group the semicolon can be omitted.

Query-Pairs are separated with a single semicolon ``;``.
If the query-pair is last in the group the semicolon can be omitted.

Each value inside a query-pair is separated with a single comma.

When the value contains special characters or spaces it must be quoted.
Numbers only need to be quoted when there marked negative ``"-123"``.

.. note::

    Spaces outside the value are always ignored.

To escape a quote use it double.

Example: ``field: "va""lue";``

Escaped quotes will be normalized to a single one.

Ranges
------

A range consists of two sides, lower and upper bound (inclusive by default).
Each side is considered a value-part and must follow the value convention (as described above).

Example: ``field: 1-100; field2: "-1" - 100``

Each side is inclusive by default, meaning 'the value' and anything lower/higher then it.
To mark a value exclusive (everything between, but not the actual value) prefix it with ']'.

You can also the use '[' to mark it inclusive (explicitly).

* ]1-100 is equal to (> 1 and <= 100)
* [1-100 is equal to (>= 1 and <= 100)
* [1-100[ is equal to (>= 1 and < 100)
* ]1-100[ is equal to (> 1 and < 100)

Example:

``field: ]1 - 100;``

``field: [1 - 100;``

Excluded values
---------------

To mark a value as excluded (also done for ranges) prefix it with an '!'.

Example: ``field: !value, !1 - 10;``

Comparison
----------

Comparisons are very simple.
Supported operators are: ``<, <=, <>, >, >=``

Followed by a value-part.

Example: ``field: >1=, < "-10";``

.. tip::

    Try to avoid using comparisons as ranges, because ranges can be optimized.

PatternMatch
------------

PatternMatch works similar to Comparison,
everything that starts with tilde (~) is considered a pattern match.

Supported operators are:

* ``~*`` (contains)
* ``~>`` (starts with)
* ``~<`` (ends with)
* ``~?`` (regex matching)

And not the NOT equivalent.

* ``~!*`` (does not contain)
* ``~!>`` (does not start with)
* ``~!<`` (does not end with)
* ``~!?`` (does not match regex)

Example: ``field: ~>foo, ~*"bar", ~?"^foo|bar$";``

To mark the pattern case insensitive add an 'i' directly after the '~'.

Example: ``field: ~i>foo, ~i!*"bar", ~i?"^foo|bar$";``

.. note::

    The regex is limited to simple POSIX expressions.
    Actual usage is handled by the storage layer, and may not fully support complex expressions.

.. caution::

    Regex delimiters are not used.
