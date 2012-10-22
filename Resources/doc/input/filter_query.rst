FilterQuery
===========

FilterQuery provides you with an easy to use and understand filtering preference.

The syntax is similar to an spreadsheet formula,
but instead of defining a formula we specify search criteria.

Every filter is an pair as: name=values;

The fieldname must follow this regex convention: [a-z][a-z_0-9]*.
Unicode characters and numbers are accepted.

If the value contains an ';' or '()', the whole value must be quoted (with double quotes).
If the value contains an special character, like the range symbol 'that'
value-part must be quoted.

Like: ``"value-1"-value2``

An exception to this is when the type supports *matching*,
in that case the type will specify the pattern and quoting is not needed.

Single values containing no special characters, can be quoted. But this is not required.

If we want to use an *or* condition (either one of the groups must match),
we place the name=value; between round-bars '()' and separate them by one comma ','.

All the field=value pairs in the group are always *and*,
meaning that all the conditions in an group must match.

Any leading or trailing whitespace is ignored, unless quoted.

.. caution::

    The field=value pairs must 'always end' with an ';', especially when using an OR-group.
    The parser will not accept an input like: (field=value),(field2=value)

    Any comma or whitespace at the end is always ignored.
