Value and Field Conversions
===========================

Conversions (or converts) for Doctrine ORM are similar to the DataTransformers
used for transforming user-input to a normalized data format. Except that
the transformation happens in a single direction, and uses normalized data.

Conversions are handled by the `RollerworksSearch Doctrine DBAL extension`_,
all the details on conversions and making your own are described in
the referenced package.

.. note::

    Custom DQL-functions with the ``Column`` parameter receive the resolved
    entity-alias and column-name that the Query parser has generated. Because
    these functions only receive the column name of the current entity field
    it's impossible to know the table and column aliases of other fields.

    Trying to guess the other aliases is properly a bad idea. If you however
    need this feature, you can vote for implementing this at:
    `Conversions at DQL level for field and value <https://github.com/rollerworks/rollerworks-search-doctrine-orm/issues/6>`_

.. _`RollerworksSearch Doctrine DBAL extension`: http://rollerworks-search-doctrine-dbal.readthedocs.org/en/latest/conversions.html
