UPGRADE
=======

## Upgrade FROM v1.0.0-beta2 to v1.0.0-beta3

The Doctrine ORM configuring and related services are moved to a new package
at https://github.com/rollerworks/rollerworks-search-doctrine-orm-bundle.

If you are using Doctrine ORM then install the new package and update your
application configuration as follow.

Before:

```yaml
# app/config/config.yml
rollerworks_search:
    doctrine:
        orm:
            cache_driver: rollerworks_cache.driver.session_driver
```

After:

```yaml
# app/config/config.yml
rollerworks_search_doctrine_orm: 
    cache_driver: rollerworks_search.doctrine_orm.cache.array_driver
```

The original service names (except `rollerworks_cache.driver.session_driver`)
are left unchanged.
