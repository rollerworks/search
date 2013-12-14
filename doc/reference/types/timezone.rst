.. index::
   single: Fields; timezone

timezone Field Type
===================

The ``timezone`` type is a subset of the ``ChoiceType`` that allows the user
to select from all possible timezones.

The "value" for each timezone is the full timezone name, such as ``America/Chicago``
or ``Europe/Istanbul``.

Unlike the ``choice`` type, you don't need to specify a ``choices`` or
``choice_list`` option as the field type automatically uses a large list
of timezones. You *can* specify either of these options manually, but then
you should just use the ``choice`` type directly.

+--------------------+------------------------------------------------------------------------------+
| Overridden Options | - `choices`_                                                                 |
+--------------------+------------------------------------------------------------------------------+
| Inherited options  | - `invalid_message`_                                                         |
|                    | - `invalid_message_parameters`_                                              |
+--------------------+------------------------------------------------------------------------------+
| Parent type        | :doc:`field </reference/types/choice>`                                       |
+--------------------+------------------------------------------------------------------------------+
| Class              | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\TimezoneType` |
+--------------------+------------------------------------------------------------------------------+

Overridden Options
------------------

choices
~~~~~~~

**default**: ``Rollerworks\\Component\\Search\\Extension\\Core\\Type\\TimezoneType::getTimezones()``

The choices option defaults to all languages.
The default locale is used to translate the languages names.

Inherited options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
