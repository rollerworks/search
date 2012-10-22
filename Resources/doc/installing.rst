Installing
==========

1. Using Composer (recommended)
-------------------------------

To install RollerworksRecordFilterBundle with Composer just add the following to your
`composer.json` file:

.. code-block:: js

    // composer.json
    {
        // ...
        require: {
            // ...
            "rollerworks/recordfilter-bundle": "master-dev"
        }
    }

    "scripts": {
        "post-install-cmd": [
            // ...
            "Rollerworks\\Component\\Locale\\Composer\\ScriptHandler::updateLocaleData"
        ],
        "post-update-cmd": [
            // ...
            "Rollerworks\\Component\\Locale\\Composer\\ScriptHandler::updateLocaleData"
        ]
    }

The scripts part is needed for updating the localized validation and matching of filter types.

.. note::

    Please replace `master-dev` in the snippet above with the latest stable
    branch, for example ``1.0.*``.

    1.0.x is for Symfony 2.0
    1.1.x is for Symfony 2.1 and up

Then, you can install the new dependencies by running Composer's ``update``
command from the directory where your ``composer.json`` file is located:

.. code-block:: bash

    php composer.phar update

Now, Composer will automatically download all required files, and install them
for you. All that is left to do is to update your ``AppKernel.php`` file, and
register the new bundle:

.. code-block:: php

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new Rollerworks\Bundle\RecordFilterBundleRollerworksRecordFilterBundle(),
        // ...
    );

2. Using the ``deps`` file (Symfony 2.0.x)
------------------------------------------

Unfortunately this method is current not fully supported by the bundle.
You should really consider switching to composer.

.. code-block:: ini

    [RollerworksRecordFilterBundle]
        git=https://github.com/rollerworks/RollerworksRecordFilterBundle.git
        target=/bundles/Rollerworks/Bundle/RecordFilterBundle

    ; Dependencies:
    ;--------------
    [RollerworksLocaleComponent]
        git=https://github.com/rollerworks/Locale.git
        target=/Rollerworks/Component/Locale

    [metadata]
        git=https://github.com/schmittjoh/metadata.git
        version=1.1.1 ; <- make sure to get 1.1.1, not 1.0

    [doctrine]
        git=http://github.com/doctrine/doctrine2.git
        ; Make sure to get 2.2.4, any version lower then this is not tested and will properly fail
        version=2.2.4

Then register the bundle with your kernel:

.. code-block:: php

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new Rollerworks\Bundle\RecordFilterBundle\RollerworksRecordFilterBundle(),
        // ...
    );

Make sure that you also register the namespaces with the autoloader:

.. code-block:: php

    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        'Rollerworks'         => array(__DIR__.'/../vendor/bundles', __DIR__.'/../vendor'),
        // ...
    ));

Now use the ``vendors`` script to clone the newly added repositories
into your project:

.. code-block:: bash

    php bin/vendors install
