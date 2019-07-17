Searching with Doctrine ORM
===========================

This section of the manual explains how to install and configure the
`Doctrine ORM extension`_. The code samples assume you already have
`Doctrine ORM`_ set-up, and know how to write SQL/DQL queries.

Following the :doc:`installation instructions </installing>` install the
extension by running:

.. code-block:: bash

    $ php composer.phar require rollerworks/search-doctrine-orm

Enabling Integration
--------------------

And enable the :class:`Rollerworks\\Component\\Search\\Extension\\Doctrine\\Orm\\DoctrineOrmExtension`
*and* :class:`Rollerworks\\Component\\Search\\Extension\\Doctrine\\Dbal\\DoctrineDbalExtension`
for the ``SearchFactoryBuilder``. To adds extra options for registering :doc:`conversions`
and ensuring core types work properly.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;
    use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
    use Rollerworks\Component\Search\Extension\Doctrine\Orm\DoctrineOrmExtension;
    use Rollerworks\Component\Search\Extension\Core\CoreExtension;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new DoctrineDbalExtension())
        ->addExtension(new DoctrineOrmExtension())

        // ...
        ->getSearchFactory();

That's it, you can use RollerworksSearch with Doctrine ORM (and DBAL) support enabled.
Continue reading to learn how the query the database with a SearchCondition.

.. note::

    Make sure to also enable the ``DoctrineDbalExtension`` because columns and
    value conversions are provided by DBAL not ORM.

Querying the database
---------------------

As you already know RollerworksSearch uses a ``SearchFactory`` for bootstrapping
the search system. This factory however doesn't know about integration extensions.

To Query a database with Doctrine ORM extension, you use the
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\DoctrineOrmFactory`.

.. note::

    The ``DoctrineOrmFactory`` works next to the ``SearchFactory``.
    It's not a replacement for the ``SearchFactory``.

    You use the SearchFactory first, and the the DoctrineOrmFactory second.

The ``DoctrineOrmFactory`` class provides an entry point for creating
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\DqlConditionGenerator`,
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\CachedDqlConditionGenerator`,
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\NativeQueryConditionGenerator` and
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\CachedNativeQueryConditionGenerator`
object instances.

Initiating the ``DoctrineDbalFactory`` is as simple as::

    use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;

    // \Psr\SimpleCache\CacheInterface | null
    $cache = ...;

    $doctrineDbalFactory = new DoctrineOrmFactory($cache);

The ``$cache`` must a PSR-16 (SimpleCache) implementation, or can it
can be omitted to disable the caching of generated conditions.

See also: :doc:`/reference/caching`

Using the ConditionGenerator
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Depending on whether you use a ``Doctrine\ORM\Query`` or ``Doctrine\ORM\NativeQuery``
the returned ConditionGenerator will be different.

Both ConditionGenerators implement the same interface and API but the Where-clause
they will generate is completely different. Eg. you get an DQL or a platform
specific SQL condition.

.. caution::

    A WhereBuilder is configured with the Query object and SearchCondition.
    So reusing a WhereBuilder is not possible.

    Secondly, the generated query is only valid for the give query dialect
    or Database driver. Meaning that when you generated a query with the
    SQLite database driver this query will not work on MySQL.

First create a ``ConditionGenerator``::

    // ...

    // Doctrine\ORM\EntityManagerInterface
    $entityManager = ...;

    $statement = $entityManager->createQuery("SELECT i FROM Acme\Entity\Invoice AS i");

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($statement, $searchCondition);

Before the condition can be generated, the ConditionGenerator needs to know how
your search fields are mapped to which columns and Entity.
To configure this field-to-column mapping, use the ``setField`` method on the
ConditionGenerator::

    /**
     * Set the search field to Entity mapping mapping configuration.
     *
     * To map a search field to more then one entity field use `field-name#mapping-name`
     * for the $fieldName argument. The `field-name` is the search field name as registered
     * in the FieldSet, `mapping-name` allows to configure a (secondary) mapping for a field.
     *
     * Caution: A search field can only have multiple mappings or one, omitting `#` will remove
     * any existing mappings for that field. Registering the field without `#` first and then
     * setting multiple mappings for that field will reset the single mapping.
     *
     * Tip: The `mapping-name` doesn't have to be same as $property, but using a clear name
     * will help with trouble shooting.
     *
     * Note: Associations are automatically resolved, but can only work for a single
     * property reference. If resolving is not possible the property must be owned by
     * the entity (not reference another entity).
     *
     * If the entity field is used in a many-to-many relation you must to reference the
     * targetEntity that is set on the ManyToMany mapping and use the entity field of that entity.
     *
     * @param string $fieldName Name of the search field as registered in the FieldSet or
     *                          `field-name#mapping-name` to configure a secondary mapping
     * @param string $property  Entity field name
     * @param string $alias     Table alias as used in the query "u" for `FROM Acme:Users AS u`
     * @param string $entity    Entity name (FQCN or Doctrine aliased)
     * @param string $dbType    Doctrine DBAL supported type, eg. string (not text)
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return $this
     */
    $conditionGenerator->setField(string $fieldName, string $property, string $alias = null, string $entity = null, string $dbType = null);

