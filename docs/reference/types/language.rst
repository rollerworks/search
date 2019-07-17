.. index::
   single: Fields; language

language Field Type
===================

The ``language`` type is a subset of the :doc:`choice type </reference/types/choice>` that allows the user
to select from a large list of languages. As an added bonus, the language names
are displayed in the language of the user.

The "value" for each language is the *Unicode language identifier*
(e.g. ``fr`` or ``zh-Hant``).

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
| Class              | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\LanguageType` |
+--------------------+------------------------------------------------------------------------------+

Overridden Options
------------------

choices
~~~~~~~

**default**: ``Symfony\Component\Intl\Intl::getLanguageBundle()->getLanguageNames()``.

The choices option defaults to all languages.
The default locale is used to translate the languages names.

Inherited options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
