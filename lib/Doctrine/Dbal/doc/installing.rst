Installing the Library
======================

Installing this extension package is very simple. Assuming you have already
installed `Composer`_ and set-up your dependencies. Installing this extension
with Composer is as easy as:

.. code-block:: bash

    $ php composer.phar require rollerworks/search-doctrine-dbal

From the directory where your ``composer.json`` file is located.

Now, Composer will automatically download all required files, and install them
for you. After this you can enable the the Doctrine DBAL extension for
RollerworksSearch in your application.

Enabling the extension
----------------------

First you must enable the :class:`Rollerworks\\Component\\Search\\Extension\\Doctrine\\Dbal\\DoctrineDbalExtension`
for the ``SearchFactoryBuilder``. This search extension adds extra options
for registering :doc:`conversions` and ensures core types work properly.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;
    use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
    use Rollerworks\Component\Search\Extension\Core\CoreExtension;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new DoctrineDbalExtension())

        // ...
        ->getSearchFactory();

After this you can use RollerworksSearch with Doctrine DBAL support enabled.

.. _`Composer`: http://getcomposer.org/
.. _`downloading Composer`: http://getcomposer.org/download/

