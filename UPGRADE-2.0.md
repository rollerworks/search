UPGRADE FROM 2.0-ALPHA2 to 2.0-ALPHA5
=====================================

* Support for using Regex in ValueMatch has been removed.
  
  * The constants `PatternMatch::PATTERN_REGEX` and `PatternMatch::PATTERN_NOT_REGEX`
    have been removed.
  * The method `PatternMatch::isRegex` has been removed.

UPGRADE FROM 2.0-ALPHA1 to 2.0-ALPHA2
=====================================

* The `ValueComparison` namespaces and classes were renamed to `ValueComparator`

* The `FieldConfig::setValueComparison` method was renamed to `setValueComparator`

* The `FieldConfig::getValueComparison` method was renamed to `getValueComparator`

UPGRADE FROM 1.x to 2.0-ALPHA1
==============================

* Support PHP 5 is dropped you need at least PHP 7.1

* Classes and interfaces now use strict type hints, considering
  the size of this change they are not listed in detail in this upgrade guide.

* `FilterQuery` is renamed to `StringQuery`.

* The `single` value-type is renamed to `simple`.

* Field alias support has been removed. Now only the `StringQuery` input processor
  and exporter allow to use an alias (or the label) as field name.
  
* The `Interface` suffix has been removed from interfaces, conflicting classes
  have been renamed to `Generic`. Eg. `SearchFactory` is now `GenericSearchFactory`.
  
* The Metadata/mapping system has been removed, creating reusable FieldSet's is now
  possible using `FieldSetConfigurator`s.
  
  ```php
  namespace Acme\User\Search;
  
  use Rollerworks\Component\Search\Searches;
  use Rollerworks\Component\Search\FieldSetBuilder;
  use Rollerworks\Component\Search\FieldSetConfigurator;
  use Rollerworks\Component\Search\Extension\Core\Type as FieldType;

  class UsersFieldSet implements FieldSetConfigurator
  {
      public buildFieldSet(FieldSetBuilder $builder)
      {
          $builder
              ->add('id', FieldType\IntegerType::class)
              ->add('last-name', FieldType\TextType::class)
              ->add('last-name', FieldType\TextType::class)
      }
  }

  // ...

  $searchFactory = Searches::createSearchFactory();
  $userFieldSet = $searchFactory->createFieldSet(\Acme\User\Search\UsersFieldSet::class);
  ```
  
  **Note:** If the FieldSetConfigurator has dependencies register it in a `FieldSetRegistry`
  instead. Eg using the `LazyFieldSetRegistry`:
  
  ```php
  use Acme\User\Search\UsersFieldSet;

  use Rollerworks\Component\Search\Searches;
  use Rollerworks\Component\Search\LazyFieldSetRegistry;
  
  $fieldSetRegistery = LazyFieldSetRegistry::create([
      UsersFieldSet::class => function () {
          return new UsersFieldSet();
      }
  ]);
  
  $searchFactory = Searches::createSearchFactoryBuilder()
      ->setFieldSetRegistry($fieldSetRegistery)
      ->getSearchFactory();
  ```
  
* A FieldSet is now immutable, use the `FieldSetBuilder` to semantically build
  a new FieldSet.
  
* The `FieldSet` interface was added, the old `FieldSet` class has been renamed
  to `GenericFieldSet`.
    
## Condition Exporter

 * The `XmlExporter` and `JsonExporter` now exports the SearchCondition
   with the value's normalized format instead of the view format.
  
 * The `StringQueryExporter` now allows to export the condition
   with newlines for better readability.

## Core Extension

 * The `model_class` and `model_property` options have been removed.
   Model configuration is no longer supported.
   
 * The `FieldType` class has been renamed to `SearchFieldType`.
 
 * The `getBlockPrefix` method was added to the `FieldType` interface,
   the `SearchFieldType` "base" type automatically configures this based of
   the type's name and vendor namespace.
   
 * The `DateTimeType`, `DateType`, `IntegerType`, `NumberType`, `TimestampType` 
   and `TimeType` were synchronized with the Symfony code base and may produce slightly
   different results then before.

### ChoiceType

 * The `choices_as_values` option of the ChoiceType has been removed.
 
 * The view format can now be configured using the `norm_format` option.
   Which can be either `value`, `label` or `auto` (which uses the best value).

 * Using callable strings as choice options in ChoiceType is not supported
   anymore in favor of passing `PropertyPath` instances.

   Before:

   ```php
   'choice_value' => new PropertyPath('range'),
   'choice_label' => 'strtoupper',
   ```

   After:

   ```php
   'choice_value' => 'range',
   'choice_label' => function ($choice) {
       return strtoupper($choice);
   },
   ```
   
 * Caching of the loaded `ChoiceListInterface` in the `LazyChoiceList` has been removed,
   it must be cached in the `ChoiceLoader` implementation instead.
   
### MoneyType

 * The `MoneyType` now uses the MoneyPHP library for handling transformation and calculation.
 
 * The `precession` option was removed, this is now based of the currency information.
 
 * The `increase_by` option was added to configure with simple-values to range optimization.

