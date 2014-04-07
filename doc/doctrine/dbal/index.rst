Doctrine DBAL
=============

The Doctrine DBAL component facilitates the searching of records
using Doctrine DBAL.

Introduction
------------

.. note::

    Before you can use the Doctrine DBAL component,
    you must have the `Doctrine DBAL`_ library installed an configured.

First you must enable the ``DoctrineDbalExtension`` to ``SearchFactoryBuilder``.

This will enable the options for the search fields.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;
    use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
    use Rollerworks\Component\Search\Extension\Core\CoreExtension;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new DoctrineDbalExtension())

        // ...
        ->getSearchFactory();

.. include:: converters.rst.inc

Factory
-------

The ``DoctrineDbalFactory`` class provides an entry point for creating
a ``WhereBuilder`` instance and ``CacheWhereBuilder`` instance.

The created ``WhereBuilder`` instance is automatic configured with the options
of the fields registered in the FieldSet.

The ``CacheWhereBuilder`` is configured with the default cache-driver.

Initiating the DoctrineDbalFactory is as simple as.

.. code-block:: php

    use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;

    // \Doctrine\Common\Cache\Cache
    $doctrineCache = ...;

    $doctrineDbalFactory = new DoctrineDbalFactory($doctrineCache);

The ``$doctrineCache`` can be any caching driver supported by Doctrine2 Cache library.
For best performance you'd properly want to use a cache that stays persistent between page loads.

Creating a new `WhereBuilder`_ is done by calling ``$doctrineDbalFactory->createWhereBuilder()`` and passing the
Doctrine DBAL connection object as first parameter and the SearchCondition as second.

.. code-block:: php

    use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineOrmFactory;
    use Rollerworks\Component\Search\SearchCondition;

    // \Doctrine\Common\Cache\Cache
    $doctrineCache = ...;

    // \Doctrine\DBAL\Driver\Connection
    $connection = ...;

    $doctrineDbalFactory = new DoctrineDbalFactory();
    $searchCondition = ...;

    $query = $entityManager->createQuery(...);
    $whereBuilder = $doctrineDbalFactory->createWhereBuilder($connection, $searchCondition);

WhereBuilder
------------

The ``WhereBuilder`` creates an SQL WHERE clause for an relational
database like PostgreSQL, MySQL, SQLite or Oracle.

Usage of the ``WhereBuilder`` is very straightforward, a ``WhereBuilder`` instance
is configured for one 'query' and SearchCondition. So each query/condition requires a new instance.

.. note::

    Its possible to configure the ``WhereBuilder`` all by yourself, but using the
    factory is more recommended to ensure all the options are properly applied.

    For the following examples we will be using the factory.

Depending on the configuration we can  either choose to use a prepared-statement
or executing the query directly.

.. Note::

    Direct executing requires the parameters to be embedded.

    To do this pass ``true`` to the getWhereClause() method.
    And use query() instead of prepare() on the connection object.

.. code-block:: php

    use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
    use Rollerworks\Component\Search\SearchCondition;

    // \Doctrine\Common\Cache\Cache
    $doctrineCache = ...;

    // \Doctrine\DBAL\Driver\Connection
    $connection = ...;

    $doctrineDbalFactory = new DoctrineDbalFactory();
    $searchCondition = ...;

    /* ... */

    $query = 'SELECT u.name AS name, u.id AS id FROM users AS u';

    $whereBuilder = $doctrineDbalFactory->createWhereBuilder($query, $searchCondition);

    // See Mapping data for details
    $whereBuilder->setField('user_id', 'id', 'integer', 'u');
    $whereBuilder->setField('user_name', 'name', 'string', 'u');

    //
    // Prepared (recommended)
    //

    if ($whereClause = $whereBuilder->getWhereClause()) {
       $query .= ' WHERE '.$whereClause;
    }

    $queryStatement = $connection->prepare($query);

    // Set the binding parameters for the query
    // Only do this when there is an actual where-statement
    if ($whereClause) {
        $whereBuilder->bindParameters($queryStatement);
    }

    /* ... */

    $queryStatement->execute();

    //
    // Directly
    //

    if ($whereClause = $whereBuilder->getWhereClause(true)) {
       $query .= ' WHERE '.$whereClause;
    }

    $queryStatement = $connection->query($query);

Mapping data
~~~~~~~~~~~~

Unlike the ORM WhereBuilder you need to configure the fields manually.

Each field must exist in the FieldSet of the SearchCondition, only fields registered
at the WhereBuilder will be processed.

.. code-block:: php

    /**
     * Set Field configuration for the query-generation.
     *
     * @param string                           $fieldName Name of the Search-field
     * @param string                           $column    DB column-name
     * @param string|\Doctrine\DBAL\Types\Type $type      DB-type string or object
     * @param string                           $alias     alias to use with the column
     *
     * @return self
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset.
     * @throws BadMethodCallException When the where-clause is already generated.
     */
    $whereBuilder->setField($fieldName, $column, $type = 'string', $alias = null);

.. note::

    Conversions are registered separately.
    Overwriting an existing field will preserve the configured conversions.

Parameters
~~~~~~~~~~

Parameters are set on the Query statement as "field_name_x" (x is an incrementing number).

If this for some reason needs to be changed, you can configure a parameter-prefix (before creating
the where-clause). A good case is using the FieldSet-name as prefix.

.. code-block:: php

    /* ... */

    $whereBuilder->setParameterPrefix('my_prefix');

.. note::

    Parameters are not used when the values are marked to be embedded.

Caching
~~~~~~~

Generating the where-clause can be very expensive for the system, so its advised
to cache the result for future page loads (like when using paginating).

Fortunately the where builder also has a caching system which can handle this
for you. The only thing you need to configure the is caching key, to ensure there
are no conflicts with other search conditions.

Were you first called bindParameters() on the WhereBuilder, you now call it
on the ``CacheWhereBuilder`` instance.

.. caution::

    Conversions that depend on something that varies per page request
    should can not be cached.

.. code-block:: php

    use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
    use Rollerworks\Component\Search\SearchCondition;

    /* ... */

    // The second parameter is the lifetime in seconds
    $cacheWhereBuilder = $doctrineDbalFactory->createCacheWhereBuilder($whereBuilder, 0);

    // You can set a static caching key
    $cacheWhereBuilder->setCacheKey('my_key');

    // Or you can use a callback/closure for generating the key
    $cacheWhereBuilder->setCacheKey(null, function () {
        return 'my_key';
    });

    // Now execute the query

    if ($whereClause = $cacheWhereBuilder->getWhereClause()) {
       $query .= ' WHERE '.$whereClause;
    }

    $queryStatement = $connection->prepare($query);

    // Set the binding parameters for the query
    // Only do this when there is an actual where-statement
    if ($whereClause) {
        $cacheWhereBuilder->bindParameters($queryStatement);
    }

    /* ... */

    $queryStatement->execute();
    $cacheWhereBuilder->bindParameters($queryStatement);

.. warning::

    Any changes to the mapping configuration are **not automatically** detected.
    Always use a Cache Driver that can be easily removed, like a PHP session.

.. _Doctrine DBAL: http://www.doctrine-project.org/projects/dbal.html
