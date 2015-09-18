RollerworksSearchBundle Configuration Reference
===============================================

All available configuration options are listed below with their default values.

```yaml
# app/config/config.yml
rollerworks_search:
    metadata:
        cache_driver:              "rollerworks_search.metadata.cache_driver.file"                 # Service-id
        cache_dir:                 "%kernel.cache_dir%/rollerworks_search_metadata"                # required for the default driver
        cache_freshness_validator: "rollerworks_search.metadata.freshness_validator.file_tracking" # Service-id

        auto_mapping:   true
        # mappings are empty by default
        #mappings:
        #    MappingName: # Bundle name for example
        #        mapping:    true # Enable/disable mapping
        #        dir:        ~    # Directory to find mapping-data, relative when 'is_bundle' is true (supports parameters)
        #        prefix:     ~    # Namespace prefix eg 'Acme\UserBundle\Model\'
        #        is_bundle:  ~    # When true, the `MappingName` is the name of the bundle (eg. AcmeMyAppBundle)
    # fieldsets is empty by default
    #fieldsets:
    #    fieldset_name: # Name of the fieldset
    #        # Note: Imports require that the metadata is enabled, search-name is not the property-name
    #        import: # []
    #            -
    #                class: "Fully-qualified-class-name" # eg. Acme\UserBundle\Model\User
    #                include_fields: []                  # Search field-names to include, everything else is excluded
    #                exclude_fields: []                  # Search field-names to exclude, everything else is included
    #            # Or (shorter) imports all fields.
    #            - "Fully-qualified-class-name"          # eg. Acme\UserBundle\Model\User
    #        fields:
    #            field_name:
    #                type:           ~     # Required
    #                model_class:    ~     # Optional - Fully qualified class-name, eg. Acme\UserBundle\Model\User
    #                model_property: ~     # Required then model_class is set
    #                options:        []
```

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:rollerworks-search="http://rollerworks.github.io/schema/dic/rollerworks-search"
    xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd
                        http://rollerworks.github.io/schema/dic/rollerworks-search http://rollerworks.github.io/schema/dic/rollerworks-search/rollerworks-search-1.0.xsd">

    <rollerworks-search:config>

        <rollerworks-search:metadata
            cache-dir="rollerworks_search.cache.array_driver"
            cache-driver="%kernel.cache_dir%/rollerworks_search_metadata"
            cache-freshness-validator="rollerworks_search.metadata.freshness_validator.file_tracking"
            auto-mapping="true"
        >
            <!--<rollerworks-search:mapping name="MappingName" dir="" prefix="" is-bundle="true" />-->
        </rollerworks-search:metadata>

        <!--
        <rollerworks-search:fieldset name="field1">
            <rollerworks-search:field name="" type="" model-class="" model-property="">
                <rollerworks-search:option key="" type="collection">
                    <rollerworks-search:option key=""></rollerworks-search:option>
                    <rollerworks-search:option key="" type="collection">
                        <rollerworks-search:option key="0"></rollerworks-search:option>
                        <rollerworks-search:option key="1"></rollerworks-search:option>
                    </rollerworks-search:option>
                </rollerworks-search:option>
                <rollerworks-search:option key=""></rollerworks-search:option>
            </rollerworks-search:field>

            <rollerworks-search:import class="Model\User">
                < ! -- Either include-field[0, Inf] or exclude-field[0, Inf] but not both -- >
                <rollerworks-search:include-field>field-name</rollerworks-search:include-field>
                <rollerworks-search:exclude-field>field-name</rollerworks-search:exclude-field>
            </rollerworks-search:import>
        </rollerworks-search:fieldset>
        -->
    </rollerworks-search:config>
</container>
```
