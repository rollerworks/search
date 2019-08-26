SearchConditionSerializer
-------------------------

The :class:`Rollerworks\\Component\\Search\\SearchConditionSerializer`
class helps with (un)serializing a ``SearchCondition``.

A SearchCondition holds a condition and a `FieldSet` configuration.

The condition and it's values can be directly serialized, but the FieldSet is
more difficult. As a Field can have closures and/or resource reference's, it's
to complex to serialize.

Instead of serializing the FieldSet the serializer stores the FieldSet set-name,
and when unserializing it loads the FieldSet using a :class:`Rollerworks\\Component\\Search\\FieldSetRegistry`.

.. note::

    The Serializer doesn't check if the FieldSet is actually loadable
    by the FieldSetRegistry. You must ensure the FieldSet is loadable,
    else when unserializing you get an exception.

.. caution::

    Suffice to say, never store a serialized SearchCondition in the client-side!
    The Serializer still uses the PHP serialize/unserialize functions, and due to
    unpredictable values can't provide a list of trusted classes.

    Use an Exporter to store a SearchCondition in an untrusted storage.
