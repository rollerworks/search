README
======

[![Build Status](https://secure.travis-ci.org/rollerworks/RollerworksSearch.png?branch=master)](http://travis-ci.org/rollerworks/RollerworksSearch)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31/mini.png)](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rollerworks/RollerworksSearch/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rollerworks/RollerworksSearch/?branch=master)

What is RollerworksSearch?
---------------------------

RollerworksSearch is an advanced search-engine framework.

RollerworksSearch provides you with a powerful system for building your own search-engine.
Input processing, normalizing, validating and every else. Save, fast and easy.

This system was designed to be as flexible as possible.
You are free to use your extensions, field-types etc.

Features
--------

RollerworksSearch works using SearchConditions.

A SearchConditions is build-up of fields and condition-groups.
Each field can hold any type of value including ranges, comparisons
and pattern matchers (starts/ends with contains and basic regex).

Condition-groups can be nested for more advanced and complex conditions.

Also, SearchConditions are easily exportable to any supported format.

## Input

Input processing is provided for the following formats.
But building your own input processor is also possible.

* Array
* JSON
* XML
* FilterQuery (an easy to learn and use condition based syntax).

## Formatter

A formatter is used for normalizing and validating values in a SearchCondition.
The bundles formatters are designed for the most common use-cases,
building your own is also possible.

* Validation (using the Symfony Validator component)
* Transformation (localized representation to a normalized format and reverse)
* Removing of duplicated values
* Optimizing of ranges: detecting and removing overlapping ranges
* Connected values to ranges (1,2,3,4,5 gets converted to 1-5)

## Types

The following types are packaged with this release (but can be replaced when needed).

> **Note: Each type listed below supports localization.

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

* IP-address (coming soon)
* Mac-address (coming soon)

## Storage/Index engines

> **Note: The listed engines are supported out of the box, but you are noted limited
> to these engines. Using something like a Webservice is also possible.

* [Doctrine2 DBAL](https://github.com/rollerworks/rollerworks-search-doctrine-dbal)
* [Doctrine2 ORM](https://github.com/rollerworks/rollerworks-search-doctrine-orm)
* Apache Solr (coming soon)
* Elasticsearch (coming soon)

Requirements
------------

You need at least PHP 5.3.3, and preferable the Intl extension
for international support.

For framework integration you use the following;

* [Symfony2 Bundle](https://github.com/rollerworks/RollerworksSearchBundle)
* [Symfony2 DependencyInjection](https://github.com/rollerworks/rollerworks-search-symfony-di)
* ZendFramework2 Plugin (coming soon)
* Silex Plugin (coming soon)

Installation
------------

For installing RollerworksSearch, you can find all the details about installing in the manual.

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
RollerworksSearch will be maintained under the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backward compatibility bumps the major (and resets the minor and patch)
* New additions without breaking backward compatibility bumps the minor (and resets the patch)
* Bug fixes and misc changes bumps the patch

For more information on SemVer, please visit <http://semver.org/>.

Credits
-------

The field-type extensions are largely inspired on the Symfony2 form
component, and contain a good amount code originally developed by the amazing
Symfony2 community.

Documentation for types is also borrowed from the Symfony2 project.

License
-------

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
