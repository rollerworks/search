Converters
----------

Converters are similar to data transformers, except that the transformation
is one direction only: normalized to converted.

The original values on the SearchCondition are left unchanged.

.. note::

    Unlike data-transformers you`re limited to only
    *one* converter per search field.

Converters can be set on a ``SearchField`` using the ``doctrine_dbal_conversion``
option or directly at a query-generator (like the WhereBuilder).

Setting the conversion directly is done by calling ``setConverter($fieldName, $convertor)`` on the
query-generator.

.. note::

    Conversions configured on a field are only set at the 'query-generator',
    when you use a builder to configure the generator for you.

There are three type of converters, which you can combine together in one class.

* ValueConversion: Converts a normalized value to another format
* SqlFieldConversion: Applies an SQL statement on the field
* SqlValuedConversion: Applies an SQL statement on the value

Each converter-type receives the field's options and some 'hints' information, containing
things like the DB connection and database type.

See the converters API documentation for more information on the hints.

.. caution::

    Be very cautious when using SQL converters, the returned value is always used as-is.
    So any parameters or values embedded in the result **must be properly escaped**!

    Not doing so will result in a possible SQL injection security tread.

ValueConversion
~~~~~~~~~~~~~~~

Value conversion is applied to the value of the search statement.

In this example we will convert an ``DateTime`` object to an ISO-datetime value.

.. note::

    Doctrine can already handle ``DateTime`` objects.
    So normally you won't have to convert this.

.. code-block:: php
    :linenos:

    namespace Acme\User\Search\Dbal\Conversion;

    use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;

    class DateTimeValueConversion implements ValueConversionInterfaceInterface
    {
        public function requiresBaseConversion($input, array $options, array $hints)
        {
            // We don't want the Doctrine type to pre-convert the value for us
            return false;
        }

        public function convertValue($input, array $options, array $hints)
        {
            return $hints['connection']->quote($input->format('Y-m-d H:i:s'));
        }
    }

SqlField Conversion
~~~~~~~~~~~~~~~~~~~

When the value in the database is not in the desired format
it can be converted to a more workable version.

In this example we want to get the age of a person in years from their date of birth.
In short we convert an ``datetime`` database value to an actual age in year.

.. tip::

    The Core extension already provides a ``birthday`` type which accepts both a date
    and age.

Because PostgreSQL has to most simple to use support we will use it for this example.
Supporting this feature in PostgreSQL is very easy but will be more difficult
for other database servers.

.. code-block:: php
    :linenos:

    namespace Acme\User\Search\Dbal\Conversion;

    use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;

    class AgeConversion implements SqlFieldConversionInterface
    {
        public function convertSqlField($column, array $options, array $hints)
        {
            if ('pdo_pgsql' === $hints['connection']->getDriver()->getName()) {
                return "TO_CHAR('YYYY', AGE($column))";
            } else {
                // Return unconverted
                return $fieldName;
            }
        }
    }

SqlValue Conversion
~~~~~~~~~~~~~~~~~~~

Sometimes its required to do some special conversions with the value,
which can only done using actual SQL.

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

And the SqlValue Conversion class:

.. code-block:: php
    :linenos:

    namespace Acme\Geo\Search\Dbal\Conversion;

    use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
    use Acme\Geo\Point;

    class GeoConversion implements SqlValueConversionInterface
    {
        public function valueRequiresEmbedding($input, array $options, array $hints)
        {
            // The value is used as-is so it must be embedded for usage
            return false;
        }

        public function convertSqlValue($input, array $options, array $hints)
        {
            if ($value instanceof Point) {
                $value = sprintf('POINT(%F %F)', $input->getLongitude(), $input->getLatitude());
            }

            return $value;
        }
    }
