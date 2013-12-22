Extensions
==========

For creating search-field a type (extension) class please read the Rollerworks Search Component
documentation.

Registering types with the SearchFactory is done by tagging the service with
`rollerworks_search.type` or `rollerworks_search.type_extension` respectively.

The tag must have an `alias` parameter indicating the type-name,
or the field-type that's being extended.

**Note: For field-types the alias must equal the actual type-name.**
