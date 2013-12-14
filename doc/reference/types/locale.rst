.. index::
   single: Fields; locale

locale Field Type
=================

The ``locale`` type is a subset of the ``ChoiceType`` that allows the user
to select from a large list of locales (language+country). As an added bonus,
the locale names are displayed in the language of the user.

The "value" for each locale is either the two letter ISO639-1 *language* code
(e.g. ``fr``), or the language code followed by an underscore (``_``), then
the ISO3166 *country* code (e.g. ``fr_FR`` for French/France).

.. note::

   The locale of your user is guessed using :phpmethod:`Locale::getDefault`

Unlike the ``choice`` type, you don't need to specify a ``choices`` or
``choice_list`` option as the field type automatically uses a large list
of locales. You *can* specify either of these options manually, but then
you should just use the ``choice`` type directly.

+--------------------+----------------------------------------------------------------------------+
| Overridden Options | - `choices`_                                                               |
+--------------------+----------------------------------------------------------------------------+
| Inherited options  | - `invalid_message`_                                                       |
|                    | - `invalid_message_parameters`_                                            |
+--------------------+----------------------------------------------------------------------------+
| Parent type        | :doc:`field </reference/types/choice>`                                     |
+--------------------+----------------------------------------------------------------------------+
| Class              | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\LocaleType` |
+--------------------+----------------------------------------------------------------------------+

Overridden Options
------------------

choices
~~~~~~~

**default**: ``Symfony\Component\Intl\Intl::getLocaleBundle()->getLocaleNames()``

The choices option defaults to all locales. It uses the default locale to
specify the language.

Inherited options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
