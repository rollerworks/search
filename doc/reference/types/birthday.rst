.. index::
   single: Fields; birthday

birthday Field Type
===================

A :doc:`date </reference/types/date>` field that specializes in handling
birthday or age data. Can accept both birthdate or age.

This type is essentially the same as the :doc:`date </reference/types/date>`
type, but with a special option for handling age in years.

+----------------------+------------------------------------------------------------------------------+
| Output Data Type     | can be ``DateTime`` or ``integer``                                           |
+----------------------+------------------------------------------------------------------------------+
| Overridden Options   | - `constraints`_                                                             |
+----------------------+------------------------------------------------------------------------------+
| Inherited Options    | - `format`_                                                                  |
|                      | - `model_timezone`_                                                          |
|                      | - `input_timezone`_                                                          |
+----------------------+------------------------------------------------------------------------------+
| Parent type          | :doc:`date </reference/types/date>`                                          |
+----------------------+------------------------------------------------------------------------------+
| Class                | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\BirthdayType` |
+----------------------+------------------------------------------------------------------------------+

Overridden Options
------------------

.. include:: /reference/types/options/constraints.rst.inc

Inherited options
-----------------

These options inherit from the :doc:`date </reference/types/date>` type:

.. include:: /reference/types/options/date_format.rst.inc

.. include:: /reference/types/options/model_timezone.rst.inc

.. include:: /reference/types/options/input_timezone.rst.inc

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
