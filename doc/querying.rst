Querying the database
=====================

Once you have successfully :doc:`installed <installing>` this extension
package. You can starting querying (searching) the database for results.

The process of querying the database happens after you have processed the
user's input and optimized the ``SearchCondition`` for better performance.

.. tip::

    You can choose to keep the querying logic anywhere, but it's best to
    keep it a central place like a Repository class or ``SearchService``
    class to prevent spreading your code all over the place.

As you already know RollerworksSearch uses a ``SearchFactory`` for handling
most of to boilerplate code to get starting. But you can't use this for
querying the database, so the Doctrine DBAL extension comes with it's
own Factory :class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\DoctrineDbalFactory`.

Just the like the ``SearchFactory`` the ``DoctrineDbalFactory`` reduces
the amount of boilerplate code and helps you easily integrate this extension
within your application.

.. note::

    The ``DoctrineDbalFactory`` works next to the ``SearchFactory``.
    It's not a replacement for the ``SearchFactory``!

SearchFactory
-------------

The ``DoctrineDbalFactory`` class provides an entry point for creating
:class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\WhereBuilder` and
:class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\CacheWhereBuilder`
object instances and ensures the :doc:`conversions` are registered at the
WhereBuilder.

Initiating the ``DoctrineDbalFactory`` is as simple as.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;

    // \Doctrine\Common\Cache\Cache object
    $doctrineCache = ...;

    $doctrineDbalFactory = new DoctrineDbalFactory($doctrineCache);

The value of ``$doctrineCache`` can be any caching driver supported by
the `Doctrine Cache`_ library. For best performance it's advised to use
a cache driver that stays persistent between page loads.

Using the WhereBuilder
~~~~~~~~~~~~~~~~~~~~~~

The WhereBuilder generates an SQL Where-clause for a relational database
like PostgreSQL, MySQL, MSSQL, SQLite or Oracle.

.. caution::

    A WhereBuilder is configured with a database connection and SearchCondition.
    So reusing a WhereBuilder is not possible.

    Secondly, the generated query is only valid for the give Database driver.
    Meaning that when you generated a query with the SQLite database driver
    this query will fail to work on MySQL.

First create a ``WhereBuilder``:

.. code-block:: php
    :linenos:

    /* ... */

    // Doctrine\DBAL\Connection object
    $connection = ...;

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $whereBuilder = $doctrineDbalFactory->createWhereBuilder($connection, $searchCondition);

Now before the query can be generated, the WhereBuilder needs to know which
search-fields belongs to which column and table/schema. To configure this
field-to-column mapping, use the ``setField`` method on the WhereBuilder
object:

.. code-block:: php
    :linenos:

    /**
     * Set Field configuration for the query-generation.
     *
     * @param string $fieldName Name of the SearchField
     * @param string $column    DB column-name
     * @param string $type      DB-type string or object
     * @param string $alias     alias to use with the column
     */
    $whereBuilder->setField($fieldName, $column, $type = 'string', $alias = null);

The first parameter is the field-name as registered in the provided FieldSet,
followed by the database column-name (without any quoting), the mapping-type
(as provided by Doctrine DBAL) and last an optional table alias that corresponds
with the table alias in the Query.

.. note::

    The mapping-type must correspond to a Doctrine DBAL support Type.
    So instead of using ``varchar`` you use ``string``.

    See `Doctrine DBAL Types`_ for a complete list of types and options.

    If you have a type which requires the setting of options you may need
    to use a :ref:`value_conversion` instead.

.. caution::

    Only SearchFields in the FieldSet that have a column-mapping configured
    will be processed. Other fields are simply ignored.

    If you try to configure a column-mapping for a none registered SearchField
    the WhereBuilder will throw an exception.

Once the WhereBuilder is configured, it's time to generate the SQL Where-clause.
The WhereBuilder will safely embed all values within the generated query.

.. tip::

    The WhereBuilder embeds the values because any changes to the SearchCondition
    will also change the overall structure of the generated query, so using
    a prepared statement here would over complicate the code and actually
    slow down the searching process.

.. code-block:: php
    :linenos:

    // Doctrine\DBAL\Connection object
    $connection = ...;

    /* ... */

    $query = '
        SELECT
            u.name AS user_name,
            u.id AS user_id
        FROM
            users AS u
        LEFT JOIN
            contacts as c
        ON
            u.id = u.user_id
    ';

    // See Mapping data for details
    $whereBuilder->setField('user_id', 'id', 'integer', 'u');
    $whereBuilder->setField('user_name', 'name', 'string', 'u');
    $whereBuilder->setField('contact_name', 'name', 'string', 'c');

    // The ' WHERE ' value is placed before the generated where-clause,
    // but only when there is actual where-clause, else it returns an empty string.
    $whereClause = $whereBuilder->getWhereClause(' WHERE ');

    // Add the Where-clause
    $query .= $whereClause;

    $statement = $connection->query($query);

    // Get all the records
    // See http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html#data-retrieval
    $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

.. tip::

    To prevent certain users from getting results they are not allowed to
    see you can combine the generated Where-clause with a primary AND-condition.

    .. code-block:: php

        $query = 'SELECT u.name AS name, u.id AS id FROM users AS u WHERE id = ?';
        $whereBuilder = ...;

        $whereClause = $whereBuilder->getWhereClause();

        if (!empty($whereClause)) {
           $query .= ' AND '.$whereClause;
        }

        $statement = $connection->prepare($query);
        $statement->bindValue(1, $id);
        $statement->execute();

Setting Conversions
*******************

Conversions are automatically registered using the ``DoctrineDbalFactory``,
but if you're not using the ``DoctrineDbalFactory`` or need to set conversions
manually you can still register them by calling ``setConverter($fieldName, $converter)``
on the WhereBuilder.

Caching the Where-clause
~~~~~~~~~~~~~~~~~~~~~~~~

Generating a Where-clause may require quite some time and system resources,
which is why it's recommended to cache the generated query for future usage.
Fortunately this package provides :class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\CacheWhereBuilder`
which can handle caching of the WhereBuilder for you.

