README
======

[![Build Status](https://secure.travis-ci.org/rollerworks/RollerworksSearch.png?branch=master)](http://travis-ci.org/rollerworks/RollerworksSearch)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31/mini.png)](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rollerworks/RollerworksSearch/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rollerworks/RollerworksSearch/?branch=master)

What is RollerworksSearch?
--------------------------

RollerworksSearch provides you with a powerful system for integrating a search system
in your PHP application.

The system has a modular design and can work with any PHP framework,
user locale, data format or storage system.

Features
--------

Searches are performed using SearchConditions
which can be easily exported to any format.

A SearchConditions is build-up of fields and condition-groups.
Each field can hold any type of value including ranges, comparisons
and pattern matchers (starts/ends with contains and basic regex).

Condition-groups can be nested for more advanced and complex conditions.

## Input processors

Input processing is provided for the most common formats.

Including a special format called FilterQuery which provides
a user-friendly syntax for creating simple and complex conditions.

Each input processor transforms the input a normalized format,
and ensures that no malformed data is passed to the storage layer.

* Array
* JSON
* XML
* FilterQuery

## Optimizers

Optimizers help you with optimizing the SearchCondition for a better
and faster search-condition, including removing duplicated values and
normalizing redundant values.

## FieldTypes

The following types are provided out of the box, building your is also
possible and very straightforward.

> **Note: All types listed below support localization.

* Birthday (with optional support for Age conversion)
* Choice (array, entity list, custom implementation)
* Country choice
* Currency choice
* DateTime
* Date
* Integer
* Language choice
* Locale choice
* Money
* Number
* Text
* Timezone choice

## Storage/Index engines (condition processor)

Storage engines for searching (or condition processors) are provided
as separate packages. Building your own condition processor is also possible.

* [Doctrine2 DBAL](https://github.com/rollerworks/rollerworks-search-doctrine-dbal)
* [Doctrine2 ORM](https://github.com/rollerworks/rollerworks-search-doctrine-orm)
* Apache Solr (coming soon)
* Elasticsearch (coming soon)

Requirements
------------

You need at least PHP 5.3.3, and Intl extension for international support.

For framework integration you may use the following;

* [Symfony2 Bundle](https://github.com/rollerworks/RollerworksSearchBundle)
* [Symfony2 DependencyInjection](https://github.com/rollerworks/rollerworks-search-symfony-di)
* ZendFramework2 Plugin (coming soon)
* Silex Plugin (coming soon)

Installation
------------

For installing and integrating RollerworksSearch, you can find all the
details in the manual.

[doc/installing](doc/installing.rst)

Documentation
-------------

The documentation for RollerworksSearch is written in [reStructuredText][3] and can be built
into standard HTML using [Sphinx][4].

To build the documentation do the following:

1. Install [Spinx][4]
2. Change to the `docs` directory on the command line
3. Run `make html`

This will build the documentation into the `docs/_build/html` directory.

Further information can be found in The Symfony2 [documentation format][5] article.

> The Sphinx extensions are already included and don't need to be downloaded separately.

Versioning
----------

For transparency and insight into the release cycle, and for striving to maintain backward compatibility,
RollerworksSearch is maintained under the Semantic Versioning guidelines as much as possible.

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
Component, and contain a good amount code originally developed by the amazing
Symfony developers.

Documentation for types is also borrowed from the Symfony project.

License
-------

RollerworksSearch is provided under the none-restrictive MIT license.

[LICENSE](LICENSE)

Contributing
------------

This is an open source project. If you'd like to contribute,
please read the [Contributing Code][1] part of Symfony for the basics. If you're submitting
a pull request, please follow the guidelines in the [Submitting a Patch][2] section.

[1]: http://symfony.com/doc/current/contributing/code/index.html
[2]: http://symfony.com/doc/current/contributing/code/patches.html#check-list
[3]: http://docutils.sourceforge.net/rst.html
[4]: http://sphinx-doc.org/
[5]: http://symfony.com/doc/current/contributing/documentation/format.html
