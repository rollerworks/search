UPGRADE FROM 2.0-BETA1 to 2.0-BETA2
===================================

 * Support for PHP < 7.4 was dropped.
 
 * Support for Symfony < 4.4 was dropped.

 * Support for PHPUnit < 9.5 was dropped.

### Doctrine ORM

 * Support for passing a `Doctrine\ORM\Query` object in the generators was removed, 
   pass a `Doctrine\ORM\QueryBuilder` object instead.
   
  _This BC change was required to make applying of result-ordering possible without worrying
   to much about details and edge-cases._

 * The methods `getWhereClause()` and `getParameters()` on the ConditionGenerators were removed.
   _It's still possible to generate a stand-alone where-clause by using the `DqlConditionGenerator` directly, 
    but this is not officially supported nor documented._

 * The `createCachedConditionGenerator` of `DoctrineOrmFactory` now expects a
   a `QueryBuilder` and `SearchCondition` are provided instead of a ConditionGenerator.

   Before:
   
      ```php
      $generator = $ormFactory->createConditionGenerator($query, $searchCondition);
      $generator = $ormFactory->createCachedConditionGenerator($generator, 60 * 60);
      ```
   
   Now:
   
      ```php
      $generator = $ormFactory->createCachedConditionGenerator($query, $searchCondition, 60 * 60);
      ```
   
 * The `updateQuery()` method on the ConditionGenerators was renamed to `apply()` and no 
   longer supports a prepend for the query, as the query must now always be a `QueryBuilder`.

UPGRADE FROM 2.0-ALPHA23 to 2.0-ALPHA24
=======================================

 * The `html5` option for the `DateTimeType` has been removed.
   Only the RFC3339 for the norm-input format is supported now.

UPGRADE FROM 2.0-ALPHA21 to 2.0-ALPHA23
=======================================

 * The `$forceNew` argument in `SearchConditionBuilder::field()` is deprecated and will
   be removed in v2.0.0-BETA1, use `overwriteField()` instead.
    
### Doctrine DBAL

 * Support for SQLite was removed in Doctrine DBAL.

 * Values are no longer embedded but are now provided as parameters,
   make sure to bind these before executing the query.
   
   Before:
   
   ```php
   $whereClause = $conditionGenerator->getWhereClause();
   $statement = $connection->execute('SELECT * FROM tableName '.$whereClause);
   
   $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
   ```
   
   Now:
   
   ```php
   $whereClause = $conditionGenerator->getWhereClause();
   $statement = $connection->prepare('SELECT * FROM tableName '.$whereClause);
   
   $conditionGenerator->bindParameters($statement);

   $statement->execute();
   
   $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
   ```
   
 * The `Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion::convertValue()` method
   now expects a `string` type is returned, and requires a return-type.
   
 * Conversion strategies was changed to return a different column/value
   statement rather than keeping all strategies cached.
   
   Use the `ConversionHint` new parameters and helper method to determine
   the value for the Column.

### Doctrine ORM
   
 * Support for Doctrine ORM NativeQuery was removed, use the Doctrine DBAL
   condition-generator instead for this usage.
    
 * Values are no longer embedded but are now provided as parameters,
   make sure to bind these before executing the query.
   
   Note: Using the `updateQuery()` method already performs the binding process.
   
 * Doctrine DBAL conversions are no longer applied, instead the Doctrine ORM
   integration now has it's own conversion API with a much more powerful integration.
   
   **Note:** Any functions used in the conversion-generated DQL must be registered
   with the EntityManager configuration, refer to the Doctrine ORM manual for details. 
   

UPGRADE FROM 2.0-ALPHA19 to 2.0-ALPHA20
=======================================

 * The DataTransformers have been synchronized with the Symfony
   versions, which might cause some minor BC breakages.
   
   * The `BaseNumberTransformer` has been removed, 
     extend from `NumberToLocalizedStringTransformer` instead.
   * The `pattern` option of `DateTimeType` now only affects the
     view transformer, the norm transformer will use either the `DateTimeToRfc3339Transformer`
     or `DateTimeToHtml5LocalDateTimeTransformer` when the `html5` option is set to true.
   * The `precision` option of the `NumberType` has been renamed to `scale`.
   * The `IntegerType` no longer accepts float values.