The ``$alias`` and ``$entity`` arguments are marked optional, however they are
in fact required. A field mapping can not function with an alias an Entity
class.

But instead of having to supply this for every field you can set a default
alias an entity name using ``setDefaultEntity``. Which has an interesting feature.

Calling this method after calling ``setField`` will not affect fields that
were already configured. Which means you can use this method to configure
chunks of configuration.

.. code-block:: php

    // ...

    /**
     * Set the default entity mapping configuration, only for fields
     * configured *after* this method.
     *
     * Note: Calling this method after calling setField() will not affect
     * fields that were already configured. Which means you can use this
     * method to configure chunks of configuration.
     *
     * @param string $entity Entity name (FQCN or Doctrine aliased)
     * @param string $alias  Table alias as used in the query "u" for `FROM Acme:Users AS u`
     *
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return $this
     */
    $conditionGenerator->setDefaultEntity('Acme:Invoice', 'I');
    $conditionGenerator->setField('id', 'id');

    $conditionGenerator->setDefaultEntity('Acme:Customer', 'C');
    $conditionGenerator->setField('customer', 'id', null, null);
    $conditionGenerator->setField('customer_first_name', 'firstName');
    $conditionGenerator->setField('customer_last_name', 'lastName');
    $conditionGenerator->setField('customer_birthday', 'birthday');

.. note::

    The Entity alias must be properly configured for ``Acme:Invoice`` to
    work as shown in the following examples.

    .. code-block:: php

        $entityManager->getConfiguration()->addEntityNamespace('Acme', 'Acme\Entity');

Only SearchFields in the FieldSet that have a column-mapping configured
will be processed. All other SearchFields are ignored.

If you try to configure a field-mapping for a unregistered SearchField
the ConditionGenerator will fail with an exception.

.. caution::

    When using DQL, the column mapping of a field must point to the entity
    field that owns the value (not reference another Entity object).

    So if you have an ``Invoice`` Entity with a ``customer`` (``Customer``
    Entity) reference, the ``Customer`` Entity owns the the actual value
    and the field must point to the ``Customer.id`` field, **not**
    ``Invoice.customer``.

    If you point to a Join association the generator will throw an exception.
    This limitation only applies for DQL and not NativeQuery.

    In NativeQuery you must provide the ``$type`` as this cannot be
    automatically resolved.

The ``$type`` (when given) must correspond to a Doctrine DBAL
support type. So instead of using ``varchar`` you use ``string``.

See `Doctrine DBAL Types`_ for a complete list of types and options.

If you have a type which requires the setting of options you may need
to use a :ref:`ValueConversion <value_conversion>` instead.

After this you are ready to generate the query condition.

Generating the Condition
************************

.. code-block:: php
    :linenos:

    // ...

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

    $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($statement, $searchCondition);

    $conditionGenerator->setDefaultEntity('Acme:Invoice', 'I');
    $conditionGenerator->setField('id', 'id');

    $conditionGenerator->setDefaultEntity('Acme:Customer', 'C');
    $conditionGenerator->setField('customer', 'id', null, null);
    $conditionGenerator->setField('customer_first_name', 'firstName');
    $conditionGenerator->setField('customer_last_name', 'lastName');
    $conditionGenerator->setField('customer_birthday', 'birthday');

Now to apply the generated condition on the query you have two options;

You can use ``updateQuery`` which updates the query for you and sets
the Query-hints for DQL, but only when there is an actual condition generated::

    // ...

    $conditionGenerator->updateQuery();

    /* ... OR ... */

    // If the query has already has an `WHERE ` part you can
    // use ` AND ` instead, this will be placed before the generated condition.
    $conditionGenerator->updateQuery(' AND ');

