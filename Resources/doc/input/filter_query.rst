FilterQuery
===========

FilterQuery provides you with an easy to use and understand filtering preference syntax.

The syntax is similar to an spreadsheet formula,
but instead of defining a formula you specify a search criteria.

Every filter is an pair as: name=values;

The fieldname must follow this regex convention: [a-z][a-z_0-9]*.
Unicode characters and numbers are accepted.

If the value contains an ';' or '()', the whole value must be "quoted" (with double quotes).
If the value contains any special character, like the range symbol 'that'
value-part must be quoted.

Like: ``"value-1"-value2``

An exception to this is when the type has support for *matching*,
in that case the type will specify the pattern and quoting is not required.

Single values containing no special characters, can be quoted. But this is optional.

Any leading or trailing whitespace is ignored, unless quoted.

.. caution::

    The field=value pairs *must always end* with a ';', especially when using groups.
    The parser will not accept an input like: (field=value),(field2=value)

    Any comma or whitespace at the end of a is always ignored.

Grouping
--------

Groups created are by placing the all the filtering pairs between ().

Like: ``(birthday="2000-12-12";), (gender=male)``
