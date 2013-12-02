format
~~~~~~

**type**: ``integer`` or ``string`` **default**: `IntlDateFormatter::MEDIUM`_

Option passed to the ``IntlDateFormatter`` class, used to transform user input
into the proper format. This is critical when the input is passed localized format,
and will define how the user will input the data.
By default, the format is determined based on the current user locale: meaning
that *the expected format will be different for different users*. You
can override it by passing the format as a string.

For more information on valid formats, see `Date/Time Format Syntax`_

.. _`Date/Time Format Syntax`: http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Time-Format-Syntax
.. _`IntlDateFormatter::MEDIUM`: http://www.php.net/manual/en/class.intldateformatter.php#intl.intldateformatter-constants
