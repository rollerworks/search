README
======

[![Build Status](https://secure.travis-ci.org/rollerworks/RollerworksSearch.png?branch=master)](http://travis-ci.org/rollerworks/RollerworksSearch)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31/mini.png)](https://insight.sensiolabs.com/projects/92caf31d-dae6-49dd-9526-440d859daa31)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/rollerworks/RollerworksSearch/badges/quality-score.png?s=5eebfd1ff3695ab59d59406702978a0ddf29df21)](https://scrutinizer-ci.com/g/rollerworks/RollerworksSearch/)


**Note:** This project has moved to https://github.com/rollerworks/RollerworksSearch
This current version will maintained (bug fixes only) till december 2015 after this support is discontinued.

What is Rollerworks Search?
---------------------------

Rollerworks Search is an advanced search-building framework.

Providing powerful system for building your own site search-engine.
From input to formatting and validating, and finally applying conditions on the
storage engine.

This system was designed to be as expendable as possible.
Everything, and absolute everything can be replaced with your implementation.

Features
--------

The searching condition is build of fields and groups.
Each field can have any-type of value including ranges, comparisons
and matchers (starts/ends with contains).

Groups can be nested at any level of depth.

## Input

Input processing is provided for the following formats.

* Array
* JSON
* XML
* FilterQuery (an easy to learn and use condition based input format).

> Each provided input format also provides an related exporter.

## Formatter

* Validation
* Transformation (localized representation to normalized and reverse)
* Removing of duplicated values
* Optimizing of ranges: detecting and removing overlapping ranges
* Connected values to ranges (1,2,3,4,5 gets converted to 1-5)

## Types

The following types are build-in (but can be replaced with your own if needed).

> Each type is localized.

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

For framework integration you use the following;

* Symfony2 Bundle (coming soon)
* ZendFramework2 Plugin (coming soon)
* Silex Plugin (coming soon)

Installation
------------

The installation is very easy, all the details about installing can be found in.

[doc/installing](doc/installing.rst)

Documentation
-------------

The documentation is written in [reStructuredText][3] and can be built into standard HTML using [Sphinx][4].

To build the documentation are:

1. Install [Spinx][4]
2. Change to the `docs` directory on the command line
3. Run `make html`

This will build the documentation into the `docs/_build/html` directory.

Further information can be found in The Symfony2 [documentation format][5] article.

> The Sphinx extensions are already included and don't need to be downloaded separately.

Versioning
----------

For transparency and insight into our release cycle, and for striving to maintain backward compatibility,
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
