.. index::
   single: Fields; money

money Field Type
================

A field specialized in money data.

This field type allows you to process money with a currency.
There are also several other options for customizing how
the input and output of the data is handled.

+----------------------+---------------------------------------------------------------------------+
| Output Data Type     | ``Rollerworks\Component\Search\Extension\Core\Model\MoneyValue``          |
+----------------------+---------------------------------------------------------------------------+
| Options              | - `default_currency`_                                                     |
|                      | - `divisor`_                                                              |
|                      | - `precision`_                                                            |
|                      | - `grouping`_                                                             |
+----------------------+---------------------------------------------------------------------------+
| Inherited options    | - `invalid_message`_                                                      |
|                      | - `invalid_message_parameters`_                                           |
+----------------------+---------------------------------------------------------------------------+
| Parent type          | :doc:`field </reference/types/field>`                                     |
+----------------------+---------------------------------------------------------------------------+
| Class                | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\MoneyType` |
+----------------------+---------------------------------------------------------------------------+

Field Options
-------------

default_currency
~~~~~~~~~~~~~~~~

**type**: ``string`` **default**: ``EUR``

Specifies the default currency that the money is being specified in. This
value is only used when no currency symbol is detected.

This can be any `3 letter ISO 4217 code`_. You can also set this to false to
enforce an explicit currency symbol.

divisor
~~~~~~~

**type**: ``integer`` **default**: ``1``

If, for some reason, you need to divide your starting value by a number
before passing it to the storage later, you can use the ``divisor`` option.

precision
~~~~~~~~~

**type**: ``integer`` **default**: ``2``

For some reason, if you need some precision other than 2 decimal places,
you can modify this value. You probably won't need to do this unless,
for example, you want to round to the nearest dollar (set the precision
to ``0``).

.. include:: /reference/types/options/grouping.rst.inc

Inherited Options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc

.. _`3 letter ISO 4217 code`: http://en.wikipedia.org/wiki/ISO_4217
