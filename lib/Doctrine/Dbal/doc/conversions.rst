Value and Field Conversions
===========================

Conversions (or converts) for Doctrine DBAL are similar to the DataTransformers
used for transforming user-input to a normalized data format. Except that
the transformation happens in a single direction, and uses normalized data.

So why are are they useful? The power of relational databases is they
can store complex data structures, and allow to retrieve these records
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
    is properly configured with "date" or "datetime" as mapping-type.

There are three types of converters. Which can be combined together in one
single class. Conversions can happen at the column (field) and/or value.
So you can really utilize the power of your SQL queries.

.. note::

    Unlike DataTransformers you`re limited to *one* converter per search
    field. So setting an new convert (of the same) will overwrite previous
    ones.

Registering conversions can be done two way:

#. You can configure the conversion object on the ``SearchField`` by using
   the ``configureOptions`` method of your field type. Using:

   .. code-block:: php

       public function configureOptions(OptionsResolver $resolver)
       {
           $resolver->setDefaults(
               array('doctrine_dbal_conversion' => new MyConversionClass())
           );
       }

#. Or by setting the conversion directly at a query-generator (like the
   WhereBuilder) by calling ``setConverter`` like:

   .. code-block:: php

       $whereBuilder->setConverter('my-field-name', new MyConversionClass());

.. tip::

    The ``doctrine_dbal_conversion`` option also accepts a ``Closure`` object
    for lazy initializing, the closure is executed when creating the
    query-generator.

Before you get started, it's important to know the following about converters:

#. Converters should be stateless, meaning they don't remember anything
   about there operations. This is because the calling order of converter methods
   is not predictable and converters are only executed during the
   generation process, so using a cached result will not execute them.
#. Each converter method receives a `:class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\ConversionHints`
   object which provides access to the used database connection, SearchField
   configuration, column and optionally the conversionStrategy.
#. The ``$options`` array provides the options of the SearchField.

.. tip::

    If you use SQLite and need to register an user-defined-function (UDF)
    you can register a ``postConnect`` event listener at the Doctrine EventsManager
    to register the function.

    See `SqliteConnectionSubscriber.php`_ for an example.

SqlFieldConversion
------------------

The SqlFieldConversion transforms the provided column-name to an SQL
part like a sub-query or user-defined functional call.

The :class:`Rollerworks\\Component\\Search\\Doctrine\\SqlFieldConversionInterface`
requires the implementation of one method that must return the column
or anything that can be used as a replacement.

This example shows how to get the age of a person in years from their date
of birth. In short, the ``u.birthdate`` column is converted to an actual
age in years.

.. code-block:: php
    :linenos:

    namespace Acme\User\Search\Dbal\Conversion;

    use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
    use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;

    class AgeConversion implements SqlFieldConversionInterface
    {
        public function convertSqlField($column, array $options, ConversionHints $hints)
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
first function converts the date to an Interval and then the second function
extracts the years of the Interval and then casts the extracted years to a
integer. Now you easily search for users with a certain age.

.. _value_conversion:

ValueConversion
---------------

The ValueConversion converts the provided value to a format
that can be safely embedded within the generated query. This can e.g. convert
an object to a string or extract the value of an object/array and return this.

The :class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\ValueConversionInterface`
requires the implementation of two methods.

* The ``requiresBaseConversion`` method returns whether the configured column-type
  must first convert the value before passing it to this method.

  If you you configured ``date`` as column-type and passed an ``DateTime``
  object the ``DateTime`` will be first converted to a string like ``2015-02-26``.

  Return ``true`` to perform the base conversion, ``false`` to receive the value
  without any base transformation.

* The ``convertValue`` converts the provided value to usable format for the query.
  If the value is an integer it's embedded as-is else it's correctly quoted.

This example shows how to convert an ``DateTime`` object to an SQL supported
data value.

.. code-block:: php
    :linenos:

    use Doctrine\DBAL\Types\Type as DBALType;
    use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
    use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;

    class DateConversion implements ValueConversionInterface
    {
        public function requiresBaseConversion($input, array $options, ConversionHints $hints)
        {
            return false;
        }

        public function convertValue($value, array $options, ConversionHints $hints)
        {
            return DBALType::getType('date')->convertToDatabaseValue(
                $value,
                $hints->connection->getDatabasePlatform()
            );
        }
    }

SqlValueConversion
------------------

Value conversions very useful for converting an object to a string,
but if the object holds the structure of a value that needs to be applied
in a special way simply converting the value to a string will not be enough.
You need apply some SQL logic to further transform the value.

