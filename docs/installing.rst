Installing the Library
======================

Installing RollerworksSearch is trivial. By using Composer to install
the dependencies you don't have to worry about compatibility or autoloading.

`Composer`_ is a dependency management library for PHP, which you can use
to download the RollerworksSearch library, and at your choice any extensions.

Start by `downloading Composer`_ anywhere onto your local computer.
And install RollerworksSearch with Composer by running the following:

.. code-block:: bash

    $ php composer.phar require "rollerworks/search:^2.0"

From the directory where your ``composer.json`` file is located.

Now, Composer will automatically download all the required files, and install them
for you. After this you can start integrating RollerworksSearch with your application.

.. note::

    All code examples assume you are using the class auto-loader provided by Composer.

    .. code-block:: php

        require 'vendor/autoload.php';

        // ...

    When using a Framework integration this is already the case, so don't worry
    about this step.

.. caution::

    RollerworksSearch v2.0 is not stable yet! Use with caution. However
    RollerworksSearch v1.0 is no longer maintained.

Extensions
----------

The ``rollerworks/search`` core library itself does not provide any mechanise
for searching in a storage engine (like Doctrine or Elasticsearch). Instead they
are provided as separate extensions you can install.

Framework integration libraries (provided by Rollerworks) are designed to provide
a clear-cut and ready to use solution. Whenever you install an addition extension,
the integration automatically enables the support for it.

.. note::

    Only extensions provided by Rollerworks are fully integrated, for extensions
    provided by third party developers you may need to enable these manually.

Framework integration
---------------------

RollerworksSearch provides full integration for:

* The :doc:`Symfony Framework <integration/symfony_bundle>`,
* The :doc:`Api Platform <integration/api_platform>`,
* :doc:`Doctrine DBAL and ORM <integration/doctrine/index>`.
* :doc:`ElasticSearch <integration/elasticsearch/index>`.

Further reading
---------------

* :doc:`Using the SearchProcessor <processing_searches>`
* :doc:`Composing SearchConditions <composing_search_conditions>`

.. _`Composer`: http://getcomposer.org/
.. _`downloading Composer`: https://getcomposer.org/download/
