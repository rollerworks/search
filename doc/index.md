Doctrine ORM
============

The Doctrine ORM extension facilitates the searching of records
using Doctrine ORM (both NativeQuery and DQL).

**Note:** Before you can use the Doctrine ORM extension,
make sure the [Doctrine ORM][1] library is installed and configured.

You also need the Doctrine Dbal extension for Rollerworks Search.

If you installed this package using Composer, the Doctrine ORM library is
already installed for you.

## Introduction

First you must enable the `DoctrineOrmExtension` and `DoctrineDbalExtension`
for the `SearchFactoryBuilder`.

This will enable the extra-options for the search fields.
And configures the EntityManagers for usage of transformers.

```php

use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\Extension\Core\CoreExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\DoctrineOrmExtension;

// \Doctrine\Common\Persistence\ManagerRegistry
$managerRegistry = ...;

$searchFactory = new Searches::createSearchFactoryBuilder()
    ->addExtension(new DoctrineDbalExtension())
    ->addExtension(new DoctrineOrmExtension($managerRegistry))

    // ...
    ->getSearchFactory();
```


## Factory

The `DoctrineOrmFactory` class provides an entry point for creating
`WhereBuilder` and `CacheWhereBuilder` instances.

A created `WhereBuilder` instance is automatically configured with the options
of the fields registered in the FieldSet.

A `CacheWhereBuilder` is configured with the default cache-driver
of the `DoctrineOrmFactory`.

Initiating the DoctrineOrmFactory is as simple as.

```php
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;

// \Doctrine\Common\Cache\Cache
$doctrineCache = ...;

$doctrineOrmFactory = new DoctrineOrmFactory($doctrineCache);
```

The `$doctrineCache` can be any caching driver supported by Doctrine Cache library.
For best performance its advised to use a cache driver that stays persistent between page loads.

Creating a new `WhereBuilder` is done by calling `$doctrineOrmFactory->createWhereBuilder()`
and passing the Doctrine ORM `Query` object as first parameter and the SearchCondition as second.

**Note:** The WhereBuilder also accepts `NativeQuery` and `QueryBuilder`.

```php
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
```

## WhereBuilder

The `WhereBuilder` class creates a WHERE-clause for the Doctrine ORM Query processor.

Usage of the `WhereBuilder` is very straightforward, note that a `WhereBuilder` instance
is configured for one 'query' and SearchCondition.
So each query/condition requires a new instance.

**Note:**

> Its also possible to configure the `WhereBuilder` manually, but using the
> DoctrineDbalFactory is more recommended as it ensures all the options
> are properly applied.
>
> The examples shown below will be using the factory.

### Mapping configuration

Before the query can be generated, the WhereBuilder needs to know which fields
belongs to which column and table/schema.

**Caution:**

> The Doctrine ORM WhereBuilder requires that each field has a property reference,
> which equals the Entity class-name. And Doctrine Mapping is configured properly.
>
> You can not use property reference `EntityA::id` and configure an explicit mapping
> to `EntityB::id`.

If you only "select" from a single entity the WhereBuilder will be configured for you,
but when you use Joins the mapping needs to be configured manually.

**Tip:** If you configured Doctrine to support the short entity notation ``AcmeUser:User``
you can also use those instead.

The easiest way is to configure an 'Entity to alias' is done using `setEntityMapping()`.

```php
/**
 * Set the entity mapping per class.
 *
 * @param string $entity class or Doctrine alias
 * @param string $alias  Entity alias as used in the query.
 *                       Set to the null to remove the mapping
 *
 * @return self
 *
 * @throws BadMethodCallException When the where-clause is already generated.
 */
public function setEntityMapping($entity, $alias);
```

To set multiple entity in one time, use the `setEntityMappings()` method instead.

```php
/**
 * Set the entity mappings.
 *
 * Mapping is set as [class] => in-query-entity-alias.
 *
 * Caution. This will overwrite any configured entity-mappings.
 *
 * @param array $mapping
 *
 * @return self
 *
 * @throws BadMethodCallException When the where-clause is already generated.
 */
public function setEntityMappings(array $mapping);
```

**Note:** If you have a more complex condition (like an 'inner join'), you must configure an
explicit search field-name to alias for each inner joining field, using `setFieldMapping()`.

```
/**
 * Set the entity mapping for a field.
 *
 * Use this method for a more explicit mapping.
 * By setting the mapping for the field, the builder
 * will use the specific alias instead of the globally configured one.
 *
 * Example if ClassA is mapped to alias A, but FieldB (model A)
 * needs a special alias reference you can set it as alias FieldB => AB.
 *
 * @param string      $fieldName FieldName as registered in the fieldset
 * @param string|null $alias     Entity alias as used in the query.
 *                               Set to the null to remove the mapping
 *
 * @return self
 *
 * @throws UnknownFieldException  When the field is not registered in the fieldset.
 * @throws BadMethodCallException When the where-clause is already generated.
 */
public function setFieldMapping($fieldName, $alias);
```

