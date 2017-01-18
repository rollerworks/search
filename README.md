RollerworksSearchBundle
=======================

Main purpose of this bundle is to integrate [RollerworksSearch](https://github.com/rollerworks/RollerworksSearch)
with any Symfony based application.

    RollerworksSearch provides a powerful searching system.

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/3a327c61-805f-4b58-b5bf-afd4a6e4ab7f/mini.png)](https://insight.sensiolabs.com/projects/3a327c61-805f-4b58-b5bf-afd4a6e4ab7f)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rollerworks/RollerworksSearchBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rollerworks/RollerworksSearchBundle/?branch=master)

Requirements
------------

You need at least the Symfony 2.3 or 3.0 (Symfony FrameworkBundle)
and the Composer package manager for installing dependencies.

For searching in storage engines you need install the additional search extensions.
See the documentation in the main package for more information.

Documentation
-------------

* [Installation](doc/installing.md)
* [Configuration reference](doc/configuration_reference.md)
* [Basic Usage](doc/basic_usage.md)
* [Extensions](doc/extensions.md)

Storage bundles
---------------

The following bundles are provided for enabling the described storage
engines with the RollerworksSearchBundle bundle.
*These bundles are extensions, you still need the RollerworksSearchBundle!.*

* [Doctrine DBAL](https://github.com/rollerworks/rollerworks-search-doctrine-dbal-bundle)
* [Doctrine ORM](https://github.com/rollerworks/rollerworks-search-doctrine-orm-bundle)

License
-------

The source of this package is subject to the MIT license that is bundled
with this source code in the file [LICENSE](LICENSE).

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
