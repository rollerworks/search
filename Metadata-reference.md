Class:

```php
 "rollerworks_search" = {
    "contexts" = {
        "_default" {
            # Set defaults, merged with more specific configuration (and _any)
        },
        "ContextName | _any" = { # ContextName is provided using event listeners (request#attributes[_search_context])
            "fieldset" = "...", # Required
            "processor" = { }, # Options for the processor
            "doctrine_orm" = {
                "relations" = {
                    "alias" = { "type (left | right | inner)", "entity" "join", "conditionType", "condition", "indexBy" }
                },
                "mappings" = {
                    "mapping-name" = { "property", "alias", "db_type" }
                },
            },
        }
    }
}
```
