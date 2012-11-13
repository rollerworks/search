ArrayInput
==========

Accepts filtering preference as a PHP Array.

The provided input must be structured.
The root is an array where each entry is as group with.

.. code:: php

    array('fieldname' => ( structure ))

There structure must contain the following, all keys are optional - but at least must exists.

.. code:: php

    array(
        'single-values'   => array('value1', 'value2')
        'excluded-values' => array('my value1', 'my value2')
        'ranges'          => array(array('lower'=> 10, 'upper' => 20))
        'excluded-ranges' => array(array('lower'=> 25, 'upper' => 30))
        'comparisons'     => array(array('value'=> 50, 'operator' => '>'))
    )

.. note::

    "Value" must must be either an integer or string.

    Big integer/float must quoted.
