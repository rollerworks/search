.. index::
   single: Fields; choice

choice Field Type
=================

A multi-purpose field used to allow the user to "choose" one or more options.
It can be rendered as a ``select`` tag, radio buttons, or checkboxes.

To use this field, you must specify *either* the ``choice_list`` or ``choices``
option.

+--------------------+-------------------------------------------------------------------------------+
| Output Data Type   | can be various types depending on the choice value                            |
+--------------------+-------------------------------------------------------------------------------+
| Options            | - `choices`_                                                                  |
|                    | - `choice_list`_                                                              |
+--------------------+-------------------------------------------------------------------------------+
| Inherited options  | - `invalid_message`_                                                          |
|                    | - `invalid_message_parameters`_                                               |
+--------------------+-------------------------------------------------------------------------------+
| Parent type        | :doc:`field </reference/types/field>`                                         |
+--------------------+-------------------------------------------------------------------------------+
| Class              | :class:`Rollerworks\\Component\\Search\\Extension\\Core\\Type\\ChoiceType`    |
+--------------------+-------------------------------------------------------------------------------+

Example Usage
-------------

The easiest way to use this field is to specify the choices directly via the
``choices`` option. The key of the array becomes the value that's actually
set on your underlying object (e.g. ``m``), while the value is what the
user choices/types on the input (e.g. ``Male``).

.. code-block:: php

    $builder->add('gender', 'choice', array(
        'choices'   => array('m' => 'Male', 'f' => 'Female'),
        'required'  => false,
    ));

You can also use the ``choice_list`` option, which takes an object that can
specify the choices.

Field Options
-------------

choices
~~~~~~~

**type**: ``array`` **default**: ``array()``

This is the most basic way to specify the choices that should be used
by this field. The ``choices`` option is an array, where the array key
is the item value and the array value is the item's label::

    $builder->add('gender', 'choice', array(
        'choices' => array('m' => 'Male', 'f' => 'Female')
    ));

choice_list
~~~~~~~~~~~

**type**: ``Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceListInterface``

This is one way of specifying the options to be used for this field.
The ``choice_list`` option must be an instance of the ``ChoiceListInterface``.
For more advanced cases, a custom class that implements the interface
can be created to supply the choices.

Inherited options
-----------------

These options inherit from the :doc:`field </reference/types/field>` type:

.. include:: /reference/types/options/invalid_message.rst.inc

.. include:: /reference/types/options/invalid_message_parameters.rst.inc
