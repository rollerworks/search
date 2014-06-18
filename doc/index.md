Doctrine Dbal
=============

The Doctrine Dbal extension facilitates the searching of records
using Doctrine Dbal.

**Note:** Before you can use the Doctrine Dbal extension,
make sure the [Doctrine Dbal][1] library is installed and configured.

If you installed this package using Composer, the Doctrine Dbal library is
already installed for you.

## Introduction

First you must enable the `DoctrineDbalExtension` for the `SearchFactoryBuilder`.

This will enable the extra-options for the search fields.

```php

use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
use Rollerworks\Component\Search\Extension\Core\CoreExtension;

$searchFactory = new Searches::createSearchFactoryBuilder()
    ->addExtension(new DoctrineDbalExtension())

    // ...
    ->getSearchFactory();
```

## Factory

The `DoctrineDbalFactory` class provides an entry point for creating
`WhereBuilder` and `CacheWhereBuilder` instances.

A created ``WhereBuilder`` instance is automatically configured with the options
of the fields registered in the FieldSet.

A `CacheWhereBuilder` is configured with the default cache-driver
of the `DoctrineDbalFactory`.

Initiating the DoctrineDbalFactory is as simple as.

```php
use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;

// \Doctrine\Common\Cache\Cache object
$doctrineCache = ...;

$doctrineDbalFactory = new DoctrineDbalFactory($doctrineCache);
```

The ``$doctrineCache`` can be any caching driver supported by Doctrine Cache library.
For best performance its advised to use a cache driver that stays persistent between page loads.

Creating a new `WhereBuilder` is done by calling `$doctrineDbalFactory->createWhereBuilder()` and passing the
Doctrine Dbal connection object as first parameter and the SearchCondition as second.

```php

use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineOrmFactory;
use Rollerworks\Component\Search\SearchCondition;

// \Doctrine\Common\Cache\Cache
$doctrineCache = ...;

// \Doctrine\Dbal\Driver\Connection
$connection = ...;

$doctrineDbalFactory = new DoctrineDbalFactory();
$searchCondition = ...;

$query = $entityManager->createQuery(...);
$whereBuilder = $doctrineDbalFactory->createWhereBuilder($connection, $searchCondition);
```

## WhereBuilder

The `WhereBuilder` class creates an SQL WHERE-clause for a relational
database like PostgreSQL, MySQL, SQLite or Oracle.

Usage of the `WhereBuilder` is very straightforward, note a ``WhereBuilder`` instance
is configured for one 'query' and SearchCondition.
So each query/condition requires a new instance.

**Note:**

> Its also possible to configure the `WhereBuilder` manually, but using the
> DoctrineDbalFactory is more recommended as it ensures all the options are properly applied.
>
> The examples shown below will be using the factory.

Before the query can be generated, the WhereBuilder needs to know which fields
belongs to which column and table/schema.

To set the so-called mapping configuration for the WhereBuilder,
call `WhereBuilder::setField()` for each of the fields in the FieldSet.

**Note:** Only fields at the WhereBuilder will be processed.
Fields that don't exist in the FieldSet will throw an exception.

```php
/**
 * Set Field configuration for the query-generation.
 *
 * @param string                           $fieldName Name of the Search-field
 * @param string                           $column    DB column-name
 * @param string|\Doctrine\Dbal\Types\Type $type      DB-type string or object
 * @param string                           $alias     alias to use with the column
 *
 * @return self
 *
 * @throws UnknownFieldException  When the field is not registered in the fieldset.
 * @throws BadMethodCallException When the where-clause is already generated.
 */
$whereBuilder->setField($fieldName, $column, $type = 'string', $alias = null);
```

Depending on your situation you can either choose to use a prepared-statement
or execute the query directly.

**Note:** Direct execution requires that values are embedded with the query.

To enable value-embedding pass `true` to the getWhereClause() method.
And use query() instead of prepare() on the connection object.

```php
use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\SearchCondition;

// \Doctrine\Common\Cache\Cache
$doctrineCache = ...;

// \Doctrine\Dbal\Driver\Connection
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
// Directly (with embedded values)
//

if ($whereClause = $whereBuilder->getWhereClause(true)) {
   $query .= ' WHERE '.$whereClause;
}

$queryStatement = $connection->query($query);
```

### Parameters

Unless embedded, the values are set as parameters on the query statement
as `field_name_x` (where x is an incrementing number).

When needed, you can configure a parameter-prefix (before creating
the where-clause). A good case is using the FieldSet-name as prefix.

```php
/* ... */

$whereBuilder->setParameterPrefix('my_prefix');
```

**Note:** Parameters are not used for values that are marked to be embedded.

### Caching

Generating the where-clause can be very expensive for the system, so its advised
to cache the result for future page loads (like when using paging).

Fortunately this system also a special `CachedWhereBuilder` class which can handle this
for you. The only thing you need to configure is the caching key (prevent conflicts).

**Note:** Were you first called bindParameters() on the WhereBuilder, you must now call it
on the created ``CacheWhereBuilder`` instance instead.

**Caution:** Conversions that are depended on something that varies per page request
should can not be cached. To avoid situations like this, you should do the conversion
using a user defined SQL function when possible.

```php
use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\SearchCondition;

/* ... */

// The second parameter is the cache lifetime in seconds
$cacheWhereBuilder = $doctrineDbalFactory->createCacheWhereBuilder($whereBuilder, 0);

// You can either set a static caching key
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
```

**Warning:** Changes to the mapping configuration are **not automatically** detected.
Always use a Cache Driver that can be easily removed, like a PHP session.

## Next Steps

Now that you have completed the basic installation and configuration,
you are ready to learn about more advanced features and usages of this extension.

* [Converters](converters.md)

[1]: http://www.doctrine-project.org/projects/dbal.html
