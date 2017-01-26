.. index::
   single: input; FilterQuery

FilterQuery
===========

Processes input in the FilterQuery format.

The FilterQuery consists is a user-friendly syntax for providing search
conditions. Note that input is accepted in any local including none latin
characters.

.. tip::

    Spaces are ignored so that you can keep whitespace
    between condition characters for better readability.

    ``field-name: value1, value2;`` and ``field-name:value1,value2;``
    Are technically the same.

The syntax works using whats called query-pairs consisting of a
field-name and list of values. Values are separated using a single coma
(``,``).

.. code-block:: php

    username: value1, value2;

"username" is the field-name, "value1" and "value" are the values of the
username field.

The ``;`` character at the end of the query-pair indicates that the values
list has ended, and a new query-pair or subgroup can be started. When the
query-pair is not followed by anything (except optional space) the ``;`` can be omitted.

.. code-block:: php

    field1: value1, value2; field2: value1, value2;

Can shorted to.

.. code-block:: php

    field1: value1, value2; field2: value1, value2

Field-name
----------

A field-name is limited in formatting, it must start with an alphabetic
character or word from any language. So a to z or "价" (price) in
Simplified Chinese.

After this it may be followed be a number, alphabetic character, word
or a dash ``-`` or underscore ``_`` any given number of times.

The following fields-names are valid:

* ``价``
* ``price``
* ``price0``
* ``total_price``
* ``total-price``

The following field-names are invalid:

* ``0K`` (must not start with a number)
* ``0价`` (must not start with a number)
* ``0`` (must not start with a number)
* ``_price`` (must not start with an underscore)
* ``-price``  (must not start with an dash)
* ``total-price:`` (must not end with an double colon ``:``).

Values
------

A value can be anything from a word, a sentence, number or specially
formatted value like a date. But not all values can be used directly, if
the value contains special characters (including spaces) the value must
be quoted using double quotes (``"``).

.. note::

    Decimal numbers must be quoted as well like ``"10.00"``.
    ``10.00`` unquoted is considered invalid!

If the value itself contains double quotes these must be escaped by duplicating
theme, so ``va"lue`` becomes ``va""lue`` and ``va""lue`` becomes ``va""""lue``.

Escaped quotes will then be normalized when processing the input.

.. caution::

    Be careful with quotes at the beginning, ``""foo"`` might seem valid,
    but the first quote is used to indicate a quoted value.

    The correct usage is ``"""foo"`` which is transformed to ``"foo``

Using a value like described above is whats called a "single value".
If there are more we call them of a list of single values.

But as single values alone are not really useful, you can also use
excluded single values, (excluded) ranges, comparisons and pattern matchers.

To mark a value as being excluded prefix it with an ``!``, this works for
almost all value types except comparisons and pattern matcher which have
there own syntax.

.. code-block:: php

    field: !value, !1 - 10;

As some values are part of an expression like a range, the value is referred
to as a value-part.

Ranges
~~~~~~

A range consists of two sides, a lower and upper bound (inclusive by default).
Each side is considered a value-part and must follow the value convention
(as described above).

The following condition is seen as: field1 is between (inclusive) 1 and 100,
and field2 is between (inclusive) -1 and 100.

.. code-block:: php

    field: 1-100; field2: "-1" - 100

Each side is inclusive by default, meaning the 'value' itself and anything
lower/higher then it. To mark a value exclusive (everything between,
but not the actual value) use the outer turning square brace ``]`` for the
lower-bound and ``[`` for the upper-bound.

* ``]1-100`` is equal to (higher then) 1 and (equal to or lower then) 100)
* ``[1-100`` is equal to (equal to or higher then) 1 and (equal to or lower then) 100)
* ``[1-100[`` is equal to (equal to or higher then) 1 and (lower then) 100)
* ``]1-100[`` is equal to (higher then) 1 and (lower then) 100)

You can also mark a bound explicitly inclusive using ``[`` for lower-bound
and ``]`` for the upper-bound to mark the. But as bounds inclusive by
default you don't have to do this, it's just for explicitness.

Comparison
~~~~~~~~~~

Comparisons are very straightforward, each comparison starts with an operator
followed by a value-part.

Supported operators are:

* ``<`` (lower then)
* ``<=`` (lower then or equal to)
* ``<>`` (not higher or lower then (same as marking the value as excluded))
* ``>`` (higher then)
* ``>=`` (higher then or equal to)

.. code-block:: php

    field: >=1, < "-10", date: >"06/02/2015";

.. tip::

    When ever possible try to use ranges instead of multiple comparisons,
    because ranges can be optimized.

PatternMatch
------------

PatternMatchers work similar to Comparisons, everything starting
with tilde (~) is considered a pattern-matcher.

Supported operators are:

* ``~*`` (contains)
* ``~>`` (starts with)
* ``~<`` (ends with)
* ``~?`` (regex matching)
* ``~=`` (equals)

And not the excluding equivalent.

* ``~!*`` (does not contain)
* ``~!>`` (does not start with)
* ``~!<`` (does not end with)
* ``~!?`` (does not match regex)
* ``~!=`` (equals)

Example: ``field: ~>foo, ~*"bar", ~?"^foo|bar$";``

To mark the pattern case insensitive add an 'i' directly after the '~'.

Example: ``field: ~i>foo, ~i!*"bar", ~i?"^foo|bar$";``

.. note::

    The regex is limited to simple POSIX expressions. Actual usage is
    handled by the storage layer, and may not fully support complex expressions.

    Most matchers can be easily solved without regexes, always try to
    use a normal matcher before trying a regex.

.. caution::

    In most languages the Regex would start and end with a delimit,
    but in filter-query this is not the case.

Subgroups
---------

For more complex conditions you can nest query-pairs inside subgroups.
Subgroups are separated the same way as query-pairs, using the ``;``
character. And when the group closing character is not followed by anything
(except optional space) the last ``;`` can be omitted.

.. code-block:: php

    (field-name: value1, value2;); (field-name: value1, value2)

Or in combination with query-pairs.

.. code-block:: php

    field-name: value1, value2; (field-name: value1, value2);

.. tip::

    Notice that query-pair in the second subgroup does end with a ``;``?
    That's because the processor is smart enough to know that the group
    has ended here and it can simply ignore the missing ``;`` and continue.
    If there was a second query-pair or nested subgroup an ``;`` is required.

By default all groups are marked as logical AND, meaning all the fields
within the group must give a positive match. For explicitness you can use this
to mark the group as logical AND.

.. code-block:: php

    &(field1: values; field2: values);

To change a group and make it OR'ed (at least one field must give a positive
match), prefix the group with an ``*`` character.

.. code-block:: php

    *(field1: values; field2: values);

If you want to head-group (the condition itself) OR'ed or AND (default) use
``*`` or ``&`` as the first character in the condition.

.. code-block:: php

    *field1: values; field2: values;

.. code-block:: php

    &field1: values; field2: values;

.. caution::

    The OR'ed symbol works only on groups, because the condition always
    starts with a group the OR'ed symbol is only valid at the start of
    a condition or subgroup. So the following is invalid: ``is_admin: t; * enabled: f;``

    But this is valid: ``is_admin: t; *(enabled: f)`` and marks subgroup 0
    as OR'ed.
