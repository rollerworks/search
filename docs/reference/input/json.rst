.. index::
   single: input; json

JsonInput Format
================

The provided input must be structured as follow:

* Each entry must contain an object with either ``fields`` and/or ``groups``.
* Optionally the object can contain ``'logical-case' => 'AND'`` to make it AND-cased.

The ``groups`` must have array one or more arrays with the structure as
described above (``fields`` and/or ``groups``).

The fields array is an associative array where each key is the field-name
and the values as follow (all the keys are optional, but at least one must
exists):

.. code-block:: javascript
    :linenos:

    {
        "simple-values": [
            "value1",
            "value2"
        ],
        "excluded-values": [
            "my value1",
            "my value2"
        ],
        "ranges": [
            {
                "lower": 10,
                "upper": 20
            }
        ],
        "excluded-ranges": [
            {
                "lower": 25,
                "upper": 30
            }
        ],
        "comparisons": [
            {
                "value": 50,
                "operator": ">"
            }
        ],
        "pattern-matchers": [
            {
                "value": "foo",
                "type": "STARTS_WITH"
            }
        ]
    }

The type of ``pattern-matchers`` must either one of the following:

* ``CONTAINS```
* ``STARTS_WITH``
* ``ENDS_WITH``
* ``NOT_CONTAINS``
* ``NOT_STARTS_WITH``
* ``NOT_ENDS_WITH``

Full example:

.. code-block:: javascript
    :linenos:

    {
        "logical-case": "AND",
        "fields": {
            "field1": {
                "ranges": [
                    {
                        "lower": 10,
                        "upper": 20
                    },
                    {
                        "lower": 30,
                        "upper": 40
                    },
                    {
                        "lower": 50,
                        "upper": 60,
                        "inclusive-lower": false
                    },
                    {
                        "lower": 70,
                        "upper": 80,
                        "inclusive-upper": false
                    }
                ]
            }
        },
        "groups": [
            {
                "logical-case": "AND",
                "fields": {
                    "field1": {
                        "simple-values": [
                            "value",
                            "value2",
                            "value3",
                            "value4",
                            "value5"
                        ]
                    }
                },
                "groups": [...]
            }
        ]
    }

.. note::

    Internally the JsonInput processor uses the same input structure as :doc:`array`.

    The advantage of using the JsonInput processor instead decoding the JSON object
    yourself is that the JsonInput processor does a linting on the provided input,
    ensuring the JSON input is valid and will give a more detail message on whats
    wrong.
