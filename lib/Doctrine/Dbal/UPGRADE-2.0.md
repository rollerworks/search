UPGRADE FROM 2.0-ALPHA3 to 2.0-ALPHA8
=====================================

* `Rollerworks\Component\Search\Doctrine\Dbal\StrategySupportedConversion::getConversionStrategy`
  method not must return an integer. And requires a return-type is set.

UPGRADE FROM 1.x to 2.0
=======================

 * Support PHP 5 is dropped you need at least PHP 7.1

 * The Cache implementation now depends on PSR-16 (SimpleCache) instead
   of the Doctrine Cache library.
 
 * The `WhereBuilder` class had been renamed to `SqlConditionGenerator`.
 
 * The WhereBuilderInterface has been renamed to ConditionGenerator.
 
 * The CachedConditionGenerator (was CacheWhereBuilder) class no longer allows
   configuring the cache key. The Cache key is now automatically computed 
   based on the FieldSet (name), serialized root ValuesGroup and Field mappings
   (except field options).
 
 * Not setting a Cache implementation for the Factory will simple disable the cache
   and will no longer throw an exception when creating a CachedConditionGenerator.
 
 * The CachedConditionGenerator and SqlConditionGenerator now strictly implements
   the same interface. Meaning the CachedConditionGenerator can be used for configuring
   the decorated SqlConditionGenerator.
   
 * The `QueryField` properties are now public, all getter methods have been removed;
 
   The `column` property now includes the table alias (if any), use the `tableColumn` property
   to get actual column name with alias.
   
## Mapping

 * The prototype signature setField changed to 
   `setField(string $fieldName, string $column, string $alias = null, string $type = 'string')`.

 * The `setField` method now allows configure secondary mappings for the same
   field. This replaces the `setCombinedField` method.
   
   Before:

   ```php
   $conditionGenerator->setCombinedField('customer-name', [
       ['column' => 'first_name', 'type' => 'string', 'alias' => 'c'],
       ['column' => 'last_name', 'type' => 'string', 'alias' => 'c'],
   ]);
   ```

   After:

   ```php
   $conditionGenerator->setField('customer-name#first_name', 'first_name', 'c', 'string');
   $conditionGenerator->setField('customer-name#last_name', 'last_name', 'c', 'string');
   ```
   
   The name of a mapping begins with the field-name as registered in the FieldSet,
   followed by `#` and the name of the mapping (used for debugging).
   
   **Caution:** A field can only have multiple mappings or one, omitting `#` will remove
   any existing mappings for that field. Registering the field without `#` first and then
   setting multiple mappings for that field will reset the single mapping. 

 * The `setCombinedField` method has been removed.
 
 * The `setField` method no longer accepts DBAL types as object, use a string for the
   type instead.
  
## Conversions

 * Conversions can no longer be configured on a ConditionGenerator,
   use the FieldType to configure a Conversion for the field.
   
   The `getFieldConversions` and `getValueConversions` methods have been removed.

 * Conversions now always apply at the SQL level, the `ValueConversion::convertValue`
   method is now expected to return an SQL value statement.
   
   **Caution:** The value is not escaped by the search system, the conversion
   must handle proper escaping and quoting of the value!
   
   The `requiresBaseConversion` method is removed.
   
 * Renamed SqlFieldConversionInterface to ColumnConversion
 
 * Renamed SqlValueConversion to ValueConversion
 
 * Renamed ConversionStrategyInterface to StrategySupportedConversion
