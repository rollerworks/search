Class:

```php
 "rollerworks_search" = {
    "contexts" = {
        "_defaults" {
            # Set defaults, merged with more specific configuration (and _any)
        },
        "ContextName | _any" = { # ContextName is provided using event listeners (request#attributes[_search_context])
            "fieldset" = "...", # Required
            "processor" = {
                "cache_ttl" = "60" # time in seconds
            }, # Options for the processor
            "doctrine_orm" = {
                "relations" = {
                    "alias" = { "type" = "(left | right | inner)", "entity" "join", "conditionType" = null, "condition" = null, "indexBy" = null }
                },
                "mappings" = {
                    "mapping-name" = { "property" = "...", "alias" = "...", "db_type" = null }
                },
            },
        }
    }
}
```
