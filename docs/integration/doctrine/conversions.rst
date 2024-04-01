Value and Column Conversions (DBAL)
===================================

.. cuation::

    Since RollerworksSearch v2.0-ALPHA22 conversions for Doctrine ORM
    are handled separately. See the related chapter for reference.

Conversions for Doctrine DBAL are similar to the DataTransformers
used for transforming user-input to a model data-format. Except that
the transformation happens in a single direction and are applied SQL.

So why are are they useful? The power of relational databases is
storing complex data structures, and allow to retrieve these records
into a transformed and combined result.

For example the "birthday" field type accepts both an actual (birth)date
or an age value like "9". But you're not going to store the actual age,
as this would require constant updating. Instead you calculate the age by
the date that is stored in the database (you transform the data). This
transformation process is called a conversion.

.. note::

    Some values don't have to be converted if the Doctrine DBAL Type
    is already able to process the value as-is.

    For example a ``DateTime`` object can be safely used *if* the mapping-type
    is properly configured with "date" or "datetime" as column type.

There are two types of converters. Which can be combined together in one
single class. Conversions can happen at the column and/or value.
So you can really utilize the power of your SQL queries.

.. note::

    Unlike DataTransformers you`re limited to *one* converter per search
    field. So setting an new conversion will overwrite previous one.

Conversions are registered by setting the conversion object on the
``SearchField`` by using the ``configureOptions`` method of the field type.

Using:

   .. code-block:: php

       public function configureOptions(OptionsResolver $resolver)
       {
           $resolver->setDefaults([
               'doctrine_dbal_conversion' => new MyConversionClass(),
           ]);
       }

.. tip::

    The ``doctrine_dbal_conversion`` option also accepts a ``Closure`` object
    for lazy initializing, the closure is executed when initializing the
    condition-generator.

   .. code-block:: php

       public function configureOptions(OptionsResolver $resolver)
       {
           $resolver->setDefaults([
               'doctrine_dbal_conversion' => static fn () => new MyConversionClass(),
           ]);
       }

Before you get started, it's important to know the following about conversions:

#. Conversion should be stateless, meaning they don't remember anything
   about there operations. This is because the calling order of conversion methods
   is not predictable and conversions are only executed during the
   generation process, so using a cached result does not execute them.
#. Each method receives a :class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\ConversionHints`
   object which provides access to the used database connection, SearchField
   configuration, column, and helper methods for using parameter-placeholders and
   getting the actual value that is currently being processed (in the column context).
#. The ``$options`` array provides the options of the SearchField.

See existing conversions for a more detailed example.
https://github.com/rollerworks/search/tree/master/lib/Doctrine/Dbal/Extension/Conversion

.. tip::

    To use a conversion for an existing FieldType use a
    :ref:`FieldTypeExtension <field_type_extension>`.

ColumnConversion
----------------

A ColumnConversion transforms the provided column to an SQL
part like a sub-query or user-defined functional call.

