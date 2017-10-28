Installing the Library
======================

Installing this extension package is very simple. Assuming you have already
installed `Composer`_ and set-up your dependencies. Installing this extension
with Composer is as easy as:

.. code-block:: bash

    $ php composer.phar require rollerworks/search-doctrine-orm

From the directory where your ``composer.json`` file is located.

Now, Composer will automatically download all required files, and install them
for you. After this you can enable the the Doctrine DBAL extension for
RollerworksSearch in your application.

Enabling the extension
----------------------

First you must enable the :class:`Rollerworks\\Component\\Search\\Extension\\Doctrine\\Dbal\\DoctrineDbalExtension`
for the ``SearchFactoryBuilder``. This search extension adds extra options
for registering :doc:`conversions` and ensures core types work properly.

And if you want to use the Doctrine ORM Query Language (DQL) with RollerworksSearch
you must also enable the :class:`Rollerworks\\Component\\Search\\Extension\\Doctrine\\Orm\\DoctrineOrmExtension`
to register the required custom DQL-functions.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;
    use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
    use Rollerworks\Component\Search\Extension\Doctrine\Orm\DoctrineOrmExtension
    use Rollerworks\Component\Search\Extension\Core\CoreExtension;

    // Doctrine\Common\Persistence\ManagerRegistry object
    // providing access to all available EntityManagers.
    $emRegistry = ...;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new DoctrineDbalExtension())
        ->addExtension(new DoctrineOrmExtension($emRegistry))

        // ...
        ->getSearchFactory();

The ``DoctrineOrmExtension`` will automatically register the customer DQL-functions
at the "Default" EntityManager (provided by ``$emRegistry``) if you use
a different entity-manager name or want to use multiple entity-managers,
you can pass these (by name) as the second parameter:

.. code-block:: php

   new DoctrineOrmExtension($emRegistry, ['Default', 'MyCustomEm'])

After this you can use RollerworksSearch with Doctrine ORM support enabled.

.. _`Composer`: http://getcomposer.org/
.. _`downloading Composer`: http://getcomposer.org/download/

