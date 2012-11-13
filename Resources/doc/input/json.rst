JsonInput
=========

Accepts filtering preference in the JSON format.

The provided input must be structured.
The root is an array where each entry is a group with

.. code:: js

    { "fieldname": { structure } }

There structure must contain the following, all keys are optional - but at least must exists.

.. code:: js

    {
         "single-values":    [ "value1", "value2" ]
         "excluded-values":  [ "my value1", "my value2" ]
         "ranges":           [ { "lower": 10, "upper": 20 } ]
         "excluded-ranges":  [ { "lower": 25, "upper": 30 } ]
         "comparisons":      [ { "value": 50,"operator": ">" } ]
     }

.. note::

    Big integer/float must quoted.
