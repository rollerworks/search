README
======

[![Build Status](https://secure.travis-ci.org/rollerworks/RollerworksRecordFilterBundle.png?branch=master)](http://travis-ci.org/rollerworks/RollerworksRecordFilterBundle)

What is the RecordFilterBundle?
-------------------------------

The RecordFilterBundle is a Symfony 2 Bundle for filter-based record searching.

Filter-based in that it uses a filtering system to search.
You search by conditions, not terms.

This bundle was designed to be used for any kind of storage, input and local.

Out of the box it (currently) only supports Doctrine ORM for searching in.

Requirements
------------

You need at least Symfony 2.1 and the Composer package manager.

    A Component that is used with this bundle currently does not support the old vendor-script installation.

Installation
------------

Installation is very easy, all the details about installing can be found in.

[docs/Installing](docs/installing.rst)

Documentation
-------------

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
