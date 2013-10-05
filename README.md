README
======

[![Build Status](https://secure.travis-ci.org/rollerworks/RollerworksSearch.png?branch=master)](http://travis-ci.org/rollerworks/RollerworksSearch)

What is Rollerworks Search?
---------------------------

Rollerworks Search is an advanced search-building framework.

Providing some powerful basics for building your own site search-engine.
From input to formatting and validating, and finally applying condition to the
storage engine.

This system was designed to be as expendable as possible.
Everything, and absolute everything can be replaced with your implementation.

Features
--------

The searching condition is build of fields and groups.
Each field have any-type of value including ranges, comparisons
and matchers (starts/ends with contains).

Groups can be nested at any level of depth.

## Input

Input processing is possible for the following formats by default.

* Array
* JSON
* XML
* FilterQuery (an easy to learn and use formula-based input format).

> Each provided input format also provides an related exporter component.

## Formatter

* Validation
* Transformation (view representation to normalized and reverse)
* Removing of duplicated values
* Optimizing of ranges: detecting and removing overlapping ranges
* Connected values to Ranges (1,2,3,4,5 gets converted to 1-5)

## Types

The following types are build (but can be replaced with your own if needed).

> Each type is internalized.

* Birthday (with optional support for ages)
* Choice
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

* Doctrine2 ORM
* Doctrine2 DBAL (coming soon)
* Doctrine2 phpcr-odm (coming soon)
* CouchDB, MongoDB (coming soon)
* Apache Solr (coming soon)
* Elasticsearch (coming soon)

Requirements
------------

You need at least PHP 5.3.3, and preferable the Intl extension
for international support.

For framework integration you use the following.

* Symfony2 Bundle (coming soon)
* ZendFramework2 Plugin (coming soon)
* Silex Plugin (coming soon)

Installation
------------

Installation is very easy, all the details about installing can be found in.

[docs/Installing](docs/installing.rst)

Documentation
-------------

> **The current documentation is outdated, please be patient as it gets updated.**

The documentation is written in [reStructuredText][3] and can be built into standard HTML using [Sphinx][4].

To build the documentation are:

1. Install [Spinx][4]
2. Change to the `docs` directory on the command line
3. Run `make html`

This will build the documentation into the `docs/_build/html` directory.

Further information can be found in The Symfony2 [documentation format][5] article.

> The Sphinx extensions are already included and do not need to be downloaded separately.

License
========

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
