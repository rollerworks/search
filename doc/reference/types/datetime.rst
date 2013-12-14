.. index::
   single: Fields; datetime

datetime Field Type
===================

A field to capture datetime input.

The provided input can be provided localized.
The underlying data is stored as a ``DateTime`` object.

+----------------------+------------------------------------------------------------------------------+
| Output Data Type     | ``DateTime``                                                                 |
+----------------------+------------------------------------------------------------------------------+
| Options              | - `with_seconds`_                                                            |
|                      | - `with_minutes`_                                                            |
|                      | - `model_timezone`_                                                          |
|                      | - `input_timezone`_                                                          |
+----------------------+------------------------------------------------------------------------------+
| Parent type          | :doc:`field </reference/types/field>`                                        |
+----------------------+------------------------------------------------------------------------------+
| Class                | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\DateTimeType` |
+----------------------+------------------------------------------------------------------------------+

Field Options
-------------

.. include:: /reference/types/options/date_format.rst.inc

.. include:: /reference/types/options/with_seconds.rst.inc

.. include:: /reference/types/options/with_minutes.rst.inc

.. include:: /reference/types/options/model_timezone.rst.inc

.. include:: /reference/types/options/input_timezone.rst.inc

Inherited options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
