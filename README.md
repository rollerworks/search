[RollerworksRecordFilterBundle](http://projects.rollerscapes.net/RollerFramework/)
==================================================

This bundle provides the RollerworksRecordFilterBundle, 

## Installation

Installation depends on how your project is setup:

### Step 1: Installation using the `bin/vendors.php` method

If you're using the `bin/vendors.php` method to manage your vendor libraries,
add the following entry to the `deps` in the root of your project file:

```
[RollerworksRecordFilterBundle]
    git=https://github.com/Rollerscapes/RollerworksRecordFilterBundle.git
    target=/vendor/bundles/Rollerworks/RecordFilterBundle
```

Next, update your vendors by running:

```bash
$ ./bin/vendors
```

Great! Now skip down to *Step 2*.

### Step 1 (alternative): Installation with sub-modules

If you're managing your vendor libraries with sub-modules, first create the
`vendor/bundles/Rollerworks/RecordFilterBundle` directory:

```bash
$ mkdir -pv vendor/bundles/Rollerworks/RecordFilterBundle
```

Next, add the necessary sub-module:

```bash
$ git submodule add https://github.com/Rollerscapes/RollerworksRecordFilterBundle.git vendor/bundles/Rollerworks/RecordFilterBundle
```

### Step2: Configure the autoloader

Add the following entry to your autoloader:

```php
<?php
// app/autoload.php

$loader->registerNamespaces(array(
    // ...
    'Rollerworks' => __DIR__.'/../vendor/bundles',
));
```

### Step3: Enable the bundle

Finally, enable the bundle in the kernel:

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Rollerworks\RecordFilterBundle\RollerworksRecordFilterBundle(),
    );
}
```

### Step4: Configure the bundle

__Full documentation is currently missing sorry.__

Finally, add the following to your config file:

``` yaml
# app/config/config.yml

rollerworks_recordfilter:
    #filters_namespace: RecordFilter
    #filters_directory: %kernel.cache_dir%/record_filters

    # Following contains the configuration for the factory classes (all default to false).
    # Can be set to false and configured per class
    # The configuration is read from the Entity's annotation (see Resources/Docs/Factory for more information).

    #formatter_factory.auto_generate: false
    #sqlstruct_factory.auto_generate: false
    #querybuilder_factory.auto_generate: false
```