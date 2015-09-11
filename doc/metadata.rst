Metadata
========

Class metadata is used by the ``FieldSetBuilder`` to populate a ``FieldSet`` instance
field configuration with the metadata of a Model class.

To actually use the metadata component you first need a compatible metadata loader.

RollerworksSearch doesn't come bundled with a metadata loader, but
you can use the `RollerworksSearch Metadata extension`_ as compatible metadata loader.

.. note::

    The `RollerworksSearch JMS Metadata extension`_ is deprecated and will no longer
    be supported in future versions.

    You only need to update the PHP, all metadata already defined is still compatible.
    But the ``required`` flag is no longer available and needs to be removed.

.. _`RollerworksSearch Metadata extension`: https://github.com/rollerworks/rollerworks-search-metadata
.. _`RollerworksSearch JMS Metadata extension`: https://github.com/rollerworks/rollerworks-search-jms-metadata
