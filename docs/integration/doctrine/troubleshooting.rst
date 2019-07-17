Troubleshooting
===============

Why am I not getting any results
--------------------------------

Make sure sure you have properly configured column-mapping for the ConditionGenerator.
If you are still not getting any results check the following:

#. Are there actual records in the database?
#. Are you using a result limiter like ``WHERE id = ?`` in the query?
   If so try running the query without it.
#. Using multiple tables (with JOIN) will only work when all the tables
   have a positive match, try using ``LEFT JOIN`` to ignore any missing
   matches.
#. Are you using any custom Conversions? Check if they are missing quotes
   or are quoting an integer value. *SQLIte doesn't work well with quoted
   integer values.*
#. Try using a smaller SearchCondition and make sure the values you are
   searching are actually existent.

Didn't any of this work? Ask help at the `RollerworksSearch Gitter channel`_.

Why am I getting duplicate results? (DBAL)
------------------------------------------

This a known problem and is not fixable, this happens when you select from
multiple tables using a JOIN and at least one record matched in multiple
tables.

For example you have a head table called "users", the user has "contacts".
These contacts are linked to the user by the "userId". If you search for
a user by it's contacts multiple contacts will be found that all point to
the same userId.

Because of how relational databases work you get the contacts list and the
linked user. But these records are flat, so one row can contain the data of
multiple tables. And therefor you get duplicate results.
*Even if you don't select from any of the contacts fields.*

How to solve this?
~~~~~~~~~~~~~~~~~~

There are a few possibilities you can consider, all of which have there
pro's and con's.

#. You can use ``DISTINCT`` to remove any duplicate records, but this will
   not work when you select from other columns of JOINED tables.
#. Or you can use ``DISTINCT`` to remove any duplicate userId's, giving you
   a list list of none-duplicate userId's, but you will need to perform an
   additional query to get all the users with the found userId's.
#. You can remove the duplicate values yourself using a PHP script.
   But again this will not work when you need the columns from JOINING
   tables.

If this all seems like a bit to much work you may want to consider
using `Doctrine ORM`_, which does the part of transforming flat
data to an array/object graph for you. Plus there is a
:doc:`RollerworksSearch Doctrine ORM extension <orm>`!

.. caution::

    You may be tempted to use something like:

    .. code-block:: sql

        SELECT * FROM users AS u LEFT JOIN contacts as c ON c.userid = u.userid GROUP BY userId

    **STOP!** This will not work because the behavior on the other columns
    is unspecified. MySQL will only accept this query when you don't enable
    strict-mode, but just because it's not giving any errors doesn't mean
    it will work as expected.

.. _`RollerworksSearch Gitter channel`: https://gitter.im/rollerworks/RollerworksSearch
.. _`Doctrine ORM`: http://www.doctrine-project.org/projects/orm.html
.. _`RollerworksSearch Doctrine ORM extension`: https://github.com/rollerworks/rollerworks-search-doctrine-orm
