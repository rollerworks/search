Searching with Doctrine DBAL
============================

This section of the manual explains how to install and configure the
`Doctrine DBAL extension`_. The code samples assume you already have
`Doctrine DBAL`_ set-up, and know how to write SQL queries.

Following the :doc:`installation instructions </installing>` install the
extension by running:

.. code-block:: bash

    $ php composer.phar require rollerworks/search-doctrine-dbal

Enabling Integration
--------------------

And enable the :class:`Rollerworks\\Component\\Search\\Extension\\Doctrine\\Dbal\\DoctrineDbalExtension`
for the ``SearchFactoryBuilder``. To adds extra options for registering :doc:`conversions`
and ensuring core types work properly.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;
    use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
    use Rollerworks\Component\Search\Extension\Core\CoreExtension;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new DoctrineDbalExtension())

        // ...
        ->getSearchFactory();

That's it, you can use RollerworksSearch with Doctrine DBAL support enabled.
Continue reading to learn how the query the database with a SearchCondition.

Querying the database
---------------------

As you already know RollerworksSearch uses a ``SearchFactory`` for bootstrapping
the search system. This factory however doesn't know about integration extensions.

To Query a database with the Doctrine DBAL extension, you use the
:class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\DoctrineDbalFactory`.

.. note::

    The ``DoctrineDbalFactory`` works next to the ``SearchFactory``.
    It's not a replacement for the ``SearchFactory``.

    You use the SearchFactory first, and the the DoctrineDbalFactory second.

The ``DoctrineDbalFactory`` class provides an entry point for creating
:class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\SqlConditionGenerator` and
:class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\CachedConditionGenerator`
object instances.

.. note::

    Both classes implement the :class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\ConditionGenerator`,
    a ConditionGenerator is not to be confused a SearchCondition.

    A ``ConditionGenerator`` generates an SQL where-clause 'condition'.

Initiating the ``DoctrineDbalFactory`` is as simple as::

    use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;

    // \Psr\SimpleCache\CacheInterface | null
    $cache = ...;

    $doctrineDbalFactory = new DoctrineDbalFactory($cache);

The ``$cache`` must a PSR-16 (SimpleCache) implementation, or can it
can be omitted to disable the caching of generated conditions.

See also: :doc:`/reference/caching`

Using the ConditionGenerator
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A ConditionGenerator generates an SQL Where-clause for a relational database
like PostgreSQL, MySQL, MSSQL, or Oracle OCI.

.. caution::

    A ConditionGenerator is configured with a Doctrine DBAL QueryBuilder and SearchCondition.
    So reusing a ConditionGenerator is not possible.

    Secondly, the generated query is only valid for the give Database driver.
    Meaning that when you generated a query with the PostgreSQL database driver
    this query will not work on MySQL.

First create a ``ConditionGenerator``::

    // ...

    // Doctrine\DBAL\Query\QueryBuilder object
    $qb = ...;

    // Rollerworks\Component\Search\SearchCondition object
    $searchCondition = ...;

    $conditionGenerator = $doctrineDbalFactory->createConditionGenerator($qb, $searchCondition);

Before the condition can be generated, the ConditionGenerator needs to know how
your fields are mapped to which columns and table/schema. To configure this
field-to-column mapping, use the ``setField`` method on the ConditionGenerator:

.. code-block:: php
    :linenos:

    /**
     * Set the search field to database table-column mapping configuration.
     *
     * To map a field to more then one column use `field-name#mapping-name`
     * for the $fieldName argument. The `field-name` is the field name as registered
     * in the FieldSet, `mapping-name` allows to configure a (secondary) mapping for a field.
     *
     * Caution: A field can only have multiple mappings or one, omitting `#` will remove
     * any existing mappings for that field. Registering the field without `#` first and then
     * setting multiple mappings for that field will reset the single mapping.
     *
     * Tip: The `mapping-name` doesn't have to be same as $column, but using a clear name
     * will help with trouble shooting.
     *
     * @param string $fieldName Name of the search field as registered in the FieldSet or
     *                          `field-name#mapping-name` to configure a secondary mapping
     * @param string $column    Database table column-name
     * @param string $alias     Table alias as used in the query "u" for `FROM users AS u`
     * @param string $type      Doctrine DBAL supported type, eg. string (not text)
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return $this
     */
    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string');

The first parameter is the search field-name as registered in the provided FieldSet
(with optionally a mapping-name to allow mapping a field to multiple columns).

Order fields must be prefixed with ``@`` as their actual name, either ``@id``.

