README
======

[![Join the chat at https://gitter.im/rollerworks/search](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/rollerworks/search?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Build Status](https://secure.travis-ci.org/rollerworks/search.png?branch=master)](http://travis-ci.org/rollerworks/search)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31/mini.png)](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rollerworks/search/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rollerworks/search/?branch=master)

What is RollerworksSearch?
--------------------------

RollerworksSearch provides you with a powerful search system
for your PHP applications.

The system has a modular design and can work with any PHP framework,
user locale, data format or storage system.

Whether you want to simply search for users in your SQL database, want to
provide a powerful search system for searching products using an ElasticSearch
back-end or are looking for an easy way to abstract filtering for a reporter,
everything is possible.

And a complex data is structure is no problem, say your customer data is stored
in the "customer" table, the "invoices" data is stored in it's own table, and
the details of the invoice also have there own table.

Searching (using the FilterQuery syntax) can be as simple as:
`invoice_price: >"$20.00" invoice_row_label: ~*"my cool product"; customer_type: !consumer`.

You just searched in three relational tables using a single condition with a
user-friendly syntax. And that is just the start, checkout all the cool and
powerful features RollerworksSearch has to offer.

**Note:** FilterQuery is just one of the supported input formats, you can use XML,
JSON, PHP Array or simply build your own.

Features
--------

RollerworksSearch provides you with most of the features you would expect
from a search system. Including input processing, condition optimizing and
transforming user input to a normalized data-format. And everything can be
extended for your own use-cases and needs.

Search conditions can be as simple or complex as you need them to be.
Including grouping and nesting for the best possible result.

And to keep performance at a high rate, each search condition can be easily stored
in a persistent or session-based cache. On top of that each condition processor provides
it's own specialized caching mechanise to further reduce overhead.

*In practice this means that the generated (SQL) query for the DB or index is cached.*

And finally executing the search operation on the storage engine,
whether you use an SQL database system or using a lucene search back-end like
ElasticSearch or Apache Solr.

## Input processors

Input processing is provided for the most common formats.
Including a special format called FilterQuery which provides
a user-friendly syntax for creating any type of condition.

Each input processor transforms the input to a normalized format,
and ensures that no malformed data is passed to the storage layer.

* Array
* JSON
* XML
* FilterQuery

## Optimizers

Optimizers help you with optimizing SearchConditions for a better
and faster search-condition, including removing duplicated values and
normalizing redundant values.

## FieldTypes

The following types are provided out of the box, building your is also
possible and very straightforward.

**Tip:** All types listed below support localization.

* Birthday (with optional support for Age conversion)
* Choice (array, entity list, and custom implementation)
* Country choice
* Currency choice
* Timezone choice
* Language choice
* Locale choice
* DateTime
* Date
* Integer
* Money
* Number
* Text

## Storage/Index engines (condition processor)

Condition processors for searching in the storage engines are provided
as separate packages. Building your own condition processor is also possible.

* [Doctrine2 DBAL](https://github.com/rollerworks/search-doctrine-dbal)
* [Doctrine2 ORM](https://github.com/rollerworks/search-doctrine-orm)
* [Elasticsearch](https://github.com/rollerworks/search-elasticsearch) (coming soon)

Requirements
------------

You need at least PHP 5.4, and Intl extension for international support.

For framework integration you may use the following;

* [Symfony Bundle](https://github.com/rollerworks/SearchBundle)
* [Symfony DependencyInjection](https://github.com/rollerworks/search-symfony-di)
* [Symfony Validator](https://github.com/rollerworks/search-symfony-validator)
* ZendFramework2 Plugin (coming soon)
* Silex Plugin (coming soon)

Installation
------------

For installing and integrating RollerworksSearch, you can find all the
details in the manual.

[Installing](http://rollerworkssearch.readthedocs.org/en/latest/installing.html)

Documentation
-------------

[Read the Documentation for master][6]

The documentation for RollerworksSearch is written in [reStructuredText][3] and can be built
into standard HTML using [Sphinx][4].

To build the documentation do the following:

1. Install [Spinx][4]
2. Change to the `doc` directory on the command line
3. Run `make html`

This will build the documentation into the `doc/_build/html` directory.

Further information can be found in The Symfony [documentation format][5] article.

> The Sphinx extensions and theme are installed sing Git submodules
> and don't need to be downloaded separately.

Versioning
----------

For transparency and insight into the release cycle, and for striving
to maintain backward compatibility, RollerworksSearch is maintained under
the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backward compatibility bumps the major (and resets the minor and patch)
* New additions without breaking backward compatibility bumps the minor (and resets the patch)
* Bug fixes and misc changes bumps the patch

For more information on SemVer, please visit <http://semver.org/>.

Credits
-------

The field-type extensions are largely inspired on the Symfony Form
Component, and contain a good amount code originally developed by
the amazing Symfony developers.

Documentation for types and chapters are also borrowed from the
Symfony project.

License
-------

RollerworksSearch is provided under the none-restrictive MIT license,
you are free to use it for any free or proprietary product/application,
without restrictions.

[LICENSE](LICENSE)

Contributing
------------

This is an open source project. If you'd like to contribute,
please read the [Contributing Guidelines][1]. If you're submitting
a pull request, please follow the guidelines in the [Submitting a Patch][2] section.

[1]: https://github.com/rollerworks/contributing
[2]: https://contributing.readthedocs.org/en/latest/code/patches.html
[3]: http://docutils.sourceforge.net/rst.html
[4]: http://sphinx-doc.org/
[5]: https://contributing.readthedocs.org/en/latest/documentation/format.html
[6]: http://rollerworkssearch.readthedocs.org/en/latest/
