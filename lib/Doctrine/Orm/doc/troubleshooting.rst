Troubleshooting
===============

Why am I not getting any results
--------------------------------

Make sure sure you have properly configured entity/field-mapping for the
WhereBuilder. If you are still not getting any results check the following:

#. Are there actual records in the database?
#. Are you using a result limiter like ``WHERE id = ?`` in the query?
   If so try running the query without it.
#. Using multiple tables (with JOIN) will only work when all the tables
   have a positive match, try using ``LEFT JOIN`` to ignore any missing
   matches.
#. Try using a smaller SearchCondition and make sure the values you are
   searching are actually existent.

Didn't any of this work? Ask help at the `RollerworksSearch Gitter channel`_.

.. _`RollerworksSearch Gitter channel`: https://gitter.im/rollerworks/RollerworksSearch
.. _`Doctrine ORM`: http://www.doctrine-project.org/projects/orm.html
.. _`RollerworksSearch Doctrine ORM extension`: https://github.com/rollerworks/rollerworks-search-doctrine-orm
