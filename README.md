RollerworksSearch Symfony Validator extension
=============================================

[![Build Status](https://secure.travis-ci.org/rollerworks/rollerworks-search-symfony-validator.svg?branch=master)](http://travis-ci.org/rollerworks/rollerworks-search-symfony-validator)

The RollerworksSearch Symfony Validator extension facilitates the validating
of SearchConditions for [RollerworksSearch][1] using the [Symfony Validator component][3].

**Note**: If you are new to RollerworksSearch, please read the main documentation
of [RollerworksSearch][1] before continuing.

If you'd like to contribute to this project, please see the [RollerworksSearch contributing guide lines][2].

Installation
------------

To install this extension, require the `rollerworks/search-symfony-validator`
package in your composer.json and update your dependencies.

```bash
$ composer require rollerworks/search-symfony-validator
```

Next you need to enable the `Rollerworks\Component\Search\Extension\Symfony\Validator\ValidatorExtension`
in the `SearchFactoryBuilder`. This search extension adds extra options
for configuring `constraints` and `validation_groups`.

```php
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\Extension\Symfony\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

$validatorBuilder = Validation::createValidatorBuilder();
$validator = $validatorBuilder->getValidator();

$searchFactory = new Searches::createSearchFactoryBuilder()
    ->addExtension(new ValidatorExtension($validator))

    // ...
    ->getSearchFactory();
```

After this you can use RollerworksSearch Symfony Validator extension.

Usage
-----

> Before you continue make sure you have a good understanding of what Constraints
> are and how they are to be used. See [Symfony Validator component][4] for
> more information.

### Constraints

**Note:** When you configure a model reference using the `model_class` and
`model_property` options the `ValidatorExtension` will automatically try
to load the constraints from the model-reference.

Set the `constraints` options value to overwrite the loaded constraints
or set an empty array to not perform any validation on the field's values.

To add validation constraints to your fields configure the `constraints`
option in the Search Field(Type).

You can configure the constraints object on the `SearchField` by using
the `configureOptions` method of your field type. Using:

```php
public function configureOptions(OptionsResolver $resolver)
{
    $resolver->setDefaults(
        array(
            'constraints' => new Assert\Length(array('min' => 101)),
            'validation_groups' => array('Default'),
        )
    );
}
```

Or you can configure the constraint on a per-field basis when building
your FieldSet:

```php
use Symfony\Component\Validator\Constraints as Assert;

// ..

$fieldSetBuilder = $searchFactory->createFieldSetBuilder('my_field_fieldSet')
$fieldSet->add('id', 'integer', array('constraints' => new Assert\Range(array('min' => 5))));
```

### Validating a SearchCondition

Validating a SearchCondition is very simple, note that only fields with
constraints are validated.

```php
use Symfony\Component\Validator\Validation;
use Rollerworks\Component\Search\Extension\Symfony\Validator\Validator;

// ... 

// First create the Symfony validator
$constraintsValidator = Validation::createValidator();
$conditionValidator = new Validator($constraintsValidator);

// Rollerworks\Component\Search\SearchConditionInterface object
$searchCondition = ...;

if (!$constraintsValidator->validate($searchCondition)) {
    // condition contains invalid values, show the errors
}
```

If the condition has invalid values the constraintViolations are converted
to `Rollerworks\Component\Search\ValuesError` objects and added to the
related ValuesBag object.

See the `displaySearchErrors` function in [Performing searches][5] for
more information on transforming the `ValuesError` objects to
user-friendly error messages.

License
-------

The source of this package is subject to the MIT license that is bundled
with this source code in the file [LICENSE](LICENSE).

[1]: https://github.com/rollerworks/RollerworksSearch
[2]: https://github.com/rollerworks/RollerworksSearch#contributing
[3]: https://github.com/symfony/Validator
[4]: http://symfony.com/doc/current/book/validation.html
[5]: http://rollerworkssearch.readthedocs.org/en/latest/searches.html#invalidsearchconditionexception
