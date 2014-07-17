UPGRADE
=======

## UPGRADE FROM 1.0.0-beta2 to 1.0.0-beta3

RollerworksSearch is split to multiple smaller packages,
each providing an extension for the RollerworksSearch 'core' package.

* rollerworks-search-doctrine-dbal: Doctrine DBAL Searching support
* rollerworks-search-doctrine-orm:  Doctrine ORM Searching support

* rollerworks-search-symfony-validator: Symfony validator extension
* rollerworks-search-symfony-di: Symfony DependencyInjection extension (lazy loading)
* rollerworks-search-jms-metadata: JMS Metadata adapter extension

If you install any of the mentioned packages using composer,
the RollerworksSearch package will be automatic installed as well.

## UPGRADE FROM RollerworksRecordFilterBundle to RollerworksSearch

This project was formally called the RollerworksRecordFilterBundle.

If you like to switch to this new project please bare in mind
this library is completely rewritten, you should read the new documentation
to get started with the upgrade.

**Note:** The RollerworksRecordFilterBundle is discontinued, and will only receive
minor and security fixes until 2015. You are strongly advised to upgrade as soon as
possible.