UPGRADE FROM 2.0-ALPHA12 to 2.0-ALPHA13
=======================================

 * The ArrayInput processor has been removed.
 
 * ApiPlatform SearchConditionListener no longer supports array-input. 
   Use JSON or the NormStringQuery input-format instead.
   
 * The default restriction values of `ProcessorConfig` have been changed
   to provide a better default;
   
   * Maximum values per field is now 100 (was 1000)
   * Maximum number of groups is now 10 (was 100)
   * Nesting is now 5 (was 100)
   
   Unless you must support a higher number of values 
   it is advised to not increase these values.

UPGRADE FROM 2.0-ALPHA8 to 2.0-ALPHA12
======================================

### Core

 * The ConditionOptimizers have been removed.
 
 * The XmlInput processor has been removed.

### Processor

 * The SearchProcessor Component has been removed, use an InputProcessor directly.
   
   **Before:**
   
   ```php
   $inputProcessorLoader = Loader\InputProcessorLoader::create();
   $conditionExporterLoader = Loader\ConditionExporterLoader::create();    
   $processor = new Psr7SearchProcessor($searchFactory, $inputProcessorLoader, $conditionExporterLoader);
   
   $request = ...; // A PSR-7 ServerRequestInterface object instance
   
   $processorConfig = new ProcessorConfig($userFieldSet);
   $searchPayload = $processor->processRequest($request, $processorConfig);
   
   if ($searchPayload->isChanged() && $searchPayload->isValid()) {
       header('Location: /search?search='.$searchPayload->searchCode);
       exit();
   }
   
   if (!$payload->isValid()) {
       foreach ($payload->messages as $error) {
          echo (string) $error.PHP_EOL;
       }
   }
   
   // Notice: This is null when there are errors, when the condition is valid but has
   // no fields/values this is an empty SearchCondition object.
   $condition = $payload->searchCondition;
   ```
   
   **After:**
   
   ```php
   // ...
   
   $inputProcessor = new StringQueryInput(); // Can be wrapped in a CachingInputProcessor
   $processorConfig = new ProcessorConfig($fieldSet);
   
   $request = ...; // A PSR-7 ServerRequestInterface object instance
   
   try {
       if ($request->getMethod() === 'POST') {
           $query = $request->getQueryParams()['search'] ?? '';
           
           header('Location: /search?search='.$searchPayload->searchCode);
           exit();
           
           // return new RedirectResponse($request->getRequestUri().'?search='.$query);
       }
       
       $query = $request->getParsedBody()['search'] ?? '';
       $condition = $inputProcessor->process($processorConfig, $query);
       
       // Use condition
   } catch (InvalidSearchConditionException $e) {
       foreach ($e->getErrors() as $error) {
          echo (string) $error.PHP_EOL;
       }
   }
   ```
   
   **Note:** The ArrayInput processor has been removed, only string-type input
   formats (StringInput and JsonInput) are supported now.

### ApiPlatform

 * The `ApiSearchProcessor` has been removed. Internally the `SearchConditionListener`
   now handles the user-input and error handling.
 
 * The `SearchConditionListener` constructor has changed:
 
    **Before:**
 
    ```
    SearchFactory $searchFactory
    SearchProcessor $searchProcessor
    UrlGeneratorInterface $urlGenerator
    ResourceMetadataFactory $resourceMetadataFactory
    EventDispatcherInterface $eventDispatcher
    ```
   
    **After:**
   
    ``` 
    SearchFactory $searchFactory
    InputProcessorLoader $inputProcessorLoader
    ResourceMetadataFactory $resourceMetadataFactory
    EventDispatcherInterface $eventDispatcher
    CacheInterface $cache = null
    ```
    
    **Note:** The `$cache` argument is optional and only used when the `$cacheTTL`
    of the `ProcessorConfig` is configured.
    
 * Cache TTL configuration has been moved to `Rollerworks\Component\Search\Input\ProcessorConfig`, 
   the metadata configuration format has remained unchanged.
 
 * The Input format is now automatically detected by the first character.
   When the provided input starts with an `{` the `JsonInput` processor is used,
   otherwise the `NormStringQueryInput` processor is used.
   
 * ArrayInput is deprecated and is internally delegated to the JsonInputProcessor.
   
   **In RollerworksSearch v2.0.0-ALPHA12 support for ArrayInput is completely 
   removed and will throw an exception instead.**

UPGRADE FROM 2.0-ALPHA5 to 2.0-ALPHA8
=====================================

## ApiPlatform

