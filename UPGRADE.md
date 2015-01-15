UPGRADE
=======

## Upgrade FROM 1.0.0-beta4 to 1.0.0-beta5

There has been been some major refactoring to make the system more robust
easier to use. 

* The `Rollerworks\Component\Search\Formatter\TransformFormatter` is removed,
  transforming is now performed in the InputProcessor.

### Input

* Input processors are made reusable, configuration (limiting) must be passed as the
  first parameter of `Rollerworks\Component\Search\Input\InputProcessorInterface::process()`.
  
* When the created search-condition has errors an `InvalidSearchValuesException`
  will be thrown (after processing).
  
* Validation of ranges (correct bounds) is now performed when processing the Input (not after).
  
### User error handling

Because its possible that a search-condition contains errors, each processor
that has a transforming or validating role will throw a
`Rollerworks\Component\Search\Exception\InvalidSearchValuesException`.

The `InvalidSearchValuesException` provides access to the search-condition,
but the condition will contain some values that are invalid.
  
**Note:** The `InvalidSearchValuesException` is thrown *after* processing,
so it contains all invalid values (and not just the first violation) in a field values list.

A `ValuesGroup` object no longer keeps track of errors, getting the errors
is now done by asking each field in the group for there errors-state.

**Note:** By default only the fields at current level are checked,
pass `true` to `ValuesGroup::hasErrors()` to traverse the deeper
error-state of all nested groups.

### Values
  
* Value objects `Compare`, `SingleValue`, `Range`, `PatternMatch` are now marked as
  final and made immutable. To change a value, remove the original at the index and
  add the new value object to the ValuesBag.
  
* `PatternMatch::getViewValue()` was removed as this value-type has no view version.
  
* All view-values are casted to strings now;
  When no view value is provided the "normalized" value must support casting to a string!

## Upgrade from 1.0.0-beta2 to 1.0.0-beta3

RollerworksSearch is split to multiple smaller packages,
each providing an extension for the RollerworksSearch 'core' package.

* rollerworks-search-doctrine-dbal: Doctrine DBAL Searching support
* rollerworks-search-doctrine-orm:  Doctrine ORM Searching support

* rollerworks-search-symfony-validator: Symfony validator extension
* rollerworks-search-symfony-di: Symfony DependencyInjection extension (lazy loading)
* rollerworks-search-jms-metadata: JMS Metadata adapter extension

If you install any of the mentioned packages using composer,
the RollerworksSearch package will be automatic installed as well.

## UPGRADE FROM RollerworksRecordFilterBundle to RollerworksSearch

This project was formally called the RollerworksRecordFilterBundle.

If you like to switch to this new project please bare in mind
this library is completely rewritten, you should read the new documentation
to get started with the upgrade.

**Note:** The RollerworksRecordFilterBundle is discontinued, and will only receive
minor and security fixes until 2015. You are strongly advised to upgrade as soon as
possible.
