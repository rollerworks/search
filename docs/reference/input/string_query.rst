.. index::
   single: input; StringQuery

StringQuery Format
==================

The StringQuery consists is a user-friendly syntax for providing search
conditions. Note that input is accepted in any local including none latin
characters.

.. tip::

    Whitespace characters (including new lines) are ignored for better
    readability.

    ``field-name: value1, value2;`` and ``field-name:value1,value2;``
    Are technically the same.

    But spaces within a value like ``field: hello world`` are prohibited.
    You must quote the entire value then: ``field: "hello world"``

    New lines in a value are always prohibited.

The syntax consists for field-values pairs. Values are separated using
a single coma (``,``). And pairs are separated using a single semi-colon
(``;``)

.. code-block:: php

    username: value1, value2;

``username`` is the field-name, ``value1`` and ``value`` are the values of the
username field.

The ``;`` character at the end of the query-pair indicates that the values
list has ended, and a new pair or subgroup can be started.

.. tip::

    When the pair is last in the group or at the end of the input it
    can be omitted.

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
formatted value like a date. But not all values can be used directly.

.. note::

    If the value contains special characters (including spaces) the value
    must be quoted using double quotes (``"``).

    Special characters are: ``[<>[](),;~!*?=&*``, otherwise these characters
    could be interpreted as value/field separate or give a syntax error.

If the value itself contains double quotes these must be escaped by duplicating
theme, so ``va"lue`` becomes ``va""lue`` and ``va""lue`` becomes ``va""""lue``.

Escaped quotes will then be normalized when processing the input.

.. caution::

    Be careful with quotes at the beginning, ``""foo"`` might seem valid,
    but the first quote is used to indicate a quoted value.

    The correct usage is ``"""foo"`` which is transformed to ``"foo``

Using a value like described above is whats called a "simple value".
If there are more we call them of a list of simple values.

But as simple values alone are not really useful, you can also use
excluded simple values, (excluded) ranges, comparisons, and pattern matchers.

To mark a value as being excluded prefix it with an exclamation (``!``),
this works for almost all value types except comparisons and pattern matcher
which have there own syntax.

.. code-block:: php

    field: !value, !1 ~ 10;

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

    field: 1-100; field2: -1 ~ 100

Each side is inclusive by default, meaning the 'value' itself and anything
lower/higher then it. To mark a value as exclusive (everything between,
but not the actual value) use the outer turning square brace ``]`` for the
lower-bound and ``[`` for the upper-bound.

* ``]1 ~ 100`` is equal to (higher then) 1 and (equal to or lower then) 100)
* ``[1 ~ 100`` is equal to (equal to or higher then) 1 and (equal to or lower then) 100)
* ``[1 ~ 100[`` is equal to (equal to or higher then) 1 and (lower then) 100)
* ``]1 ~ 100[`` is equal to (higher then) 1 and (lower then) 100)

You can also mark a bound explicitly inclusive using ``[`` for lower-bound
and ``]`` for the upper-bound to mark the. But as bounds are inclusive by
default you don't have to do this, it's merely for explicitness.

Comparison
~~~~~~~~~~

Comparisons are pretty straightforward, each comparison starts with an
operator followed by a value-part.

Supported operators are:

* ``<`` (lower then)
* ``<=`` (lower then or equal to)
* ``<>`` (not higher or lower then (same as marking the value as excluded))
* ``>`` (higher then)
* ``>=`` (higher then or equal to)

.. code-block:: php

    field: >=1, < -10, date: > 06/02/2015

.. tip::

    Whenever possible try to use ranges instead of multiple comparisons,
    because ranges can be optimized.

PatternMatch
------------

PatternMatchers work similar to Comparisons, everything starting
with a tilde (``~``) is considered a pattern-matcher.

Supported operators are:

* ``~*`` (contains)
* ``~>`` (starts with)
* ``~<`` (ends with)
* ``~=`` (equals)

And not the excluding equivalent.

* ``~!*`` (does not contain)
* ``~!>`` (does not start with)
* ``~!<`` (does not end with)
* ``~!=`` (equals)

.. code-block:: php

    field: ~> foo, ~*"bar";

To mark the pattern case-insensitive add an 'i' directly after the '~'.

.. code-block:: php

    field: ~i> foo, ~i!* "bar";

Subgroups
---------

For more complex conditions you can nest pairs inside subgroups.
Subgroups are separated the same way as pairs, using the ``;`` character.

And just like field-values pairs, the group separation character can
be omitted when the group is last in the group or input.

.. code-block:: php

    (field-name: value1, value2;); (field-name: value1, value2)

Or in combination with field-values pairs.

.. code-block:: php

    field-name: value1, value2; (field-name: value1, value2)

.. tip::

    Noticed that pair in the second subgroup does not end with a ``;``?

    That's because the processor is smart enough to know that the group
    has ended here and it can simply ignore the missing ``;`` and continue.
    If there was a second pair or nested subgroup an ``;`` is required.

By default all groups are marked as logical AND, meaning all the fields
within the group must give a positive match. For explicitness you can use
this to mark the group as logical AND.

.. code-block:: php

    &(field1: values; field2: values);

To change a group and make it OR'ed (at least one field must give a positive
match), prefix the group with a star ``*`` character.

.. code-block:: php

    *(field1: values; field2: values);

If you want to head-group (the condition itself) OR'ed or AND (default) use
``*`` or ``&`` as the first character in the condition.

.. code-block:: php

    * field1: values; field2: values;

.. code-block:: php

    &field1: values; field2: values;

.. caution::

    The OR'ed symbol works only on groups, because the condition always
    starts with a group the OR'ed symbol is only valid at the start of
    a condition or subgroup.

    So the following is invalid: ``is_admin: t; * enabled: f;``

    But this is valid: ``is_admin: t; *(enabled: f)`` and marks subgroup 0
    as OR'ed.
