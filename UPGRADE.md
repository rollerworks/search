UPGRADE
=======

## Upgrade FROM v1.0.0-beta6 to 1.0.0-beta7

* The `Rollerworks\Component\Search\Doctrine\Dbal\AbstractWhereBuilder`
  is merged with the `Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder`
  class, the old `AbstractWhereBuilder` class is removed.
  
## Upgrade FROM v1.0.0-beta5 to 1.0.0-beta6

No changes required.

## Upgrade FROM v1.0.0-beta4 to 1.0.0-beta5

No changes required.

## Upgrade FROM v1.0.0-beta3 to 1.0.0-beta4

This beta release is compatible with RollerworksSearch v1.0.0-beta5
and up. **You are highly recommended to upgrade as the old versions will
not receive any updates!**

Just like the RollerworksSearch package there has been some major
refactoring to make the Doctrine DBAL search-condition processor more
robust and easier to use.

* Query parameters are completely removed, there we to much problems with
  type-binding and conversion handling. In practice this means all the values
  are safely embedded within in the generated query. As a bonus, it's also
  faster.
  
  Before:
  
  ```php
    if ($whereClause = $whereBuilder->getWhereClause()) {
       $query .= ' WHERE '.$whereClause;
    }
    
    $queryStatement = $connection->prepare($query);

    if ($whereClause) {
        $cacheWhereBuilder->bindParameters($queryStatement);
    }
    
    /* ... */
    
    $queryStatement->execute();
    $cacheWhereBuilder->bindParameters($queryStatement);
  ```
  
  After:
  
  ```php
    if ($whereClause = $whereBuilder->getWhereClause()) {
       $query .= ' WHERE '.$whereClause;
    }
    
    $queryStatement = $connection->query($query);
  ```
  
* Hints for conversions are now passed as an `Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints`
  object instead of an array. The `ConversionHints` provides direct access to the properties.

  Before:
  
  ```php
    public function requiresBaseConversion($input, array $options, array $hints);
    public function convertValue($input, array $options, array $hints);
    public function convertSqlValue($input, array $options, array $hints);
    public function convertSqlField($column, array $options, array $hints);
  ```
  
  After:
  
  ```php
      public function requiresBaseConversion($input, array $options, ConversionHints $hints);
      public function convertValue($input, array $options, ConversionHints $hints);
      public function convertSqlValue($input, array $options, ConversionHints $hints);
      public function convertSqlField($column, array $options, ConversionHints $hints);
  ```
  
* The `original_value` and `value_object` parameters are no longer available
  for conversion hints.
  
* The internal cache structure of the `CacheWhereBuilder` has changed, purge
  your cache to remove any invalid entries.

The following changes don't affect how the system is used but may be of
interest.

* The internal classes are moved to the `Query` sub-namespace.
  You should not never use these classes directly. 

* Integers are no longer quoted as SQLite has some problems with automatic
  casting.
  
* The `AgeDateConversion` class (used by the `birthday` type) now only
  casts a column to a date when the column doesn't have the date type.
