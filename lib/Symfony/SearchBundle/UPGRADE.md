UPGRADE
=======

## Upgrade FROM v1.0.0 to v2.0.0-alpha1

* Support for Symfony 2 was dropped, at least Symfony 3.3 is required now.

* The Metadata system has been removed in RollerworksSearch v2.0,
  this bundle no longer allows registering FieldSets as service configurations.
  
  Instead the FieldSet configuration needs to be stored in a FieldSetConfigurator
  which (optionally) can be registered as a service for dependency loading.
  
  **Tip:** Configurators service definitions can be marked private.
  
* The SearchProcessor has been moved to it's own component `rollerworks/search-processor`
  and now requires PSR-7 to be installed.
  
  To use the processor install the following dependencies:
  
  * `rollerworks/search-processor:^1.0` (may require `"minimum-stability": "dev"` in Composer)
  * `symfony/psr-http-message-bridge:^1.0.0`
  * `zendframework/zend-diactoros:^1.3.9`
  
  Zend Diactoros is only used internally and does not require the SensioFrameworkExtraBundle
  to be installed or enabled. The RollerworksSearchBundle provides a compatibility adapter
  for the Symfony HttpFoundation component.
  
  **Note:** The processor is automatically enabled when the `rollerworks/search-processor`
  package is installed, but will refuse to work unless all dependencies are met.
  
  To disable the processor set the `rollerworks_search.processor` option to `false`.
  
* `SearchExtension`s are no longer registered with the `rollerworks_search.type_registry`
  service, it's advised to register only types and type-extensions by tagging them.
  
  Types and there extensions are loaded lazily, and no longer have to be marked public.
  
* The SearchForm type has been removed, using the SearchProcessor with the Symfony Form component
  is no longer supported.
  
* Tagged Input processors and Condition exporters now require a `format` attribute to be
  set with the tag.
  
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
