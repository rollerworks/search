.. index::
   single: Fields; integer

integer Field Type
==================

Handles an input "number" field.

This field has different options on how to handle input values that aren't
integers. By default, all non-integer values (e.g. 6.78) will round down (e.g. 6).

+--------------------+-----------------------------------------------------------------------------+
| Options            | - `rounding_mode`_                                                          |
|                    | - `precision`_                                                              |
|                    | - `grouping`_                                                               |
+--------------------+-----------------------------------------------------------------------------+
| Inherited options  | - `invalid_message`_                                                        |
|                    | - `invalid_message_parameters`_                                             |
+--------------------+-----------------------------------------------------------------------------+
| Parent type        | :doc:`field </reference/types/field>`                                       |
+--------------------+-----------------------------------------------------------------------------+
| Class              | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\IntegerType` |
+--------------------+-----------------------------------------------------------------------------+

Field Options
-------------

.. include:: /reference/types/options/precision.rst.inc

rounding_mode
~~~~~~~~~~~~~

**type**: ``integer`` **default**: ``IntegerToLocalizedStringTransformer::ROUND_DOWN``

By default, if the user enters a non-integer number, it will be rounded
down. There are several other rounding methods, and each is a constant
on the :class:`Rollerworks\\Component\\Search\\Extension\\Core\\DataTransformer\\IntegerToLocalizedStringTransformer`:

* ``IntegerToLocalizedStringTransformer::ROUND_DOWN`` Round towards zero.

* ``IntegerToLocalizedStringTransformer::ROUND_FLOOR`` Round towards negative
  infinity.

* ``IntegerToLocalizedStringTransformer::ROUND_UP`` Round away from zero.

* ``IntegerToLocalizedStringTransformer::ROUND_CEILING`` Round towards
  positive infinity.

* ``IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN`` Round towards the
  "nearest neighbor". If both neighbors are equidistant, round down.

* ``IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN`` Round towards the
  "nearest neighbor". If both neighbors are equidistant, round towards the
  even neighbor.

* ``IntegerToLocalizedStringTransformer::ROUND_HALF_UP`` Round towards the
  "nearest neighbor". If both neighbors are equidistant, round up.

.. include:: /reference/types/options/grouping.rst.inc

Inherited options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
