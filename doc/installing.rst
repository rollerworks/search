Installing the Library
======================

Installing RollerworksSearch is trivial. Usually it's just a case of
uploading the extracted source files to your web server.

Installing with Composer
------------------------

`Composer`_ is a dependency management library for PHP, which you can use
to download the RollerworksSearch system.

Start by `downloading Composer`_ anywhere onto your local computer. If you
have curl installed, it's as easy as:

.. code-block:: bash

    curl -s https://getcomposer.org/installer | php

Installing RollerworksSearch with Composer is as easy as:

.. code-block:: bash

    $ php composer.phar require rollerworks/search

From the directory where your ``composer.json`` file is located.

Now, Composer will automatically download all required files, and install them
for you. After this you can start integrating RollerworksSearch with your application.

Optional packages
~~~~~~~~~~~~~~~~~

The ``rollerworks/search`` package itself does not provide any mechanise
for searching a storage engine (like Doctrine or ElasticSearch).

To search in a storage engine you need to install additional packages
or build your own SearchCondition processor.

To get you started we already provide a number of additional packages for searching;

Doctrine
^^^^^^^^

* `rollerworks/search-doctrine-orm`_ allows searching a relational SQL database using `Doctrine2 ORM`_.
* `rollerworks/search-doctrine-dbal`_ allows searching a relational SQL database using `Doctrine2 DBAL`_.

JmsMetadata
^^^^^^^^^^^

https://github.com/rollerworks/rollerworks-search-jms-metadata

Provides a :doc:`Metadata <metadata>` loader using the `jms/metadata`_ package.

.. caution::

    The ``jms/metadata`` package is licensed under the Apache2 licence
    which is incompatible with GNU GPLv2 and up.

.. _`Composer`: http://getcomposer.org/
.. _`downloading Composer`: http://getcomposer.org/download/

.. _`rollerworks/search-doctrine-orm`: https://github.com/rollerworks/rollerworks-search-doctrine-orm
.. _`rollerworks/search-doctrine-dbal`: https://github.com/rollerworks/rollerworks-search-doctrine-dbal
.. _`Doctrine2 ORM`: http://www.doctrine-project.org/projects/orm.html
.. _`Doctrine2 dbal`: http://www.doctrine-project.org/projects/dbal.html
.. _`jms/metadata`: https://github.com/schmittjoh/metadata
