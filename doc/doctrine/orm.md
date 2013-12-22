Using Doctrine ORM
==================

To use Doctrine ORM please make sure the `doctrine/doctrine-bundle`
is installed and enabled.

Then enable the Doctrine Orm for the search bundle using the following.

``` yaml
# app/config/config.yml
rollerworks_search:
    doctrine:
        orm: ~
```

Note: you properly want to configure a 'real' cache which stays persistent between page loads.

For this you can install the [RollerworksCacheBundle](https://github.com/rollerworks/RollerworksCacheBundle)
which provides a cache driver based on a PHP session.

``` yaml
# app/config/config.yml
rollerworks_search:
    doctrine:
        orm:
            cache_driver: rollerworks_cache.driver.session_driver
```

Now you can use the `Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory` using
the `rollerworks_search.doctrine_orm.factory` service.
