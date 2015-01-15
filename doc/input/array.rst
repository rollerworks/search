ArrayInput
==========

Processes input provided as a PHP Array.

The provided input must be structured as follow;

Each entry must contain an array with either 'fields' and/or groups.
Optionally the array can contain ``'logical-case' => 'AND'`` to make it AND-cased.

The "groups" array contains numeric groups with the structure as described
above (fields and/or groups).

The fields array is an associative array where each key is the field-name
and the values as follow.

All the keys are optional, but at least one must exists.

.. code:: php

     array(
        'single-values'   => array('value1', 'value2')
        'excluded-values' => array('my value1', 'my value2')
        'ranges'          => array(array('lower'=> 10, 'upper' => 20))
        'excluded-ranges' => array(array('lower'=> 25, 'upper' => 30))
        'comparisons'     => array(array('value'=> 50, 'operator' => '>'))
        'pattern-matchers' => array(array('value'=> 'foo', 'type' => 'STARTS_WITH'))
    )

The type of 'pattern-matchers' must either one of the following:

* CONTAINS
* STARTS_WITH
* ENDS_WITH
* REGEX
* NOT_CONTAINS
* NOT_STARTS_WITH
* NOT_ENDS_WITH
* NOT_REGEX

Full example:

.. code:: php

    array(
        'fields' => array(
            'field1' => array(
                'ranges' => array(
                    array('lower' => 10, 'upper' => 20),
                    array('lower' => 30, 'upper' => 40),
                    array('lower' => 50, 'upper' => 60, 'inclusive-lower' => false),
                    array('lower' => 70, 'upper' => 80, 'inclusive-upper' => false),
                )
            )
        ),
        'groups' => array(
            array(
                'logical-case' => 'AND'
                'fields' => array(
                    'field1' => array(
                        'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                    )
                )
            )
        )
    )
