UPGRADE
=======

## Upgrade FROM v1.1.0 to v1.2.0
 
The `doctrine_orm_entity_count` (`EntityCountType`) is removed as this
type has been broken for a long time. 

Instead you can use the `doctrine_dbal_child_count` type, which require
manual configuration of the `table_name` and `table_column` for the child
relation.

## Upgrade FROM v1.0.0-beta2 to 1.0.0-beta3

This beta release is compatible with RollerworksSearch v1.0.0-beta5
and up. **You are highly recommended to upgrade as the old versions will
not receive any updates!**

Just like the RollerworksSearch package there has been some major
refactoring to make the Doctrine ORM search-condition processor more
robust and easier to use.

 * The Minimum version of the RollerworksSearch Doctrine DBAL extension
   package is v1.0.0-beta7. See [UPGRADE Instructions of RollerworksSearch Doctrine DBAL extension][1]
   for more details to update your conversions.
    
 * The WhereBuilder query handing logic has been completely rewritten.
   If you use the `DoctrineOrmFactory` your changes should be minimum.

 * Depending on whether you use `Doctrine\ORM\Query` (DQL) or `Doctrine\ORM\NativeQuery`
   (NativeQuery) the returned WhereBuilder will differ. *Both implement the
   same interface, but don't support all query objects.*
   
    * A DQL query will give you `Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder`
    * A NativeQuery will give you `Rollerworks\Component\Search\Doctrine\Orm\NativeWhereBuilder`
   
   The same is also done for the CacheWhereBuilder.
   
    * A DQL query will give you `Rollerworks\Component\Search\Doctrine\Orm\CacheWhereBuilder`
    * A NativeQuery will give you `Rollerworks\Component\Search\Doctrine\Orm\CacheNativeWhereBuilder`
  
 * Query parameters are completely removed, there were to much problems with
   type-binding and conversion handling. In practice this means all the values
   are safely embedded within in the generated query. As a bonus, it's also
   faster. The *getParameters* method is used as an internal detail and
   should not be called manually.
  
   Before:
    
   ```php
     $whereBuilder->updateQuery(' WHERE ', true);
   ```
   
   Or getting only the where-clause.
     
   ```php
     $whereBuilder->getWhereClause(true);
   ```
    
   After:
   
   ```php
     $whereBuilder->updateQuery(' WHERE ');
   ```
   
   Or getting only the where-clause.
     
   ```php
     // first parameter will be prepended to the returned where-clause,
     // only when there is a result.
     $whereBuilder->getWhereClause();
   ```
   
 * Method `updateQuery` no longer remembers if the query is updated, calling
   it will twice will update your query twice!
   
 * Using `setField`, the property must be owned by the entity (not reference another entity).
   If the entity field is used in a many-to-many relation you must to reference the
   targetEntity that is set on the ManyToMany mapping and use the entity field of that entity.
   
   This was already required but was never validated.
   
 * Fields with no model-mapping or using a model-class mapping that is not
   mapped in the WhereBuilder are now silently ignored.
   
 * Methods `getQueryHintName()` and `getQueryHintValue()` are only available
   on the `Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder` class.

[1]: https://github.com/rollerworks/rollerworks-search-doctrine-dbal/blob/master/UPGRADE.md

## Upgrade FROM v1.0.0-beta1 to 1.0.0-beta2

No changes required.
