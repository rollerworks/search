UPGRADE FROM 1.x to 2.0
=======================

 * Support PHP 5 is dropped you need at least PHP 7.1

 * The Cache implementation now depends on PSR-16 (SimpleCache) instead
   of the Doctrine Cache library.
 
 * The `WhereBuilder` classes had been renamed to `ConditionGenerator`,
   DqlConditionGenerator and NativeQueryConditionGenerator respectively.
   
 * The WhereBuilderInterface has been renamed to ConditionGenerator.
 
 * The CachedConditionGenerator (was CacheWhereBuilder) class no longer allows
   configuring the cache key. The Cache key is now automatically computed 
   based on the FieldSet (name), serialized root ValuesGroup and Field mappings
   (except field options).
 
 * Not setting a Cache implementation for the Factory will simple disable the cache
   and will no longer throw an exception when creating a CachedConditionGenerator.
 
 * All ConditionGenerators now strictly implement the same interface.
   Meaning the CachedConditionGenerator can be used for configuring the decorated
   ConditionGenerator.
   
## Mapping

 * The Metadata system has been removed, and therefor Fields must be
   mapped explicitly to an Entity/Table and property/column.
   
   Before: 

   ```php
   $conditionGenerator->setEntityMapping('Acme\Entity\ECommerceInvoice', 'I');
   ```

   After:

   ```php
   $whereBuilder->setDefaultEntity(\Acme\Entity\ECommerceInvoice::class, 'I');
   $whereBuilder->setField('id', 'id');
   $whereBuilder->setField('label', 'label');
   $whereBuilder->setField('pub-date', 'date');
   $whereBuilder->setField('status', 'status');
   $whereBuilder->setField('total', 'total');
   ```
   
   **Note:** The setDefaultEntity only applies for fields set after the method call,
   so you can use it multiple times without affecting already mapped fields.
   
 * The prototype signature of setField changed to 
   `setField(string $fieldName, string $property, string $alias = null, string $entity = null, string $dbType = null)`.

 * The `setField` method now allows configure secondary mappings for the same
   field. This replaces the `setCombinedField` method.
   
   Before:

   ```php
   $conditionGenerator->setCombinedField('customer-name', [
       ['column' => 'first_name', 'alias' => 'c'],
       ['column' => 'last_name', 'alias' => 'c'],
   ]);
   ```

   After:

   ```php
   $conditionGenerator->setField('customer-name#first_name', 'first_name', 'c');
   $conditionGenerator->setField('customer-name#last_name', 'last_name', 'c');
   ```
   
   The name of a mapping begins with the field-name as registered in the FieldSet,
   followed by `#` and the name of the mapping (used for debugging).
   
   **Caution:** A field can only have multiple mappings or one, omitting `#` will remove
   any existing mappings for that field. Registering the field without `#` first and then
   setting multiple mappings for that field will reset the single mapping. 

 * The `setCombinedField` method has been removed.
 
 * The `setField` method no longer accepts DBAL types as object, use a string for the
   type instead.
   
 * The mapping configuration no longer allows to reference a JOIN column for the
   DQLConditionGenerator, the owning side of the Entity field must now be referenced instead.
   
   For the NativeQuery it is still possible but requires you provide the DBAL type explicitly.
  
## Conversions

 * Conversions can no longer be configured on a ConditionGenerator,
   use the FieldType to configure a Conversion for the field.
   
   The `getFieldConversions` and `getValueConversions` methods have been removed.
