.. index::
   single: Fields; country

country Field Type
==================

The ``currency`` type is a subset of the :doc:`choice type </reference/types/choice>`
that allows the user to select from a large list of countries of the world.

The "value" for each country is the two-letter country code.

.. note::

   The locale of your user is guessed using :phpmethod:`Locale::getDefault`

Unlike the ``choice`` type, you don't need to specify a ``choices`` or
``choice_list`` option as the field type automatically uses all of the countries
of the world. You *can* specify either of these options manually, but then
you should just use the ``choice`` type directly.

+--------------------+------------------------------------------------------------------------------+
| Overridden Options | - `choices`_                                                                 |
+--------------------+------------------------------------------------------------------------------+
| Inherited options  | - `invalid_message`_                                                         |
|                    | - `invalid_message_parameters`_                                              |
+--------------------+------------------------------------------------------------------------------+
| Parent type        | :doc:`field </reference/types/choice>`                                       |
+--------------------+------------------------------------------------------------------------------+
| Class              | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\CountryType`  |
+--------------------+------------------------------------------------------------------------------+

Overridden Options
------------------

choices
~~~~~~~

**default**: ``Symfony\Component\Intl\Intl::getRegionBundle()->getCountryNames()``

The country type defaults the ``choices`` option to the whole list of countries.
The locale is used to translate the countries names.

Inherited options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