The :class:`Rollerworks\\Component\\Search\\Doctrine\\SqlValueConversionInterface`
is an extension of the ```ValueConversionInterface`` and allows to convert
the provided input using SQL query-fragment.

.. warning::

    The ``convertSqlValue`` method is required to return an SQL query-fragment
    that will be applied as-is!

    Be extremely cautious to properly escape and quote values, failing to do
    this can easily lead to a category of security holes called SQL injection,
    where a third party can modify the executed SQL and even execute their own
    queries through clever exploiting of the security hole!

    The only only save way to escape and quote a value is with:

    .. code-block:: php

        $hints->connection->quote($value);

    Don't try to replace the escaping with your own implementation
    as this may not provide a full protection against SQL injections.

One of these things is Spatial data which requires a special type of input.
The input must be provided using an SQL function, and therefor this can not be done
with only PHP.

This example describes how to implement a MySQL specific column type called Point.

The point class:

.. code-block:: php
    :linenos:

    namespace Acme\Geo;

    class Point
    {
        private $latitude;
        private $longitude;

        /**
         * @param float $latitude
         * @param float $longitude
         */
        public function __construct($latitude, $longitude)
        {
            $this->latitude  = $latitude;
            $this->longitude = $longitude;
        }

        /**
         * @return float
         */
        public function getLatitude()
        {
            return $this->latitude;
        }

        /**
         * @return float
         */
        public function getLongitude()
        {
            return $this->longitude;
        }
    }

And the SqlValueConversion class:

.. code-block:: php
    :linenos:

    namespace Acme\Geo\Search\Dbal\Conversion;

    use Acme\Geo\Point;
    use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
    use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;

    class GeoConversion implements SqlValueConversionInterface
    {
        public function requiresBaseConversion($input, array $options, ConversionHints $hints)
        {
            // The column-type is string so no base-conversion is possible or needed
            return false;
        }

        public function convertSqlValue($input, array $options, ConversionHints $hints)
        {
            if ($value instanceof Point) {
                $value = sprintf('POINT(%F %F)', $input->getLongitude(), $input->getLatitude());
            }

            return $value;
        }
    }

.. note::

    Alternatively you can choose to create a custom Type for Doctrine itself.
    See `Custom Mapping Types`_ in the Doctrine DBAL manual for more information.

    But doing this may cause issues with certain database vendors as Query generator
    doesn't now the value is wrapped inside a function and therefor is unable
    to adjust the generation process for better interoperability.

Using Strategies
----------------

You already know is it's possible to convert fields (columns) and values
to a different format and that you can wrap them with SQL statements. But
there is more.

Converting values and/or fields will work in most situations, but what if
you need to work with differing values like the birthday type which accepts
both dates and integer (age) values? To make this possible you need to introduce
conversion-strategies. Conversion-strategies are based on the `Strategy pattern`_
and work very simple and straightforward.

A conversion-strategy is determined by the given value, each field
and value gets a determined strategy assigned. If there is no strategy
(which is the default) ``null`` is used instead. Then each strategy is
applied per field and it's values, meaning that a field and the related
values are grouped together.

Say you have the following values-list for the birthday type: ``2010-01-05, 2010-05-05, 5``.
The first two values are dates, but third is an age. With the conversion
strategy enabled the system will process the values as follow;

    Dates are assigned strategy-number 1, integers (ages) are assigned with
    strategy-number 2.

    So ``2010-01-05`` and ``2010-05-05`` get strategy-number 1.
    And the ``5`` value gets strategy-number 2.

    Now when the query is generated the converter methods receive the strategy
    using the ``conversionStrategy`` property of the ``ConversionHints``, and
    is the method able to determine how the conversion should happen.

    But there is more to this idea, as the values don't need any SQL logic
    for the value conversion the generator can use the ``IN`` statement to
    group values of the same strategy together.

    So in the end you something like:

    .. code-block:: sql

        (((u.birthday IN('2010-01-05', '2010-05-05') OR search_conversion_age(u.birthday) IN(5))))

Implementing conversion-strategies
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

To make your own conversion support conversion-strategies you need to
implement the :class:`Rollerworks\\Component\\Search\\Doctrine\\Dbal\\ConversionStrategyInterface`
and the ``getConversionStrategy`` method.

.. note::

    If your conversion supports both the field and (sql)value conversions
    then all conversion methods will receive the determined strategy.

The following example uses a simplified version of AgeConversion class already
provided with this package.

.. code-block:: php
    :linenos:

    use Doctrine\DBAL\Types\Type as DBALType;
    use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
    use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
    use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
    use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
    use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

    /**
     * AgeDateConversion.
     *
     * The chosen conversion strategy is done as follow.
     *
     * * 1: When the provided value is an integer, the DB-value is converted to an age.
     * * 2: When the provided value is an DateTime the input-value is converted to an date string.
     * * 3: When the provided value is an DateTime and the mapping-type is not a date
     *      the input-value is converted to an date string and the DB-value is converted to a date.
     */
    class AgeDateConversion implements ConversionStrategyInterface, SqlFieldConversionInterface, ValueConversionInterface
    {
        public function getConversionStrategy($value, array $options, ConversionHints $hints)
        {
            if (!$value instanceof \DateTime && !ctype_digit((string) $value)) {
                throw new UnexpectedTypeException($value, '\DateTime object or integer');
            }

            if ($value instanceof \DateTime) {
                return $hints->field->getDbType()->getName() !== 'date' ? 2 : 3;
            }

            return 1;
        }

        public function convertSqlField($column, array $options, ConversionHints $hints)
        {
            if (3 === $hints->conversionStrategy) {
                return $column;
            }

            if (2 === $hints->conversionStrategy) {
                return "CAST($column AS DATE)";
            }

            $platform = $hints->connection->getDatabasePlatform()->getName();

            switch ($platform) {
                case 'postgresql':
                    return "to_char(age($column), 'YYYY'::text)::integer";

                default:
                    throw new \RuntimeException(
                        sprintf('Unsupported platform "%s" for AgeDateConversion.', $platform)
                    );
            }
        }

        public function requiresBaseConversion($input, array $options, ConversionHints $hints)
        {
            return false;
        }

        public function convertValue($value, array $options, ConversionHints $hints)
        {
            if (2 === $hints->conversionStrategy || 3 === $hints->conversionStrategy) {
                return DBALType::getType('date')->convertToDatabaseValue(
                    $value,
                    $hints->connection->getDatabasePlatform()
                );
            }

            return (int) $value;
        }
    }

.. _`SqliteConnectionSubscriber.php`: https://github.com/rollerworks/rollerworks-search-doctrine-dbal/blob/master/src/EventSubscriber/SqliteConnectionSubscriber.php
.. _`Custom Mapping Types`: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#custom-mapping-types
.. _Strategy pattern: http://en.wikipedia.org/wiki/Strategy_pattern
