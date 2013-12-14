Doctrine ORM
============

The Doctrine ORM component facilitates the searching of records
using Doctrine ORM.

.. toctree::
    :maxdepth: 1

    where_builder

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

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new DoctrineDbalExtension())
        ->addExtension(new DoctrineOrmExtension($managerRegistry))

        // ...
        ->getSearchFactory();

    // \Doctrine\Common\Persistence\ManagerRegistry
    $managerRegistry = ...;

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

Creating a new `WhereBuilder`_ is done by calling ``$doctrineOrmFactory->createWhereBuilder()`` and passing the
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

.. _WhereBuilder: WhereBuilder

.. _Doctrine ORM: http://www.doctrine-project.org/projects/orm.html