Followed by the database column-name (without any quoting), the table alias that
corresponds with the table alias in the Query, and last the dbal-type
(as provided by Doctrine DBAL).

.. note::

    The db-type must correspond to a Doctrine DBAL supported Type.
    So instead of using ``varchar`` you use ``string``.

    See `Doctrine DBAL Types`_ for a complete list of types and options.

    If you have a type which requires the setting of options you may need
    to use a :ref:`value_conversion` instead.

.. caution::

    Only SearchFields in the FieldSet that have a column-mapping configured
    will be processed. All other fields are ignored.

    If you try to configure a column-mapping for a unregistered SearchField
    the ConditionGenerator will fail with an exception.

After configuring you are ready to generate the query condition.

Generating the Condition
************************

.. code-block:: php
    :linenos:

    // Doctrine\DBAL\Query\QueryBuilder object
    $qb = $connection->createQueryBuilder();

    // ...

    $qb
        ->select('u.name AS user_name', 'u.id AS user_id')
        ->from('users', 'u')
        ->leftJoin('u', 'contacts', 'c', 'c.user_id = u.id')
    ;

    $conditionGenerator = $doctrineDbalFactory->createConditionGenerator($qb, $searchCondition);

    // Set the field to column mapping
    $conditionGenerator->setField('user_id', 'u', 'id', 'integer');
    $conditionGenerator->setField('user_name', 'u', 'name', 'string');
    $conditionGenerator->setField('contact_name', 'c', 'name', 'string');

    // Apply the condition (with ordering, if any) to the QueryBuilder
    $conditionGenerator->apply();

    // Get all the records
    // See http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html#data-retrieval
    $result = $qb->execute();

.. tip::

    To prevent certain users from getting results they are not allowed to
    see you can add an addition and-condition the QueryBuilder
    or you can use a :ref:`pre_condition` for better portability.

Mapping a field to multiple columns
***********************************

Instead of searching in a single column it's possible to search in multiple
columns for the same field. In practice this will work the same as using
the same values for other fields.

In the example below field ``name`` will search in both the user's ``first``
and ``last`` name columns (as ``OR`` case). *And* it's still possible to search
with only the first and/or last name.

.. code-block:: php

    $conditionGenerator = $doctrineDbalFactory->createConditionGenerator($connection, $searchCondition);
    $conditionGenerator->setField('name#first', 'first', 'u', 'string');
    $conditionGenerator->setField('name#last', 'last', 'u', 'string');
    $conditionGenerator->setField('first-name', 'first', 'u', 'string');
    $conditionGenerator->setField('last-name', 'last', 'u', 'string');

Caching the Where-clause
~~~~~~~~~~~~~~~~~~~~~~~~

Generating a Where-clause may require quite some time and system resources,
which is why it's recommended to cache the generated query for future usage.

Fortunately the factory allows to create a ``CachedConditionGenerator``
which can handle caching of the ConditionGenerator for you.

Plus, usage is no different then using the ``SqlConditionGenerator``,
the ``CachedConditionGenerator`` decorates the ``SqlConditionGenerator``
and can be configured very similar::

    // ...

    // The third parameter is the cache lifetime in as supported by PSR-16
    $cacheConditionGenerator = $doctrineDbalFactory->createCachedConditionGenerator($qb, $conditionGenerator, null);
    $cacheConditionGenerator->setField('first-name', 'first', 'u', 'string');
    $cacheConditionGenerator->setField('last-name', 'last', 'u', 'string');

    // Apply the condition (with ordering, if any) to the QueryBuilder
    $cacheConditionGenerator->apply();

The cache-key is a hashed (sha256) combination of the SearchCondition
(root ValuesGroup and FieldSet set-name) and configured field mappings.

.. note::

    Changes in the FieldSet's Fields configuration are not automatically
    detected. Keep your cache life-time short and purge existing entries
    when changing your FieldSet configurations.

Next Steps
----------

Now that you have completed the basic installation and configuration,
and know how to query the database for results. You are ready to learn
about more advanced features and usages of this extension.

You may have noticed the word "conversions", now it's time learn more
about this! :doc:`conversions`.

And if you get stuck with querying, there is a :doc:`Troubleshooter <troubleshooting>`
to help you. Good luck.

.. _`Doctrine DBAL Types`: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
.. _`Doctrine DBAL extension`: https://github.com/rollerworks/search-doctrine-dbal
.. _`Doctrine DBAL`: http://www.doctrine-project.org/projects/dbal.html
