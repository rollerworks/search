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

    Make sure to also enable the ``DoctrineDbalExtension`` because some functionality is
    provided by DBAL, not ORM.

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
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\QueryBuilderConditionGenerator`,
:class:`Rollerworks\\Component\\Search\\Doctrine\\Orm\\CachedDqlConditionGenerator`,
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

The ConditionGenerator requires a ``Doctrine\ORM\QueryBuilder`` instance,
for NativeQuery use the :doc:`Doctrine DBAL </integration/doctrine/orm.rst>`
``ConditionGenerator`` instead.

.. caution::

    A ConditionGenerator is configured with the QueryBuilder object and SearchCondition.
    So reusing a ConditionGenerator instance is not possible.

    Secondly a generated where-clause is only applied once, calling the ``apply()``
    method twice will raise an PHP ``E_USER_WARNING``.

First create a ``ConditionGenerator``::

    // ...

    // Doctrine\ORM\EntityManagerInterface
    $entityManager = ...;

    $qb = $entityManager->createQueryBuilder();
    $qb
        ->select('I')
        ->from(\Acme\Entity\Invoice::class, 'I')
    ;

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($qb, $searchCondition);

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
     * @param string $alias     Table alias as used in the query "u" for `FROM Acme\Entity\Users AS u`
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
in fact required. A field mapping cannot function without an alias and Entity
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
     * @param string $alias  Table alias as used in the query "u" for `FROM Acme\Entity\Users AS u`
     *
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return $this
     */
    $conditionGenerator->setDefaultEntity(\Acme\Entity\Invoice, 'I');
    $conditionGenerator->setField('id', 'id');

    $conditionGenerator->setDefaultEntity(\Acme\Entity\Customer::class, 'C');
    $conditionGenerator->setField('customer', 'id', null, null);
    $conditionGenerator->setField('@customer', 'id'); // Sorting field (must be registered), without this sorting is not processed for this field.
    $conditionGenerator->setField('customer_first_name', 'firstName');
    $conditionGenerator->setField('customer_last_name', 'lastName');
    $conditionGenerator->setField('customer_birthday', 'birthday');

Only SearchFields in the FieldSet that have a column-mapping configured
will be processed (including sorting fields). All other SearchFields are ignored.

If you try to configure a field-mapping for a unregistered SearchField
the ConditionGenerator will fail with an exception.

.. caution::

    For DQL the column mapping of a field must point to the entity
    field that owns the value (not reference another Entity object).

    Given you have an ``Invoice`` Entity with a ``customer`` (``Customer``
    Entity) reference, the ``Customer`` Entity owns the the actual value
    and the field must point to the ``Customer.id`` field, **not**
    ``Invoice.customer``.

    If you point to a Join association the generator will throw an exception.

The ``$type`` (when given) must correspond to a Doctrine DBAL
supported type. So instead of using ``varchar`` you use ``string``.

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

    $qb = $entityManager->createQueryBuilder();
    $qb
        ->select('I')
        ->from(\Acme\Entity\Invoice::class, 'I')
        ->join('I.customer', 'C')
    ;

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($qb, $searchCondition);

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($statement, $searchCondition);

    $conditionGenerator->setDefaultEntity(\Acme\Entity\Invoice::class, 'I');
    $conditionGenerator->setField('id', 'id');

    $conditionGenerator->setDefaultEntity(\Acme\Entity\Customer, 'C');
    $conditionGenerator->setField('customer', 'id');
    $conditionGenerator->setField('@customer', 'id'); // The `@customer` field must be registered as ordering field
    $conditionGenerator->setField('customer_first_name', 'firstName');
    $conditionGenerator->setField('customer_last_name', 'lastName');
    $conditionGenerator->setField('customer_birthday', 'birthday');

Now apply the generated condition on the QueryBuilder and get the result::

    $conditionGenerator->apply();

    $invoices = $qb->getQuery()->execute();

.. tip::

    To prevent certain users from getting results they are not allowed to
    see you can combine the generated condition with a primary AND-condition.

    .. code-block:: php
        :linenos:

        // Doctrine\ORM\EntityManagerInterface
        $entityManager = ...;

        $qb = $entityManager->createQueryBuilder();
        $qb
            ->select('I')
            ->from(\Acme\Entity\Invoice::class, 'I')
            ->andWhere('C.id = :user_id') // Limit the invoices to a single user. Mapping the 'customer' field has no effect as this condition is primary.
            ->join('I.customer', 'C')
            ->setParameter('user_id', $id);

        // Rollerworks\Component\Search\SearchCondition object
        $searchCondition = ...;

        $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($qb, $searchCondition);
        // ...

        $conditionGenerator->apply();

        $invoices = $qb->getQuery()->execute();

    Or you can use a :ref:`pre_condition`.

Mapping a field to multiple columns
***********************************

Instead of searching in a single column it's possible to search in multiple
columns for the same SearchField. In practice this will work the same as using
the same values for other fields.

In the example below SearchField ``name`` will search in both the customer's ``first``
and ``last`` name columns (as ``OR`` case). And it's still possible to search
with only the first and/or last name.

.. code-block:: php

    // Doctrine\ORM\EntityManagerInterface
    $entityManager = ...;

    $qb = $entityManager->createQueryBuilder();
    $qb
        ->select('I')
        ->from(\Acme\Entity\Invoice::class, 'I')
        ->join('I.customer', 'C')
        ->setParameter('user_id', $id);

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $conditionGenerator = $doctrineOrmFactory->createConditionGenerator($qb, $searchCondition);
    $conditionGenerator->setDefaultEntity(\Acme\Entity\Customer, 'C');
    $conditionGenerator->setField('name#first', 'first');
    $conditionGenerator->setField('name#last', 'last');
    $conditionGenerator->setField('first-name', 'first');
    $conditionGenerator->setField('last-name', 'last');
    $conditionGenerator->apply();

.. note::

    Multi field-mapping is not possible for ordering fields, an ordering field
    always maps to a single field. And must include the leading ``@``-sign
    like ``@id``.

Caching the Where-clause
~~~~~~~~~~~~~~~~~~~~~~~~

Generating a Where-clause may require quite some time and system resources,
which is why it's recommended to cache the generated query for future usage.

Fortunately the factory allows to create a CachedConditionGenerator
which can handle caching of the ConditionGenerator for you.

Plus, usage is no different then using a regular ConditionGenerator
and can be configured very similar.

.. code-block:: php
    :linenos:

    // Doctrine\ORM\EntityManagerInterface
    $entityManager = ...;

    $qb = $entityManager->createQueryBuilder();
    $qb
        ->select('I')
        ->from(\Acme\Entity\Invoice::class, 'I')
        ->join('I.customer', 'C')
        ->setParameter('user_id', $id);

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    // The third argument is the cache lifetime in seconds (or anything supported by your cache implementation), null will use the Cache default
    $conditionGenerator = $doctrineOrmFactory->createCachedConditionGenerator($qb, $searchCondition, null);
    // ...

    $conditionGenerator->apply();

Next Steps
----------

Now that you have completed the basic installation and configuration,
and know how to query the database for results. You are ready to learn
about more advanced features and usages of this extension.

You may have noticed the word "conversions", now it's time learn more
about this! :doc:`conversions_orm`.

And if you get stuck with querying, there is a :doc:`Troubleshooter <troubleshooting>`
to help you. Good luck.

.. _`Doctrine ORM extension`: https://github.com/rollerworks/search-doctrine-orm
.. _`Doctrine ORM`: http://www.doctrine-project.org/projects/orm.html
.. _`Doctrine DBAL Types`: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
