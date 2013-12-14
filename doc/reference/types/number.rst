.. index::
   single: Fields; number

number Field Type
=================

A field specialized in number as input.

This type offers different options for the precision, rounding, and grouping that
you want to use for your number.

+-------------+----------------------------------------------------------------------------+
| Options     | - `rounding_mode`_                                                         |
|             | - `precision`_                                                             |
|             | - `grouping`_                                                              |
+-------------+----------------------------------------------------------------------------+
| Inherited   | - `invalid_message`_                                                       |
| options     | - `invalid_message_parameters`_                                            |
+-------------+----------------------------------------------------------------------------+
| Parent type | :doc:`field </reference/types/field>`                                      |
+-------------+----------------------------------------------------------------------------+
| Class       | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\NumberType` |
+-------------+----------------------------------------------------------------------------+

Field Options
-------------

.. include:: /reference/types/options/precision.rst.inc

rounding_mode
~~~~~~~~~~~~~

**type**: ``integer`` **default**: ``NumberToLocalizedStringTransformer::ROUND_HALFUP``

If a submitted number needs to be rounded (based on the ``precision``
option), you have several configurable options for that rounding. Each
option is a constant on the :class:`Rollerworks\\Component\\Search\\Extension\\Core\\DataTransformer\\NumberToLocalizedStringTransformer`:

* ``NumberToLocalizedStringTransformer::ROUND_DOWN`` Round towards zero.

* ``NumberToLocalizedStringTransformer::ROUND_FLOOR`` Round towards negative
  infinity.

* ``NumberToLocalizedStringTransformer::ROUND_UP`` Round away from zero.

* ``NumberToLocalizedStringTransformer::ROUND_CEILING`` Round towards
  positive infinity.

* ``NumberToLocalizedStringTransformer::ROUND_HALF_DOWN`` Round towards the
  "nearest neighbor". If both neighbors are equidistant, round down.

* ``NumberToLocalizedStringTransformer::ROUND_HALF_EVEN`` Round towards the
  "nearest neighbor". If both neighbors are equidistant, round towards the
  even neighbor.

* ``NumberToLocalizedStringTransformer::ROUND_HALF_UP`` Round towards the
  "nearest neighbor". If both neighbors are equidistant, round up.

.. include:: /reference/types/options/grouping.rst.inc

Inherited Options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
