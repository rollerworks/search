Installing
==========

Only Composer is officially supported for installing. If you use any other method,
such as the old vendor-strict or Git submodules, the Rollerworks Locale component
used by the build-in filter-types component will fail to work.

1. Using Composer (recommended)
-------------------------------

`Composer`_ is a dependency management library for PHP, which you can use
to download the RollerworksRecordFilterBundle.

Start by `downloading Composer`_ anywhere onto your local computer. If you
have curl installed, it's as easy as:

.. code-block:: bash

    curl -s https://getcomposer.org/installer | php

To install RollerworksRecordFilterBundle with Composer just add the following to your
``composer.json`` file:

.. code-block:: js

    // composer.json
    {
        // ...
        "require": {
            // ...
            "rollerworks/recordfilter-bundle": "dev-master"
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

The scripts part is needed for updating the localized validation and matching.

.. note::

    Please replace ``dev-master`` in the snippet above with the latest stable
    branch, for example ``1.0.*``.

Then, you can install the new dependencies by running Composer's ``update``
command from the directory where your ``composer.json`` file is located:

.. code-block:: bash

    php composer.phar update rollerworks/recordfilter-bundle

Now, Composer will automatically download all required files, and install them
for you. All that is left to do is to update your ``AppKernel.php`` file, and
register the new bundle:

.. code-block:: php

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new Rollerworks\Bundle\RecordFilterBundle\RollerworksRecordFilterBundle(),
        // ...
    );

.. _`Composer`: http://getcomposer.org/
.. _`downloading Composer`: http://getcomposer.org/download/
