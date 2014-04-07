Doctrine ORM
============

The Doctrine ORM component facilitates the searching of records
using Doctrine ORM.

Introduction
------------

.. note::

    Before you can use the Doctrine ORM component,
    you must have the `Doctrine ORM`_ library installed an configured.

First you must enable the ``DoctrineOrmExtension`` and
``DoctrineDbalExtension`` to ``SearchFactoryBuilder``.

This will enable the options for the search fields.
And configures the EntityManagers for usage of transformers.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;
    use Rollerworks\Component\Search\Extension\Doctrine\Orm\DoctrineOrmExtension;
    use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
    use Rollerworks\Component\Search\Extension\Core\CoreExtension;

    // \Doctrine\Common\Persistence\ManagerRegistry
    $managerRegistry = ...;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new DoctrineDbalExtension())
        ->addExtension(new DoctrineOrmExtension($managerRegistry))

        // ...
        ->getSearchFactory();


.. include:: /Doctrine/dbal/converters.rst.inc

Factory
-------

The ``DoctrineOrmFactory`` class provides an entry point for creating
a ``WhereBuilder`` instance and ``CacheWhereBuilder`` instance.

The created ``WhereBuilder`` instance is automatic configured with the options
of the fields registered in the FieldSet.

The ``CacheWhereBuilder`` is configured with the default cache-driver.

Initiating the DoctrineOrmFactory is as simple as.

.. code-block:: php

    use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;

    // \Doctrine\Common\Cache\Cache
    $doctrineCache = ...;

    $doctrineOrmFactory = new DoctrineOrmFactory($doctrineCache);

The ``$doctrineCache`` can be any caching driver supported by Doctrine2 Cache library.
For best performance you'd properly want to use a cache that stays persistent between page loads.

Creating a new WhereBuilder is done by calling ``$doctrineOrmFactory->createWhereBuilder()`` and passing the
Doctrine ORM query object as first parameter and the SearchCondition as second.

.. code-block:: php

    use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
    use Rollerworks\Component\Search\SearchCondition;

    // \Doctrine\Common\Cache\Cache
    $doctrineCache = ...;

    // \Doctrine\ORM\EntityManager
    $entityManager = ...;

    $doctrineOrmFactory = new DoctrineOrmFactory();
    $searchCondition = ...;

    $query = $entityManager->createQuery(...);
    $whereBuilder = $doctrineOrmFactory->createWhereBuilder($query, $searchCondition);

WhereBuilder
------------

The ``WhereBuilder`` creates searches for an SQL relational database like PostgreSQL, MySQL, SQLite
or Oracle using an SQL/DQL WHERE clause.

Both NativeSQL and the Doctrine Query Language (DQL) are fully supported.

Usage of the ``WhereBuilder`` is very straightforward, a ``WhereBuilder`` instance
is configured for one query and SearchCondition. So each query/condition requires a new instance.

.. note::

    Its possible to configure the ``WhereBuilder`` all by yourself, but using the
    factory is more recommended to ensure all the options are properly applied.

    For the following examples we will be using the factory.

.. code-block:: php

    use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
    use Rollerworks\Component\Search\SearchCondition;

    // \Doctrine\Common\Cache\Cache
    $doctrineCache = ...;

    // \Doctrine\ORM\EntityManager
    $entityManager = ...;

    $doctrineOrmFactory = new DoctrineOrmFactory();
    $searchCondition = ...;

    /* ... */

    // There's no need to add a 'WHERE' at the end of the query as this will be applied later
    $query = $entityManager->createQuery('SELECT u FROM User u');
    $whereBuilder = $doctrineOrmFactory->createWhereBuilder($query, $searchCondition);

**Note:** When the query selects from multiple tables or uses DQL, the class-relation to alias
mapping must be configured. If the query only uses a single table the mapping will be configured for you.

.. caution::

    Searching with joined entities might cause duplicate results.
    Use DISTINCT on the unique ID of the 'parent' table to remove any duplicates.

    The duplicate results happen because the database is asked to return all matching
    records, and one parent record may multiple matching children.

.. code-block:: php

    /* ... */

    $dql = 'SELECT u, g FROM Acme\User\Entity\User u JOIN User.group g WHERE';

    $query->setDql($dql);

    $entityAliases = array(
        'Acme\User\Entity\User' => 'u'
        'Acme\User\Entity\Group' => 'g'
    );

    $whereBuilder->setEntityMappings($entityAliases);

    // Note: Make sure you only do this when the search condition actually
    // has values, it maybe better to use updateQuery() (see below)
    $dql .= $whereBuilder->getWhereClause();

.. tip::

    If you configured Doctrine to support the short entity notation ``AcmeUser:User``
    you can also use that instead.

.. warning::

    Please take special note when using the Doctrine ORM QueryBuilder.

    The WhereBuilder uses query-hints for the conversions, the QueryBuilder
    however does not support hints, so you must set them manually on the final query.

    .. code-block:: php

        $queryBuilder = $entityManager->createQueryBuilder();

        /* ... */

        $whereBuilder = $doctrineOrmFactory->createWhereBuilder($queryBuilder, $searchCondition);
        $whereBuilder->updateQuery();

        $query = $queryBuilder->getQuery();
        $query->setHint($whereBuilder->getQueryHintName(), $whereBuilder->getQueryHintValue());

Parameters
~~~~~~~~~~

Parameters are set on the Query object as "field_name_x" (x is an incrementing number).

If this for some reason needs to be changed, you can configure a parameter-prefix (before creating
the where-clause). A good case is using the FieldSet-name as prefix.

.. code-block:: php

    /* ... */

    $whereBuilder->setParameterPrefix('my_prefix');

Updating
~~~~~~~~

Once the WHERE clause is generated the query object must updated,
fortunately for the WhereBuilder provides a special method for this.

Plus, the query is only updated if there is an actual result.

.. code-block:: php

    /* ... */

    // Ask updateQuery() to append ' WHERE ' at the end of the current query
    // but before the generated WHERE-clause its self
    $whereBuilder->updateQuery(' WHERE ');

.. note::

    The WhereBuilder remembers whether the query is updated.
    Calling ``updateQuery()`` will do nothing, to force an update
    set the second parameter to ``true``.

    ``$whereBuilder->updateQuery(' WHERE ', true);``

Caching
~~~~~~~

Generating the where-clause can be very expensive for the system, so its advised
to cache the result for future page loads (like when using paginating).

Fortunately the where builder also has a caching system which can handle this
for you. The only thing you need to configure the is caching key, to ensure there
are no conflicts with other search conditions.

Were you first called updateQuery() on the WhereBuilder, you now call it
on the ``CacheWhereBuilder`` instance.

.. caution::

    Conversions that depend on something that varies per page request
    should can not be cached.

.. code-block:: php

    use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
    use Rollerworks\Component\Search\SearchCondition;

    /* ... */

    // The second parameter is the lifetime in seconds
    $cacheWhereBuilder = $doctrineOrmFactory->createCacheWhereBuilder($whereBuilder, 0);

    // You can set a static caching key
    $cacheWhereBuilder->setCacheKey('my_key');

    // Or you can use a callback/closure for generating the key
    $cacheWhereBuilder->setCacheKey(null, function () {
        return 'my_key';
    });

    // Now update the query, or you can call getWhereClause() and update the query manually

    $cacheWhereBuilder->updateQuery();

.. warning::

    Any changes to the metadata or Entity mapping are **not automatically** detected.
    Always use a Cache Driver that can be easily removed, like a PHP session.

.. _Doctrine ORM: http://www.doctrine-project.org/projects/orm.html
