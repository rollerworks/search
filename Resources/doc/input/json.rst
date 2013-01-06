JsonInput
=========

Accepts filtering preference in the JSON format.

The provided input must be structured.
The root is an array where each entry is a group with

.. code:: js

    // all types are optional - but at least one must exists.

    {
        "fieldname": {
            "single-values":    [ "value1", "value2" ]
            "excluded-values":  [ "my value1", "my value2" ]
            "ranges":           [ { "lower": 10, "upper": 20 } ]
            "excluded-ranges":  [ { "lower": 25, "upper": 30 } ]
            "comparisons":      [ { "value": 50, "operator": ">" } ]
        }
    }

.. note::

    Big integers/floats must quoted.