* The `Rollerworks\Component\Search\ApiPlatform\EventListener\SearchConditionListener`
  now requires an `EventDispatchInterface` instance as last argument.
  
## Doctrine DBAL

* The `Rollerworks\Component\Search\Doctrine\Dbal\StrategySupportedConversion::getConversionStrategy`
  method must now return an integer (and is enforced with a return-type).

UPGRADE FROM 2.0-ALPHA2 to 2.0-ALPHA5
=====================================

* Support for using Regex in ValueMatch has been removed.
  
  * The constants `PatternMatch::PATTERN_REGEX` and `PatternMatch::PATTERN_NOT_REGEX`
    have been removed.

  * The method `PatternMatch::isRegex` has been removed.

UPGRADE FROM 2.0-ALPHA1 to 2.0-ALPHA2
=====================================

* The `ValueComparison` namespaces and classes have been renamed to `ValueComparator`

* The `FieldConfig::setValueComparison` method has been renamed to `setValueComparator`

* The `FieldConfig::getValueComparison` method has been renamed to `getValueComparator`

UPGRADE FROM 1.x to 2.0-ALPHA1
==============================

* Support PHP 5 has been dropped you need at least PHP 7.1.

* Classes and interfaces now use strict type hints, considering the size
  of this change they are not listed in detail in this upgrade guide.

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
      public buildFieldSet(FieldSetBuilder $builder): void
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
  
  **Note:** If the FieldSetConfigurator has constructor dependencies, register 
  it in a`FieldSetRegistry` instead. Eg. using the `LazyFieldSetRegistry`:
  
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
   and `TimeType` were synchronized with the Symfony code base and may produce
   slightly different results then before.

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
 
**Note:** The MoneyPHP library is not installed by default, install the "moneyphp/money"
package with Composer to use the `MoneyType`: `composer install moneyphp/money`.

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
  
 * The `SearchFieldView` now expects an `FieldSetView` as the first argument
   in the class constructor.
   
 * A search field no longer supports registering multiple transformers, each field
   can have exactly one "view" and/or "norm" transformer.

## Input processor

 * The `XmlInput` and `JsonInput` now expect the input values to be in the 
   normalized data format instead of the view format.
   
 * The `XmlInput` and `JsonInput` now more strictly validate provided input.
   
 * The `StringQueryInput` has changed to a more user-friendly Lexer system:
   
   * More User-friendly error messages.
   
   * All characters (except special syntax characters) can now be used without
     surrounding them with quotes. `12.00` is now accepted.
   
   * Line numbers are now properly reported, and the column position is made more accurate.
   
   * Incorrectly escaped values will now give a friendly error message.
   
   * Spaces are no longer allowed between operators. `~ >` is invalid now.
 
 * The `StringQueryInput` now uses `~` for ranges, eg. `10 ~ 20`.
 
 * The `StringQueryInput` PatternMatch no longer requires a specific order
   for the flags. Both `i!` and `!i` are accepted now.
 
 * The structure of the XML and JSON changed to adapt to the new value-holder
   naming. In short this means that `simple` is used now instead of `single`.
   
 * Field values merging is has been removed. Using the field twice in a group
   now overwrites the previously defined value in that group.

### Validation

 * Validation is now performed directly during the input processing rather then
   afterwards. As a result, the produced `SearchCondition` no longer holds
   any invalid value. See also error handling below.

### Error Handling

Error handling for input processing has been completely rewritten,
exceptions no longer require to be parsed for usage.

 * An Input processor now throws _only_ an `InvalidSearchConditionException`
   for user-input errors. The `InvalidSearchConditionException` holds one 
   or more `ConditionErrorMessage` object instances.

 * The produced `SearchCondition` no longer holds any invalid value,
   only when all values are valid a `SearchCondition` is returned
   else an `InvalidSearchConditionException` is thrown.
 
 * The value-path of an error now depends on the structure of the input,
   XML uses XPath, while json uses an PropertyPath and StringQuery uses
   only value positions like `groups[0].fields['name']['value-position']`.
  
See the usage documentation for full instructions on handling user-input errors.

## Value

 * The `Rollerworks\Component\Search\Value\SingleValue` class has been removed,
   to add add a simple-value (as "model" format) use `addSimpleValue` method on the 
   `ValuesBag` object.
   
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
   
 * A `ValueHolder` no longer holds a "view" format, an Exporter must use the viewTransformer
   of the search field to get a view representation of the value.
   
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
