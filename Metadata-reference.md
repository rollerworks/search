Class:

```php
 "rollerworks_search" = {
    "fieldset" = "..." # optional, fields can also be configured using the ResourceMetadata properties (requires a custom FieldSetRegistry)
    "accepted_fieldsets" = [] # limiting, otherwise all are accepted. Ignored when the FieldSet is kept within the ResourceMetadata
    "processor" = { } # Options for the processor
    "doctrine_orm" = {
        "FieldSetName | *" = {
            "relations" = {
                "alias" = { "type (left | right | inner)" "join", "conditionType", "condition", "indexBy" }
            }
            "mappings" = {
                "mapping-name" = { "property", "alias", "db_type" }
            }
        }
    },
    # Fields, merged with property fields. Mainly to be used for children
    "fields" = {}
}
```

Property (FieldSet building):

```php
"rollerworks_search" = {
    "field" = { "name" = { "type", "options" } } # Optional, cannot be merged with existing FieldSet's
}
```

**Note:** FieldSet building is limited to the current resource (no children)
and only allows one configuration. Backend/frontend configurations require
separate FieldSet configurators instead.