Or if you want to do more with the generated condition, you can update
the query yourself::

    ...

    // The ' WHERE ' value is placed before the generated where-clause,
    // but only when there is actual where-clause, else it returns an empty string.
    $whereClause = $conditionGenerator->getWhereClause(' WHERE ');

    if (!empty($whereClause)) {
        $query->setDql($query.$whereClause);

        // The QueryHints are only needed for DQL Queries
        // the NativeWhereBuilder doesn't have these method.
        $query->setHint($conditionGenerator->getQueryHintName(), $conditionGenerator->getQueryHintValue());
    }

Effectively the two samples do the same, except that ``getQueryHintName``
and ``getQueryHintValue`` don't exist for the ``NativeQueryConditionGenerator``.

**Don't use ``updateQuery`` and the second example together, use only of the two.**

.. tip::

    To prevent certain users from getting results they are not allowed to
    see you can combine the generated condition with a primary AND-condition.

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

        $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($statement, $searchCondition);
        // ...

        $conditionGenerator->updateQuery(' AND '); // note the spaces around the statement

        $users = $statement->getResult();

    Or you can use a :ref:`pre_condition`.

Mapping a field to multiple columns
***********************************

Instead of searching in a single column it's possible to search in multiple
columns for the same SearchField. In practice this will work the same as using
the same values for other fields.

In the example below SearchField ``name`` will search in both the user's ``first``
and ``last`` name columns (as ``OR`` case). And it's still possible to search
with only the first and/or last name.

.. code-block:: php

    // Doctrine\ORM\EntityManagerInterface
    $entityManager = ...;

    $statement = $entityManager->createQuery("SELECT u FROM Acme\Entity\User AS u");

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($statement, $searchCondition);
    $conditionGenerator->setField('name#first', 'first');
    $conditionGenerator->setField('name#last', 'last');
    $conditionGenerator->setField('first-name', 'first');
    $conditionGenerator->setField('last-name', 'last');
    $conditionGenerator->updateQuery();

Caching the Where-clause
~~~~~~~~~~~~~~~~~~~~~~~~

Generating a Where-clause may require quite some time and system resources,
which is why it's recommended to cache the generated query for future usage.

Fortunately the factory allows to create a CachedConditionGenerator
which can handle caching of the ConditionGenerator for you.

Plus, usage is no different then using a regular ConditionGenerator,
the CachedConditionGenerator decorates the ConditionGenerator and can
be configured very similar.

.. note::

    There are two different CachedConditionGenerators, one for the
    ``DqlConditionGenerator`` and one for the
    ``NativeQueryConditionGenerator``.

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

    $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($statement, $searchCondition);
    // ...

    // The first parameter is the original ConditionGenerator as described above
    // The second parameter is the cache lifetime in seconds, null will use the Cache default
    $cacheWhereBuilder = $doctrineOrmFactory->createCacheWhereBuilder($conditionGenerator, null);

    // Call the updateQuery on the $cacheWhereBuilder NOT the $conditionGenerator itself
    // as that would break the purpose of having a cache.
    $cacheWhereBuilder->updateQuery();

    $users = $statement->getResult();

Conversions
-----------

Conversions for Doctrine ORM are similar to the DataTransformers
used for transforming user-input to a normalized data format. Except that
the transformation happens in a single direction.

Field and Value Conversions are handled by the :doc:`Doctrine DBAL extension <dbal>`.
You can read more about them in the :doc:`conversions` chapter.

.. note::

    Custom DQL-functions with the ``Column`` parameter receive the resolved
    entity-alias and column-name that the Query parser has generated. Because
    these functions only receive the column name of the current entity field
    it's impossible to know the table and column aliases of other fields.

Next Steps
----------

Now that you have completed the basic installation and configuration,
and know how to query the database for results. You are ready to learn
about more advanced features and usages of this extension.

And if you get stuck with querying, there is a :doc:`Troubleshooter <troubleshooting>`
to help you. Good luck.

.. _`Doctrine ORM extension`: https://github.com/rollerworks/search-doctrine-orm
.. _`Doctrine ORM`: http://www.doctrine-project.org/projects/orm.html
.. _`Doctrine DBAL Types`: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
