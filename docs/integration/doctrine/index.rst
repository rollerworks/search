Doctrine integration
====================

The core library of RollerworksSearch is agnostic to your storage/search index,
to help with searching your system a number of optional extensions are provided,
called integration libraries.

You need to install these integrations yourself, but other then that
there usage is straightforward.

.. note::

    Framework integrations already provide the required registration,
    you only need to install the package and maybe set some options.

    See also the framework integration details for this extension.

RollerworksSearch provides support for Doctrine DBAL and ORM using
separate extensions.

.. caution::

    Performing complex search operations in a relation database
    using Doctrine ORM may cause unresolvable performance problems.

    Due to the way ORM querying works not all conditions can be optimized
    for the best performance. Consider using :doc:`/integration/elasticsearch`
    instead.

    See also: http://ocramius.github.io/blog/doctrine-orm-optimization-hydration/

Support for document based storage is not possible due to technical limitations.
If you need this, consider using :doc:`/integration/elasticsearch`.

.. toctree::
    :maxdepth: 2

    dbal
    orm
    conversions
    troubleshooting
