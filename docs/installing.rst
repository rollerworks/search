Installing the Library
======================

Installing Rollerworks Search is trivial. Usually it's just a case of uploading the
extracted source files to your web server.

Installing with Composer
------------------------

`Composer`_ is a dependency management library for PHP, which you can use
to download the Rollerworks Search component.

Start by `downloading Composer`_ anywhere onto your local computer. If you
have curl installed, it's as easy as:

.. code-block:: bash

    curl -s https://getcomposer.org/installer | php

Installing Rollerworks Search with Composer is as easy as:

.. code-block:: bash

    $ php composer.phar require rollerworks/search

Or if you want to Rollerworks Search manually,
add the following to your ``composer.json`` file:

.. code-block:: js

    // composer.json
    {
        // ...
        "require": {
            // ...
            "rollerworks/search": "dev-master"
        }
    }

.. note::

    Please replace ``dev-master`` in the snippet above with the latest stable
    version, for example ``1.0.*`` or ``~1.0`` for the latest version.

    Rollerworks Search follows Semantic Versioning 2.0.0.
    Meaning that all changes done in minor versions are backward compatible.

Then, you can install the new dependencies by running Composer's ``update``
command from the directory where your ``composer.json`` file is located:

.. code-block:: bash

    php composer.phar update rollerworks/search

Now, Composer will automatically download all required files, and install them
for you. After this you can start integrating the library with your application.

Optional dependencies
~~~~~~~~~~~~~~~~~~~~~

The following dependencies are optional, to install them you must add them to
your ``composer.json`` file.

Or using the command above replacing ``rollerworks/search`` with the package-name
displayed in the "require" section.

Metadata
^^^^^^^^

    The ``jms/metadata`` package not be installed by default because the package is licensed
    under the Apache2 licence which is incompatible with GNU GPLv2.

    Rollerworks Search is licenced under the MIT license, but we
    cannot guarantee that any other packages are installed which require this
    compatibility.

To install the ``jms/metadata`` with Composer add the following to your
``composer.json`` file:

.. code-block:: js

    // composer.json
    {
        // ...
        "require": {
            // ...
            "jms/metadata": ">=1.1.1",
            "doctrine/annotation": "~2.3,>=2.3.0"
        }
    }

.. _`Composer`: http://getcomposer.org/
.. _`downloading Composer`: http://getcomposer.org/download/
