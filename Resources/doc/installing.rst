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
