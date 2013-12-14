trim
~~~~

**type**: ``boolean`` **default**: ``true``

If true, the whitespace of the submitted string value will be stripped
via the ``trim()`` function when the data is transformed. This guarantees that
if a value is submitted with extra whitespace, it will be removed before
the value is passed to the storage layer.
