allow_relative
~~~~~~~~~~~~~~

**type**: ``bool`` **default**: ``false``_

Enables the handling of relative Date and/or time formats like
``1 week``, ``6 years 3 hours``.

The actual datetime is relative to now (current date and time), use the minus (``-``)
sign like ``-1 year`` to invert interval to a past moment.

Internally this uses `Carbon DateInterval`_ to parse the (localized) format.

.. caution::

    For Doctrine DBAL and ORM not all platforms are supported yet.
    Currently only PostgreSQL and MySQL/MariaDB are supported.

    Working se

.. _`Date/Time Format Syntax`: http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Time-Format-Syntax
.. _`Carbon DateInterval`: https://carbon.nesbot.com/docs/#api-interval
