Querying the database
=====================

Once you have successfully :doc:`installed <installing>` this extension
package. You can starting querying (searching) the database for results.

The process of querying the database happens after you have processed the
user's input and optimized the ``SearchCondition`` for better performance.

.. tip::

    You can choose to keep the querying logic anywhere, but it's best to
    keep it a central place like the Repository class or ``SearchService``
    class to prevent spreading your code all over the place.

As you already know RollerworksSearch uses a ``SearchFactory`` for handling
most of to boilerplate code to get starting. But you can't use this for
querying the database, so the Doctrine ORM extension comes with it's
own Factory :class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\DoctrineOrmFactory`.

Just the like the ``SearchFactory`` the ``DoctrineOrmFactory`` reduces
the amount of boilerplate code and helps you easily integrate this extension
within your application.

.. note::

    The ``DoctrineOrmFactory`` works next to the ``SearchFactory``.
    It's not a replacement for the ``SearchFactory``!

DoctrineOrmFactory
------------------

The ``DoctrineOrmFactory`` class provides an entry point for creating
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\WhereBuilder`,
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\CacheWhereBuilder`,
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\NativeWhereBuilder` and
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\CacheNativeWhereBuilder`
object instances and ensures the :doc:`conversions` are registered at the
WhereBuilder.

Initiating the ``DoctrineOrmFactory`` is as simple as.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;

    // \Doctrine\Common\Cache\Cache object
    $doctrineCache = ...;

    $doctrineDbalFactory = new DoctrineOrmFactory($doctrineCache);

The value of ``$doctrineCache`` can be any caching driver supported by
the `Doctrine Cache`_ library. For best performance it's advised to use
a cache driver that stays persistent between page loads.

Using the WhereBuilder
~~~~~~~~~~~~~~~~~~~~~~

Depending on whether you use the ``Doctrine\ORM\Query`` or
``Doctrine\ORM\NativeQuery`` the returned WhereBuilder object will differ.

Both WhereBuilder types implement the same interface and API but the Where-clause
they will generate is completely different. The WhereBuilder generates an SQL/DQL
Where-clause for the Doctrine ORM Query processor.

.. caution::

    A WhereBuilder is configured with the Query object and SearchCondition.
    So reusing a WhereBuilder is not possible.

    Secondly, the generated query is only valid for the give query dialect
    or Database driver. Meaning that when you generated a NativeQuery with
    the SQLite database driver this query will fail to work on MySQL.

First create a ``WhereBuilder``:

.. code-block:: php
    :linenos:

    /* ... */

    // Doctrine\ORM\EntityManagerInterface
    $entityManager = ...;

    $statement = $entityManager->createQuery("SELECT i FROM Acme\Entity\Invoice AS i");

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $whereBuilder = $doctrineDbalFactory->createWhereBuilder($statement, $searchCondition);

Now before the Where-clause can be generated, the WhereBuilder needs to
know which search-fields belongs to which entity field and class.

Configuring the search-field mapping can be done in two ways, the first
method is very easy and straight forward, the second method requires a
bit more work and is mainly recommended for multi-column and
self-referencing Joins or when the field has no model-mapping configured.

If you have a search-field who's entity is already mapped, then the
search-field mapping will prevail over the entity mapping.

.. note::

    The Entity alias must be properly configured for ``Acme:Invoice`` to
    work as shown in the following examples.

    .. code-block:: php

        $entityManager->getConfiguration()->addEntityNamespace('Acme', 'Acme\Entity');

Setting mapping per entity
**************************

The easiest way is to configure an 'Entity to alias' mapping using the
``setEntityMapping`` method.

.. code-block:: php

    /**
     * Set the entity mapping per class.
     *
     * @param string $entityName class or Doctrine alias
     * @param string $alias      Entity alias as used in the query.
     *                           Set to the null to remove the mapping
     */
    $whereBuilder->setEntityMapping($entityName, $alias)

The ``$entityName`` parameter must be either a fully-qualified class-name
``Acme\Entity\Invoice`` or Doctrine Entity alias ``Acme:Invoice``.

.. caution::

    If you have any Joins in the in the query which are used in for creating
    the Where-clause these must be configured also.

    So the following query ``SELECT i FROM Acme\Entity\Invoice JOIN i.details AS d``
    must have have an entity-mapping for the ``d`` alias as well.

    .. code-block:: php

        $whereBuilder->setEntityMapping('Acme\Entity\Invoice', 'i');
        $whereBuilder->setEntityMapping('Acme\Entity\InvoiceDetails', 'd');

Only fields in the FieldSet that have a model reference, and which referenced
model class is configured are used. Other fields are simply ignored.

.. note::

    If the model reference points to a single-column Join association
    the correct entity field is automatically resolved.

    The resolved parent entity must be configured, or else an exception
    will be thrown.

    If the model reference property is a multi-column join the you need
    to configure the field manually as described below.

Setting mapping per field
*************************

If you have a field that points to a self-referencing/multi column Join
or when the field has no model-mapping at all you can configure the where-builder
with an exact search field to entity field mapping using the ``setField`` method.

.. code-block:: php

    /**
     * Set Field configuration for the query-generation.
     *
     * Note: The property must be owned by the entity (not reference another entity).
     * If the entity field is used in a many-to-many relation you must to reference the
     * targetEntity that is set on the ManyToMany mapping and use the entity field of that entity.
     *
     * @param string             $fieldName   Name of the Search field
     * @param string             $alias       Entity alias as used in the query
     * @param string             $entity      Entity name (FQCN or Doctrine aliased)
     * @param string             $property    Entity field name
     * @param string|MappingType $mappingType Doctrine Mapping-type
     */
    $whereBuilder->setField($fieldName, $alias, $entity = null, $property = null, $mappingType = null)

The first parameter is the search-field name as registered in the used FieldSet,
followed by the entity-alias as used in the query. ``$entity`` and following
parameters are all optional and only required when there is no model-mapping
configured.

If ``$entity`` and/or ``$property`` are empty then the model-mapping of
the search-field is used instead.

.. caution::

    Unlike alias-mapping the, when configuring a field explicitly the
    configured model-reference must point to the entity field that owns
    the value (not reference another Entity object).

    So if you have an ``Invoice`` Entity with a ``customer`` (``Customer``
    Entity) reference, the ``Customer`` Entity owns the the actual value
    and the field must point to the ``Customer.id`` field, **not**
    ``Invoice.customer``!

    If you point a Join association the system will throw an exception.

The ``$mappingType`` (when given) must correspond to a Doctrine DBAL
support mapping type. So instead of using ``varchar`` you use ``string``.

See `Doctrine DBAL Types`_ for a complete list of types and options.

If you have a type which requires the setting of options you may need
to use a value_conversion instead.

Generating the Where-clause
***************************

Once the WhereBuilder is configured, it's time to generate the Where-clause.
The WhereBuilder will safely embed all values within the generated SQL query.

.. tip::

    The WhereBuilder embeds the values because any changes to the SearchCondition
    will also change the overall structure of the generated query, so using
    a prepared statement here would over complicate the code and actually
    slow down the searching process.

.. code-block:: php
    :linenos:

    ...

    // Doctrine\ORM\EntityManagerInterface
    $entityManager = ...;

    // Note. There's no need to add a 'WHERE' at the end of the query as this can be applied later
    // An empty SearchCondition produces an empty result, and thus would result in an invalid query.
    $query = '
        SELECT
            i
        FROM
            Acme\Entity\User AS u
        LEFT JOIN
            u.contacts AS c
    ';

    $statement = $entityManager->createQuery($query);

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $whereBuilder = $doctrineDbalFactory->createWhereBuilder($statement, $searchCondition);
    $whereBuilder->setEntityMapping('Acme\Entity\User', 'i');
    $whereBuilder->setEntityMapping('Acme\Entity\Contact', 'c');

Now to apply the generated Where-clause on the query you have two options;

You can update the query yourself.

.. code-block:: php

    ...

    // The ' WHERE ' value is placed before the generated where-clause,
    // but only when there is actual where-clause, else it returns an empty string.
    $whereClause = $whereBuilder->getWhereClause(' WHERE ');

    if (!empty($whereClause)) {
        $query->setDql($query.$whereClause);

        // The QueryHints are only needed for DQL Queries
        // the NativeWhereBuilder doesn't have these method.
        $query->setHint($whereBuilder->getQueryHintName(), $whereBuilder->getQueryHintValue()());
    }

Or you can use the ``updateQuery`` method which updates the query for you
and sets the Query hints for DQL, but only when there is actual where-clause.

.. code-block:: php

    ...

    $whereBuilder->updateQuery();

Just like the ``getWhereClause`` method the ``WHERE`` string is placed
before the generated where-clause. You can also to use ``AND`` if you
already have a ``WHERE`` part in the query.

.. tip::

    To prevent certain users from getting results they are not allowed to
    see you can combine the generated Where-clause with a primary AND-condition.

    .. code-block:: php
        :linenos:

        ...

        // Doctrine\ORM\EntityManagerInterface
        $entityManager = ...;

        // Note. There's no need to add a 'WHERE' at the end of the query as this can be applied later
        // An empty SearchCondition produces an empty result, and thus would result in an invalid query.
        $query = '
            SELECT
                i
            FROM
                Acme\Entity\User AS u
            LEFT JOIN
                u.contacts AS c
            WHERE
                u.id = :user_id
        ';

        $statement = $entityManager->createQuery($query);
        $statement->setParameter('user_id', $id);

        // Rollerworks\Component\Search\SearchCondition object
        $searchCondition = ...;

        $whereBuilder = $doctrineDbalFactory->createWhereBuilder($statement, $searchCondition);
        $whereBuilder->setEntityMapping('Acme\Entity\User', 'i');
        $whereBuilder->setEntityMapping('Acme\Entity\Contact', 'c');
        $whereBuilder->updateQuery(' AND '); // note the spaces around the statement

        $users = $statement->getResult();

Setting Conversions
*******************

Conversions are automatically registered using the ``DoctrineOrmFactory``,
but if you're not using the ``DoctrineOrmFactory`` or need to set conversions
manually you can still register them by calling ``setConverter($fieldName, $converter)``
on the WhereBuilder.

Caching the Where-clause
~~~~~~~~~~~~~~~~~~~~~~~~

Generating a Where-clause may require quite some time and system resources,
which is why it's recommended to cache the generated query for future usage.
Fortunately this package provides the a CacheWhereBuilder which can handle
caching of the WhereBuilder for you.

.. note::

    Just like the WhereBuilder there are two different CacheWhereBuilder,
    one for the ``WhereBuilder`` and one of the ``NativeWhereBuilder``.

Usage of the ``CacheWhereBuilder`` is very simple, the only thing you
need to configure is the cache-key for storing and finding the generated
query.

.. tip::

    The ``setCacheKey`` methods accepts eg. a fixed value like a string
    or a PHP supported callback to generate a unique cache-key.

    When you use a callback the the "original" WhereBuilder
    object is passed as the first (and only) parameter.

.. code-block:: php
    :linenos:

    // Doctrine\ORM\EntityManagerInterface
    $entityManager = ...;

    // Note. There's no need to add a 'WHERE' at the end of the query as this can be applied later
    // An empty SearchCondition produces an empty result, and thus would result in an invalid query.
    $query = '
        SELECT
            i
        FROM
            Acme\Entity\User AS u
        LEFT JOIN
            u.contacts AS c
        WHERE
            u.id = :user_id
    ';

    $statement = $entityManager->createQuery($query);
    $statement->setParameter('user_id', $id);

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $whereBuilder = $doctrineDbalFactory->createWhereBuilder($statement, $searchCondition);
    $whereBuilder->setEntityMapping('Acme\Entity\User', 'i');
    $whereBuilder->setEntityMapping('Acme\Entity\Contact', 'c');

    // The first parameter is the original WhereBuilder as described above
    // The second parameter is the cache lifetime in seconds, 0 means not expiring
    $cacheWhereBuilder = $doctrineDbalFactory->createCacheWhereBuilder($whereBuilder, 0);

    // You can use a static cache key
    $cacheWhereBuilder->setCacheKey('my_key');

    // Or you can use a callback/closure for generating a unique key
    $cacheWhereBuilder->setCacheKey(null, function ($whereBuilder) {
        return $whereBuilder->getSearchCondition()->getFieldSet()->getSetName();
    });

    // Call the updateQuery on the $cacheWhereBuilder NOT the $whereBuilder itself
    // as that would break the purpose of having a cache.
    $cacheWhereBuilder->updateQuery();

    $users = $statement->getResult();

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
