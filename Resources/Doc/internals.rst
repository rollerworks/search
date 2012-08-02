Internals
=========

This sections is *an attempt* describe the internals of the
RecordFilterBundle. And is by no means complete.

Basic processing
----------------

1. Provide filtering preference as user-input to InputInterface
2. Parse filtering input and convert to FilterValuesBag
3. Pass input to an Formatter {validate/format, normalize, etc.}
4. Use final result for filtering.

Configuration
-------------

Filtering is defined as an set of FilterConfig objects (known as Fields),
known as an FieldSet object.

An FilterConfig object consists of:

* An unique field-name
* Filtering type
* Is required?
* accept-ranges
* accept-compares
* (optional) class/property reference

FieldSets and Field object are created on-the fly,
an FilterConfig object can be used by multiple FieldSets.

Record/Sql
----------

Creates an SQL/DQL WHERE-case Query based on the given FieldSet and
values provided by the Formatter.

The SQL field-names are resolved using the information
of the class/property reference.

Conversions are either done by reading the Metadata
of the class (value) and set explicit (field), as this uses Doctrine
there is no problem in hard-requirement.

Metadata Mapping
----------------

Metadata Mapping or Mapping for short, is used for populating an FieldSet
with configuration based on the metadata of an class.
The metadata is read using the JMS/Metadata Component.

Metadata can be stored as property annotations inside
the class self or using loose YAML/XML files (todo).

CacheWarming code
-----------------

The cache warming (depending on the application configuration),
creates FieldSet classes by reading there configuration from the `rollerworks_record_filter.fieldsets`
configuration of app/config.ext

*By using the app/config.ext we don't have to worry about merging
and such as this is handled by the Config Component. Plus we get validation for free!*

Factories
---------

Factories are used for creating class files at runtime or cache-warming.

When referring to 'classes' these are meant as *class files*.

SqlWhereBuilderFactory
~~~~~~~~~~~~~~~~~~~~~~

This factory is used to create SqlWhereBuilder classes based on the FieldSet configuration.

* Only fields present in the FieldSet are used
* Only when an field supports ranges/compares the code for this is generated.

FieldSetFactory
~~~~~~~~~~~~~~~

This is factory is used for creating FieldSet classes for faster loading.

The FieldSet is created as the state is, including name and present fields.
