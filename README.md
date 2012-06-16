RollerworksRecordFilterBundle
=============================

[![Build Status](https://secure.travis-ci.org/rollerscapes/RollerworksRecordFilterBundle.png?branch=master)](http://travis-ci.org/rollerscapes/RollerworksRecordFilterBundle)

This bundle provides the RollerworksRecordFilterBundle, record filter-based searching.

*** This bundle is considered experimental. Documentation is missing. ***

## Installation

### Step 1: Using Composer (recommended)

To install RollerworksRecordFilterBundle with Composer just add the following to your
`composer.json` file:

```js
// composer.json
{
    // ...
    require: {
        // ...
        "rollerworks/recordfilter-bundle": "master-dev"
    }
}
```

**NOTE**: Please replace `master-dev` in the snippet above with the latest stable
branch, for example ``2.0.*``.

Then, you can install the new dependencies by running Composer's ``update``
command from the directory where your ``composer.json`` file is located:

```bash
$ php composer.phar update
```

Now, Composer will automatically download all required files, and install them
for you. All that is left to do is to update your ``AppKernel.php`` file, and
register the new bundle:

```php
<?php

// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Rollerworks\Bundle\RecordFilterBundleRollerworksRecordFilterBundle(),
    // ...
);
```

### Step 1 (alternative): Using ``deps`` file (Symfony 2.0.x)

First, checkout a copy of the code. Just add the following to the ``deps``
file of your Symfony Standard Distribution:

```ini
[RollerworksRecordFilterBundle]
    git=https://github.com/rollerscapes/RollerworksRecordFilterBundle.git
    target=/bundles/Rollerworks/Bundle/RecordFilterBundle
```

**NOTE**: You can add `version` tag in the snippet above with the latest stable
branch, for example ``version=origin/2.0``.

Then register the bundle with your kernel:

```php
<?php

// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Rollerworks\Bundle\RecordFilterBundle\RollerworksRecordFilterBundle(),
    // ...
);
```

Make sure that you also register the namespace with the autoloader:

```php
<?php

// app/autoload.php
$loader->registerNamespaces(array(
    // ...
    'Rollerworks'              => __DIR__.'/../vendor/bundles',
    // ...
));
```

Now use the ``vendors`` script to clone the newly added repositories
into your project:

```bash
$ php bin/vendors install
```

### Step 1 (alternative): Using submodules (Symfony 2.0.x)

If you're managing your vendor libraries with submodules, first create the
`vendor/bundles/Rollerworks/Bundle` directory:

``` bash
$ mkdir -pv vendor/bundles/Rollerworks/Bundle
```

Next, add the necessary submodule:

``` bash
$ git submodule add git://github.com/rollerscapes/RollerworksRecordFilterBundle.git vendor/bundles/Rollerworks/Bundle/RecordFilterBundle
```

### Step2: Configure the autoloader

Add the following entry to your autoloader:

``` php
<?php
// app/autoload.php

$loader->registerNamespaces(array(
    // ...
    'Rollerworks'              => __DIR__.'/../vendor/bundles',
    // ...
));
```

### Step3: Enable the bundle

Finally, enable the bundle in the kernel:

``` php
<?php

// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Rollerworks\Bundle\RecordFilterBundle\RollerworksRecordFilterBundle(),
    // ...
);
```
### Step4: Configure the bundle

Finally, add the following to your config file:

``` yaml
# app/config/config.yml

rollerworks_recordfilter:
    # Cache location of the class metadata (must be writable)
    metadata_cache: %kernel.cache_dir%/recordfilter_metadata
```