The :class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\ColumnConversion`
requires the implementation of one method that must return the column
or anything that can be used as a replacement.

This example shows how to get the age of a person in years from their date
of birth. In short, the ``u.birthdate`` column is converted to an actual
age in years::

    namespace Acme\User\Search\Dbal\Conversion;

    use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
    use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;

    class AgeConversion implements ColumnConversion
    {
        public function convertColumn(string $column, array $options, ConversionHints $hints): string
        {
            if ('postgresql' === $hints->connection->getDatabasePlatform()->getName()) {
                return "TO_CHAR('YYYY', AGE($column))";
            } else {
                // Return unconverted
                return $fieldName;
            }
        }
    }

The ``u.birthdate`` column reference is wrapped inside two function calls,
the first function converts the date to an Interval and then the second function
extracts the years of the Interval and then casts the extracted years to a
integer. Now you easily search for users with a certain age.

Value Specific Conversion
~~~~~~~~~~~~~~~~~~~~~~~~~

Most column versions are singular, but in some cases you might need
to apply a different conversion depending on the value that is being
processed at the moment.

For the ``Rollerworks\\Component\\Search\\Extension\\Doctrine\\Dbal\\Conversion\\DateIntervalConversion`` you need
to know whether the value needs to be subtracted or added, depending on the processing context.

For the :class:`Rollerworks\\Component\\Search\\Extension\\Doctrine\\Dbal\\Conversion\\MoneyValueConversion`
you need to know the unit (*precision*) the Currency, but don't have access to the database value.

* The ``$context`` property of the ``ConversionHints`` provides
  the current processing-context, see the ``CONTEXT_`` constants of the
  ``ConversionHints`` for possible options;

* The ``$originalValue`` holds the actual value-holder
  that is currently being processed, depending on the context
  this either a ``Range``, ``Compare`` value-holder object or ``mixed``
  type value for ``CONTEXT_SIMPLE_VALUE``.

When you only need the value (regardless of the context) use the
``getProcessingValue()`` method.

.. _value_conversion:

ValueConversion
---------------

A ValueConversion converts the provided value to an SQL part like a sub-query
or user-defined functional call.

The :class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\ValueConversion`
requires the implementation of one method that must return the value
as SQL query-fragment.

.. warning::

    The ``convertValue`` method is required to return an SQL query-fragment
    that will be applied as-is!

    Avoid embedding the values directly, use the ``createParamReferenceFor``
    on the ``$hints`` instead.

    Failing to do this can easily lead to a category of security holes called
    SQL injection, where a third party can modify the executed SQL and even
    execute their own queries through clever exploiting of the security hole!

    The only only save way to embed a value is with:

    .. code-block:: php

        $hints->createParamReferenceFor($value); // will return param-name `:search_x` where x an incremented number

    Don't try to replace the escaping with your own implementation
    as this may not provide a full protection against SQL injections.

One of these values is Spatial data which requires a special type of input.
The input must be provided using an SQL function, and there for this can not
be done with only PHP.

This example describes how to implement a MySQL specific column type called Point.

The point class::

    namespace Acme\Geo;

    class Point
    {
        private $latitude;
        private $longitude;

        public function __construct(float $latitude, float $longitude)
        {
            $this->latitude  = $latitude;
            $this->longitude = $longitude;
        }

        public function getLatitude(): float
        {
            return $this->latitude;
        }

        public function getLongitude(): float
        {
            return $this->longitude;
        }
    }

And the GeoConversion class::

    namespace Acme\Geo\Search\Dbal\Conversion;

    use Acme\Geo\Point;
    use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
    use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;

    class GeoConversion implements ValueConversion
    {
        public function convertValue($input, array $options, ConversionHints $hints): string
        {
            if ($value instanceof Point) {
                // The second argument is a Doctrine DBAL type used for the binding-type and
                // any SQL specific transformation (otherwise the value is marked as text and used as-is).
                $long = $hints->createParamReferenceFor($input->getLongitude(), 'decimal');
                $lat = $hints->createParamReferenceFor($input->getLatitude(), 'decimal');

                $value = sprintf('POINT(%s, %s)', $long, $lat);
            }

            return $value;
        }
    }

.. note::

    Alternatively you can choose to create a custom Type for Doctrine DBAL.
    See `Custom Mapping Types`_ in the Doctrine DBAL manual for more information.

    But doing this may cause issues with certain database vendors as the generator
    doesn't now the value is wrapped inside a function and there for is unable
    to adjust the generation process for better interoperability.

Testing Conversions
-------------------

To test if the conversions work as expected your can compare the generated,
SQL with what your expecting, however there's no promise that the SQL
structure will remain the same for the future releases.

The only way to ensure your conversions work is to run it against an
actual database with existing records.

.. _`Custom Mapping Types`: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#custom-mapping-types
