.. index::
   single: Fields; date

date Field Type
===============

A field to capture date input.

The provided input can be provided localized.
The underlying data is stored as a ``DateTime`` object.

+----------------------+---------------------------------------------------------------------------+
| Output Data Type     | ``DateTime``                                                              |
+----------------------+---------------------------------------------------------------------------+
| Options              | - `format`_                                                               |
|                      | - `model_timezone`                                                        |
|                      | - `input_timezone`_                                                       |
+----------------------+---------------------------------------------------------------------------+
| Inherited options    | - `invalid_message`_                                                      |
|                      | - `invalid_message_parameters`_                                           |
+----------------------+---------------------------------------------------------------------------+
| Parent type          | :doc:`field </reference/types/field>`                                     |
+----------------------+---------------------------------------------------------------------------+
| Class                | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\DateType`  |
+----------------------+---------------------------------------------------------------------------+

Field Options
-------------

.. _reference-fields-type-date-format:

.. include:: /reference/types/options/date_format.rst.inc

.. include:: /reference/types/options/model_timezone.rst.inc

.. include:: /reference/types/options/input_timezone.rst.inc

Inherited options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
