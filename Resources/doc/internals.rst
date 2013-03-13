Internals
=========

This sections is *an attempt* to describe the internals of the
RecordFilterBundle; it is by no means complete.

Basic processing
----------------

1. Provide filtering preference as user-input to ``InputInterface``
2. Parse filtering input and convert to ``FilterValuesBag``
3. Pass input to an ``Formatter`` (validate/format, normalize, etc.)
4. Use final result for filtering.

Configuration
-------------

Filtering is defined as a set of ``FilterConfig`` objects (known as ``Fields``),
known as a ``FieldSet`` object.

A ``FilterConfig`` object consists of:

* An unique field-name
* Filtering type
* Is required?
* accept-ranges
* accept-compares
* (optional) class/property reference

``FieldSets`` and ``Field`` object are created on-the fly.
A ``FilterConfig`` object can be used by multiple ``FieldSets``.

Doctrine/ORM
------------

Creates an WHERE case Query based on the given ``FieldSet`` and
values provided by the ``Formatter``.

The field-names are resolved using the information
of the class/property reference.

Conversions are done by reading the ``Metadata`` of the class.

Metadata
--------

Metadata is used for populating a ``FieldSet`` with configuration based
on the metadata of one or more classes.
The metadata is read using the `JMS/Metadata Component <https://github.com/schmittjoh/metadata>`_.

Metadata can be stored as property annotations inside
the class itself or by using YAML/XML files.

CacheWarming
------------

The cache warming (depending on the application configuration),
creates ``FieldSet`` classes by reading the configuration from the ``rollerworks_record_filter.fieldsets``
configuration of ``app/config.ext``.

By using the ``app/config.ext`` we don't have to worry about merging
and such as this is handled by the `Config Component`. Plus we get validation for free!

Factories
---------

Factories are used for creating class files at runtime or cache-warming.

When referring to 'classes' these are meant as *class files*.

FieldSetFactory
~~~~~~~~~~~~~~~

This is factory is used for creating `FieldSet` classes for faster loading.

The `FieldSet` is created as the state is, including name and present fields.

OrmWhereBuilderFactory
~~~~~~~~~~~~~~~~~~~~~~

This factory is used to create ``OrmWhereBuilder`` classes based on the ``FieldSet`` configuration.

* Only fields present in the ``FieldSet`` are used.
* Only fields having a valid property reference are used.
* Only when a field supports ranges/compares, the code for this is generated.