Usage of the ``CacheWhereBuilder`` is very simple, the only thing you
need to configure is the cache-key to storing and finding the generated
query.

.. tip::

    The ``setCacheKey`` methods accepts eg. a fixed value like a string
    or a PHP supported callback to generate a unique cache-key.

    When you use a callback the the "original" WhereBuilder
    object is passed as the first (and only) parameter.

.. code-block:: php
    :linenos:

    /* ... */

    $query = 'SELECT u.name AS name, u.id AS id FROM users AS u';
    $whereBuilder = ...;

    // The first parameter is the original WhereBuilder as described above
    // The second parameter is the cache lifetime in seconds, 0 means not expiring
    $cacheWhereBuilder = $doctrineDbalFactory->createCacheWhereBuilder($whereBuilder, 0);

    // You can use a static cache key
    $cacheWhereBuilder->setCacheKey('my_key');

    // Or you can use a callback/closure for generating a unique key
    $cacheWhereBuilder->setCacheKey(null, function ($whereBuilder) {
        return $whereBuilder->getSearchCondition()->getFieldSet()->getSetName();
    });

    // The ' WHERE ' value is placed before the generated where-clause,
    // but only when there is actual where-clause, else it returns an empty string.
    $whereClause = $cacheWhereBuilder->getWhereClause(' WHERE ');

    // Add the Where-clause
    $query .= $whereClause;

    $statement = $connection->query($query);

.. note::

    Changes to the mapping configuration are **not automatically detected**.
    It's recommended to use a Cache Driver that can be easily purged, like
    a PHP session or memory storage.

Next Steps
----------

Now that you have completed the basic installation and configuration,
and know how to query the database for results. You are ready to learn
about more advanced features and usages of this extension.

You may have noticed the word "conversions" a few times, now it's time
learn more about them! :doc:`conversions`.

And if you get stuck with querying, there is
:doc:`Troubleshooter <troubleshooting>` to help you.

.. _`Doctrine Cache`: http://docs.doctrine-project.org/projects/doctrine-common/en/latest/reference/caching.html
.. _`Doctrine DBAL Types`: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