## Field
  
 * A number of field related classes were renamed and where moved to the `Field` namespace:
   * `AbstractFieldType`
   * `AbstractFieldTypeExtension`
   * `FieldConfig` (was `FieldConfigInterface`)
   * `FieldType` (was `FieldTypeInterface`)
   * `FieldTypeExtension` (was `FieldTypeExtensionInterface`)
   * `GenericResolvedFieldType` (was `ResolvedFieldType`)
   * `GenericResolvedFieldTypeFactory` (was `ResolvedFieldTypeFactory`)
   * `GenericTypeRegistry` (was `FieldRegistry`)
   * `ResolvedFieldType` (was `ResolvedFieldTypeInterface`)
   * `ResolvedFieldTypeFactory` (was `ResolvedFieldTypeFactoryInterface`)
   * `SearchField`
   * `SearchFieldView`
   * `TypeRegistry` (was `FieldRegistryInterface`)
  
 * The `SearchFieldView` now expects an `FieldSetView` as the first argument in the
   class constructor.
   
 * A search field no allows registering multiple transformers, each field
   can have exactly one "view" and/or "norm" transformer.

## Input processor

 * The `XmlInput` and `JsonInput` now expect the input values
   to be in the normalized data format rather then the view format.
   
 * The `XmlInput` and `JsonInput` now more strictly validate provided input.
   
 * The `StringQueryInput` has changed to a more user-friendly Lexer system:
   * User friendly error messages whenever possible.
   * All characters (except special syntax characters) can now be used without
     surrounding them with quotes.
   * Line numbers are now properly reported, and the column position is made more accurate.
   * Incorrectly escaped values will now give a friendly error message.
   * Spaces are no longer allowed between operators `~ >` this is invalid now.
 
 * The `StringQueryInput` now uses `~` for ranges, eg. `10 ~ 20`.
 
 * The `StringQueryInput` PatternMatch no longer requires an correct order
   for the flags. Both `i!` and `!i` are accepted now.
 
 * The structure of the XML and JSON changed to adapt to the new value-holder
   naming. In short this means that `simple` is used now rather then `single`.
   
 * Values merging is removed, using the field twice in a group
   overwrites the previously defined value in that group.

### Validation

 * Validation is now done directly during the input processing rather then
   afterwards. As a result, the produced `SearchCondition` no longer holds
   any invalid value. See also error handling below.

### Error handling

Error handling for input processing has been completely rewritten,
exceptions no longer needed to be parsed/transformed for usage.
But they can be translated if needed.

 * An Input processor now throws _only_ an `InvalidSearchConditionException`
   for user-input errors. The `InvalidSearchConditionException` actually holds
   one or more `ConditionErrorMessage` object instances.
   
   In practice this means that only one exception type needs to be cached,
   instead of 4.
   
 * The produced `SearchCondition` no longer holds any invalid value,
   only when all values are valid a `SearchCondition` is returned
   else an `InvalidSearchConditionException` is thrown.
 
 * The value-path of an error now depends on the structure of the input,
   XML uses XPath, while json uses an PropertyPath and StringQuery uses
   only value positions like `groups[0].fields['name']['value-position']`.
  
See the usage documentation for full instructions on handling user-input errors.

## Value

 * The `Rollerworks\Component\Search\Value\SingleValue` class has been removed,
   to add add simple value (as "model" format) use `addSimpleValue` on the `ValuesBag`.
   
 * The `Rollerworks\Component\Search\ValuesBag` now supports other custom value
   types instead of only Single, Ranges, Comparison, and PatternMatchers.
   
   The actual value is wrapped inside a `ValueHolder` object instance.
   
 * The `ValueHolder` is expected to a hold a "model" format of the value,
   eg. for a date-time input this is an `\DateTimeImmutable` object, for an integer
   this is a PHP primitive integer value.

 * All specific value methods have been removed.

   You must replace specific method types with: `add()`, `has()`, `get()` and `remove()`
   respectively. For example the Comparison value-type methods are used like:

   * `getComparisons()` becomes `get(\Rollerworks\Component\Search\Value\Compare::class)`
   * `hasComparisons()` becomes `has(\Rollerworks\Component\Search\Value\Compare::class)`
   * `removeComparison(1)` becomes `remove(\Rollerworks\Component\Search\Value\Compare::class, 1)`
   * `addComparisons(new Compare(..))` becomes `add(new Compare(..))`
   
 * A `ValueHolder` no longer holds a "view" format, to Exporter must use the viewTransformer of
   the search field to get a view representation of the value.
   
 * A SearchCondition's value structure cannot be locked anymore.

## Removed

The following classes/interfaces and method have been removed:

 * `Rollerworks\Component\Search\FieldConfig`:
     * `getModelRefClass()`
     * `getModelRefProperty()`
 
 * `Rollerworks\Component\Search\SearchFactory::createFieldForProperty()`
 
 * `Rollerworks\Component\Search\SearchField`:
     * `setRequired()`
     * `isRequired()`
     * `setModelRef()`
     * `getModelRefClass()`
     * `getModelRefProperty()`
 
 * `Rollerworks\Component\Search\Metadata\*`
 
 * `Rollerworks\Component\Search\Exception\FieldRequiredException`
 
 * `Rollerworks\Component\Search\Exception\ValuesStructureIsLocked`
 
 * `Rollerworks\Component\Search\FieldAliasResolverInterface`
 
 * `Rollerworks\Component\Search\FieldLabelResolverInterface`
 
 * `Rollerworks\Component\Search\SearchConditionInterface`
 
 * `Rollerworks\Component\Search\FieldAliasResolver\*`
 
 * `Rollerworks\Component\Search\ValuesBag`:
     * `setDataLocked()`
     * `isDataLocked()`
     * `ensureDataLocked()`
     * `ensureDataLocked()`
     
 * `Rollerworks\Component\Search\ValuesGroup`:
     * `setDataLocked()`
     * `isDataLocked()`
     * `throwLocked()`
