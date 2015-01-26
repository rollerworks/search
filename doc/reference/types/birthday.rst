.. index::
   single: Fields; birthday

birthday Field Type
===================

A :doc:`date </reference/types/date>` field that specializes in handling
birthday or age data. Can accept both birthdate or age.

This type is essentially the same as the :doc:`date </reference/types/date>`
type, but with a special option for handling an age in years.

+----------------------+------------------------------------------------------------------------------+
| Output Data Type     | can be ``DateTime`` or ``integer``                                           |
+----------------------+------------------------------------------------------------------------------+
| Options              | - `allow_future_date`_                                                       |
+----------------------+------------------------------------------------------------------------------+
| Inherited Options    | - `format`_                                                                  |
|                      | - `allow_age`_                                                               |
|                      | - `allow_future_date`_                                                        |
+----------------------+------------------------------------------------------------------------------+
| Parent type          | :doc:`date </reference/types/date>`                                          |
+----------------------+------------------------------------------------------------------------------+
| Class                | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\BirthdayType` |
+----------------------+------------------------------------------------------------------------------+

Field Options
-------------

allow_age
~~~~~~~~~

**type**: ``bool`` **default**: ``true``

Allow age (in years) as accepted format.

.. caution::

   This will be produce a mixed result for the field values, as some maybe integer
   while other are ``\DateTime`` objects.

allow_future_date
~~~~~~~~~~~~~~~~~

**type**: ``bool`` **default**: ``true``

Allow dates that are in the future.

Inherited options
-----------------

These options inherit from the :doc:`date </reference/types/date>` type:

.. include:: /reference/types/options/date_format.rst.inc

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
