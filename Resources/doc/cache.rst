Caching
=======

The system processes a lot of data for formatting and filtering.
The system cache can be utilized to improve the overall performance of page loads by caching the results.

By default an Array cache is used. It is only valid for the current page load.
Alternative cache options can offer additional features.

.. tip::

    You can use a different cache service per component.

Caching is configured by specifying a service for handling.

.. note::

    Both the ``CacheFormatter`` and Doctrine Component use the Doctrine Cache.
    The cache driver must implement the ``Doctrine\Common\Cache\Cache`` interface.

    If you don't want to use the Doctrine cache, you can either extend the ``CacheFormatter``
    class or build your own by implementing the ``CacheFormatterInterface``.

    The Doctrine Component of the bundle does does not enforce an interface.

Add the following to your config file.

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml
        rollerworks_record_filter:
            # ...
            formatter:
                cache:
                    # Driver is a service name
                    driver: rollerworks_record_filter.cache_array_driver

                    # lifetime in seconds, 0 means no expires
                    lifetime: 0

    .. code-block:: php

        // app/config/config.php
        $container->loadFromExtension('rollerworks_record_filter', array(
            /* ... */
            'formatter' => array(
                'cache' => array(
                    'driver' => 'rollerworks_record_filter.cache_array_driver',

                    # lifetime in seconds, 0 means no expires
                    'lifetime' => 0,
                ),
            ),
        ));

.. note::

    The cache should be easy invalidated. Use either a cache that is
    short lived or easily removable.

Session Cache
-------------

To use a PHP session for caching the results, you can install
the `rollerworks/cache-bundle <https://github.com/rollerworks/RollerworksCacheBundle>`_.

Just add the following to your ``composer.json`` file:

.. code-block:: js

    // composer.json
    {
        // ...
        "require": {
            // ...
            "rollerworks/cache-bundle": "master-dev"
        }
    }

And add the following to your config file.

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml
        rollerworks_cache:
            session: ~

        rollerworks_record_filter:
            # ...
            formatter:
                cache:
                    driver: rollerworks_cache.driver.session_driver
                    lifetime: 60

            doctrine:
                orm:
                    cache:
                        driver: rollerworks_cache.driver.session_driver
                        lifetime: 60

    .. code-block:: php

        // app/config/config.php
        $container->loadFromExtension('rollerworks_cache', array('session' => array()));

        $container->loadFromExtension('rollerworks_record_filter', array(
            /* ... */
            'formatter' => array(
                'cache' => array(
                    'driver' => 'rollerworks_cache.driver.session_driver',
                    'lifetime' => 60,
                ),
            ),

            'doctrine' => array(
                'orm' => array(
                    'cache' => array(
                        'driver' => 'rollerworks_cache.driver.session_driver',
                        'lifetime' => 60,
                    ),
                ),
            ),
        ));
