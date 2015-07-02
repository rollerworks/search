Installation
============

1. Using Composer (recommended)
-------------------------------

[Composer][1] is a dependency management library for PHP, which you can use
to download the RollerworksSearchBundle.

Start by [downloading Composer][2] anywhere onto your local computer. If you
have curl installed, it's as easy as:

```bash
curl -s https://getcomposer.org/installer | php
```

To install RollerworksSearchBundle with Composer just add the following to your
``composer.json`` file:

```js
    // composer.json
    {
        // ...
        "require": {
            // ...
            "rollerworks/search-bundle": "~1.0"
        }
    }
```

    For installing additional libraries (like the `jms/metadata` package)
    please consult the documentation of RollerworksSearch.

Then, you can install the new dependencies by running Composer's `update`
command from the directory where your ``composer.json`` file is located:

```bash
php composer.phar update rollerworks/search-bundle
```

Now, Composer will automatically download all required files, and install them
for you. All that is left to do is to update your ``AppKernel.php`` file, and
register the new bundle:

```php
// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Rollerworks\Bundle\SearchBundle\RollerworksSearchBundle(),
);
```

[1]: http://getcomposer.org/
[2]: http://getcomposer.org/download/
