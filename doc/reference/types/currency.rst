.. index::
    single: Fields; currency

currency Field Type
===================

The ``currency`` type is a subset of the
:doc:`choice type </reference/types/choice>` that allows the user to
select from a large list of `3-letter ISO 4217`_ currencies.

Unlike the ``choice`` type, you don't need to specify a ``choices`` or
``choice_list`` option as the field type automatically uses a large list of
currencies. You *can* specify either of these options manually, but then you
should just use the ``choice`` type directly.

+--------------------+------------------------------------------------------------------------------+
| Overridden Options | - `choices`_                                                                 |
+--------------------+------------------------------------------------------------------------------+
| Inherited options  | - `invalid_message`_                                                         |
|                    | - `invalid_message_parameters`_                                              |
+--------------------+------------------------------------------------------------------------------+
| Parent type        | :doc:`field </reference/types/choice>`                                       |
+--------------------+------------------------------------------------------------------------------+
| Class              | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\CurrencyType` |
+--------------------+------------------------------------------------------------------------------+

Overridden Options
------------------

choices
~~~~~~~

**default**: ``Symfony\Component\Intl\Intl::getCurrencyBundle()->getCurrencyNames()``

The choices option defaults to all currencies.

Inherited options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc

.. _`3-letter ISO 4217`: http://en.wikipedia.org/wiki/ISO_4217
