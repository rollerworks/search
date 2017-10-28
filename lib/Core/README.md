RollerworksSearch
=================

[![Join the chat at https://gitter.im/rollerworks/search](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/rollerworks/search?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31/mini.png)](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rollerworks/search/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rollerworks/search/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/rollerworks/search/badge.svg?branch=master)](https://coveralls.io/github/rollerworks/search?branch=master)

## About RollerworksSearch

RollerworksSearch is a powerful search-system for PHP.
Created to make searching in a PHP powered application as simple and fast as possible.

Whether you want to simply search for users in your SQL database, want to
provide a powerful search system for searching products using an ElasticSearch
back-end or are looking for an easy way to abstract filtering for a reporter, 
it is possible.

> **Note:** The master branch is currently undergoing some major changes and is not
> considered ready for production usage yet, documentation is currently missing!
> Use the 1.x branch for a stable code base and "proper" documentation.

### How about complex data structures?

A complex data is structure is no problem, say your customer data is stored
in the "customer" table, the "invoices" data is stored in it's own table, and
the details of the invoices (obviously) have there own table.

Instead of writing a very verbose SQL query, your users can simple use 
the user-friendly StringQuery syntax:

> `invoice_price: > $20.00; invoice_row_label: ~*"my cool product"; customer_type: !consumer`.

You just searched in three relational tables using a single condition with a
user-friendly syntax. And that is just the start, RollerworksSearch can work with
any local, custom input format, or storage system.

**Coming-up:** SmartQuery, provide a number of terms and let the system
handle the condition building.

## Features

RollerworksSearch provides you with most of the features you would expect
from a search system, including:
 
* Input processing for the most common formats (XML and JSON).
  Plus, a special format called StringQuery with a user-friendly syntax.
* Condition optimizing for smaller memory usage and faster results.

And support for the most poplar storage systems.

* [Doctrine2 DBAL](https://github.com/rollerworks/search-doctrine-dbal)
* [Doctrine2 ORM](https://github.com/rollerworks/search-doctrine-orm)
* [ElasticSearch](https://github.com/rollerworks/search-elasticsearch) (coming soon)

Search conditions can be as simple or complex as you need them to be.
Including grouping and nesting for the best possible result.

## Framework integration

RollerworksSearch can be used with any Framework of your choice, but for the best
possible experience use the provided framework integration plug-ins.

* [Symfony Bundle](https://github.com/rollerworks/SearchBundle)
* ZendFramework2 Plugin (coming soon)
* Silex Plugin (coming soon)

Your favorite framework not listed? No problem, read the [Contributing Guidelines][2]
on how you can help!

## Installation and usage

*Please ignore the instructions below if your use a framework integration.*
[Read the Documentation for master][4] for complete instructions and information.

Install the RollerworksSearch "core" library using [Composer][1]:

```bash
$ composer install rollerworks/search
```

And create the `SearchFactory` to get started.

```php
use Rollerworks\Component\Search\Searches;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\ChoiceType;
use Rollerworks\Component\Search\Input\StringQueryInput;

// The factory is reusable, you create it only once.
$searchFactory = Searches::createSearchFactory();

// Create a fieldset to inform the system about your configuration.
// Usually you will have a FieldSet for each data structure (users, invoices, etc).
$userFieldSet = $searchFactory->createFieldSetBuilder()
    ->add('firstName', TextType::class)
    ->add('lastName', TextType::class)
    ->add('age', IntegerType::class)
    ->add('gender', ChoiceType::class, [
        'choices' => ['Female' => 'f', 'Male' => 'm'],
    ])
    ->getFieldSet('users');
    
// Now lets process a simple string query.
// Tip: the input processor is reusable.
$inputProcessor = new StringQueryInput();

try {
    // The ProcessorConfig allows to limit the amount of values, groups
    // and maximum nesting level.
    $processorConfig = new ProcessorConfig($userFieldSet);
    
    // The `process` method processes the input and produces 
    // a valid SearchCondition (or throws an exception when something is wrong).
    $condition = $inpurProcessor->process('firstName: sebastiaan, melany;');

    // Remove duplicate values and perform other optimizations (optional step).
    $searchFactory->optimizeCondition($condition);
} catch (InvalidSearchConditionException $e) {
    // Each error message can be easily transformed to a localized version.
    // Read the documentation for more details.
    foreach ($e->getErrors() as $error) {
       echo (string) $error.PHP_EOL;
    }
}
```

That's it! The `$condition` contains the search condition in a normalized
data format which can be used in a condition processor (like ElasticSearch), 
or be easily exported into another format like XML or JSON.

> **Note:** RollerworksSearch is composed of multiple separate packages (to keep the architecture slim), 
> the "core" package provides everything you need to get started.
>
> To actually perform a search operation in a (web) application, you would properly
> want to use the [rollerworks/http-search-processor](https://github.com/rollerworks/search-http-processor) 
(coming soon) to take care of the heavy lifting.

### What about validation?

Each field type ensures the value is transformed to the correct format,
eg. a date input is automatically transformed to a `DateTime` object.

A field that expects an integer will fail when the provided input is not an integer.

To enforce more strict constraints like a maximum amount for an integer field you 
can use a custom validator, read the documentation for supported implementations or creating
your own.

## Resources

* [Read the Documentation for master][4]
* RollerworksSearch is maintained under the [Semantic Versioning guidelines](http://semver.org/)

## Who is behind RollerworksSearch?

RollerworksSearch is brought to you by [Sebastiaan Stok](https://github.com/sstok).

## License

RollerworksSearch is released under the [MIT license](LICENSE).

The types and extensions are largely inspired on the Symfony Form Component, 
and contain a big amount of code from the Symfony project.

## Support

[Join the chat] or use the issue tracker if your question is to complex for quick support.

> **Note:** RollerworksSearch doesn't have a support forum at the moment, if you know
> a good free service let us know by opening an issue :+1:

## Contributing

This is an open source project. If you'd like to contribute,
please read the [Contributing Guidelines][2]. If you're submitting
a pull request, please follow the guidelines in the [Submitting a Patch][3] section.

[Join the chat at https://gitter.im/rollerworks/search](https://gitter.im/rollerworks/search?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[1]: https://getcomposer.org/doc/00-intro.md
[2]: https://github.com/rollerworks/contributing
[3]: https://contributing.readthedocs.org/en/latest/code/patches.html
[4]: http://rollerworkssearch.readthedocs.org/en/latest/