**Tip:** You can use both `setEntityMapping()` and `setFieldMapping()` without conflict,
the WhereBuilder always check the explicit field-name to alias before using the global
entity to alias mapping.

### Using the WhereBuilder

Now that the mapping is set, the WhereBuilder is ready for usage.

```php
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\SearchCondition;

// \Doctrine\Common\Cache\Cache
$doctrineCache = ...;

// \Doctrine\ORM\EntityManager
$entityManager = ...;

$doctrineOrmFactory = new DoctrineOrmFactory();
$searchCondition = ...;

/* ... */

// Note. There's no need to add a 'WHERE' at the end of the query as this can be applied later
// An empty SearchCondition produces an empty result, and thus would result in an invalid query.
$query = $entityManager->createQuery('SELECT u FROM User u');

$whereBuilder = $doctrineOrmFactory->createWhereBuilder($query, $searchCondition);
```

**Caution:**

> Searching with joined entities might cause duplicate results.
> Use DISTINCT on the unique ID of the 'parent' table to remove any duplicates.
>
> The duplicate results happen because the database is asked to return all matching
> records, and one parent record may produce multiple matching children.

```php
/* ... */

$dql = 'SELECT u, g FROM Acme\User\Entity\User u JOIN User.group g WHERE';

$query->setDql($dql);

$entityAliases = array(
    'Acme\User\Entity\User' => 'u'
    'Acme\User\Entity\Group' => 'g'
);

$whereBuilder->setEntityMappings($entityAliases);

// Note: Make sure you only do this when the search condition actually
// has values, its better to use updateQuery() (see below)
$dql .= $whereBuilder->getWhereClause();
```

### QueryBuilder notes

Please take special note when using the Doctrine ORM QueryBuilder.

The WhereBuilder uses query-hints for handling conversions, the QueryBuilder
however does not support hints, so you must set these manually on the final query.

```php
$queryBuilder = $entityManager->createQueryBuilder();

/* ... */

$whereBuilder = $doctrineOrmFactory->createWhereBuilder($queryBuilder, $searchCondition);
$whereBuilder->updateQuery();

$query = $queryBuilder->getQuery();
$query->setHint($whereBuilder->getQueryHintName(), $whereBuilder->getQueryHintValue());
```

Second, the updateQuery() should not be called with value as this may result
in an invalid Query.
```

### Parameters

The values are set as parameters on the query statement
as `field_name_x` (where x is an incrementing number).

When needed, you can configure a parameter-prefix (before creating
the where-clause). A good case is using the FieldSet-name as prefix.

```php
/* ... */

$whereBuilder->setParameterPrefix('my_prefix');
```

### Query updating

Once the WHERE clause is generated the query object must updated,
fortunately for the WhereBuilder provides a special method for this.

Plus, the query is only updated if there is an actual result.

```php

/* ... */

// Ask updateQuery() to append ' WHERE ' at the end of the current query
// but before the generated WHERE-clause its self
$whereBuilder->updateQuery(' WHERE ');
```

**Note:** The WhereBuilder remembers whether the query is updated.
If the query is already updated, calling `updateQuery()` will do nothing,
to force an update set the second parameter to `true`.

`$whereBuilder->updateQuery(' WHERE ', true);`

### Caching

Generating the where-clause can be very expensive for the system, so its advised
to cache the result for future page loads (like when using paging).

Fortunately this system also a special `CachedWhereBuilder` class which can handle this
for you. The only thing you need to configure is the caching key (prevent conflicts).

**Note:** Were you first called updateQuery() on the WhereBuilder, you now call it
on the `CacheWhereBuilder` instance. Mapping data is never set on the `CachedWhereBuilder`!

**Caution:** Conversions that are depended on something that varies per page request
should can not be cached. To avoid situations like this, you should do the conversion
using a user defined SQL function when possible.

```php
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\SearchCondition;

/* ... */

// The second parameter is the cache lifetime in seconds
$cacheWhereBuilder = $doctrineOrmFactory->createCacheWhereBuilder($whereBuilder, 0);

// You can set a static caching key
$cacheWhereBuilder->setCacheKey('my_key');

// Or you can use a callback/closure for generating the key
$cacheWhereBuilder->setCacheKey(null, function () {
    return 'my_key';
});

// Now update the query
// or you can call getWhereClause() and update the query manually

$cacheWhereBuilder->updateQuery();
```

**Warning:** Changes to the mapping configuration are **not automatically** detected.
Always use a Cache Driver that can be easily removed, like a PHP session.

## Next Steps

Now that you have completed the basic installation and configuration,
you are ready to learn about more advanced features and usages of this extension.

Note, that this Extension depends heavily on the Dbal extension.
And some documentation can be found externally.

* [Converters](https://github.com/rollerworks/rollerworks-search-doctrine-dbal/blob/master/doc/converters.md)

[1]: http://www.doctrine-project.org/projects/orm.html
