WhereBuilder
============

WhereBuilder searches in an SQL relational database like PostgreSQL, MySQL, SQLite and Oracle
using an SQL WHERE case. Of course you can also use DQL.

For this component to work `Doctrine ORM <http://symfony.com/doc/current/book/doctrine.html>`_
must be installed en properly configured.

.. note ::

    The returned result does not include the actual ``WHERE`` starting part.

Using the WhereBuilder is pretty simple.

.. code-block:: php

    /* ... */

    $formatter = $container->get('rollerworks_record_filter.formatter');
    if (!$formatter->formatInput($input)) {
        /* ... */
    }

    $whereBuilder = $container->get('rollerworks_record_filter.doctrine.sql.where_builder');
    $sqlWhereCase = $whereBuilder->getWhereClause($fieldSet, $formatter);

    // Then use the $sqlWhereCase value in your query.

When selecting from multiple tables you must specify the alias to class relation.

.. code-block:: php

    /* ... */

    $sql = 'SELECT u.username, u.id, u.email, g. FROM users as u, user_groups as g WHERE g.id = u.group AND ';

    $entityAliases = array(
        'u' => 'MyProject\Model\User'
        'g' => 'MyProject\Model\Group'
    );

    $sql .= $whereBuilder->getWhereClause($fieldSet, $formatter, $entityAliases);

.. tip ::

    When using the SqlWhereBuilder factory,
    the getWhereClause() does not need the FieldSet parameter.

.. code-block:: php

    $whereBuilder = $container->get('rollerworks_record_filter.doctrine.sql.wherebuilder_factory')->getWhereBuilder($fieldSet);
    $sqlWhereCase = $whereBuilder->getWhereClause($formatter);

Conversion
----------

In most times you can just use the Record\Sql component without any special configuration.
But there can be cases when you need to do some special things,
like *converting* the input or field value. In this chapter we will get to that.

.. caution ::

    **When using DQL**:
    As most conversions use database functions that are not common amongst vendors
    these must be registered as custom functions in Doctrine.
    http://symfony.com/doc/current/cookbook/doctrine/custom_dql_functions.html

    If you don't want go trough this kind of 'trouble' its better get the SQL
    of the DQL query and append the filtering-SQL to the query, when possible.

.. note ::

    Its only possible to register one converter per field per type,
    so you can both have one a field and value converter.
    But not two value or field converters.

Field Conversion
~~~~~~~~~~~~~~~~

When the value in the database is not in the desired format
we can be converted to something that does work.

For example: we want get the 'age' in years of some person.

Normally we don't really store the age but the date of birth,
so we need to convert the date to an actual age.

PostgreSQL supports getting the age of an date by using the age() database function,
unless we (also) need to use a database that does not support this directly,
this is very simple.

.. note ::

    For calculating the age by date (other then PostgreSQL or MySQL)
    please resort to the documentation of your Database vendor.

First we must make a Converter class for handling this.

.. note ::

    This example does not work for DQL, as age() must be registered as custom function.

.. code-block:: php

    namespace Acme\RecordFilter\Converter\Field;

    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\Types\Type as DBALType;
    use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Sql\SqlFieldConversionInterface;

    class AgeConverter implements SqlFieldConversionInterface
    {
        public function convertField($fieldName, DBALType $type, Connection $connection, $isDql)
        {
            if ('pdo_pgsql' === $connection->getDriver()->getName()) {
                return "to_char('YYYY', age($fieldName))";
            } elseif ('pdo_mysql' === $connection->getDriver()->getName()) {
                // Thanks to Kirill Novitchenko. Also handles the difference with leap years
                return "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($fieldName, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($fieldName, '00-%m-%d'))";
            } else {
                // Return unconverted
                return $fieldName;
            }
        }
    }

Then we configure our converter at WhereBuilder.

.. code-block:: php

    $whereBuilder = /* ... */;
    $whereBuilder->setConversionForField('user_age', new AgeConverter());

Value Conversion
~~~~~~~~~~~~~~~~

The value conversion is similar to Field conversion
but works on the user-input instead of the database value
and must also be registered in the service container.

.. caution ::

    When the value is none-scalar, converting the value is required.
    The system will throw an exception if the final value is not scalar.

In this example we will convert an DateTime object to an scalar value.

.. note::

    Doctrine can already handle an DateTime object,
    so normally you don't have to convert this.

.. code-block:: php

    namespace Acme\RecordFilter\Converter\Value;

    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\Types\Type as DBALType;
    use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Sql\SqlFieldConversionInterface;

    class DateTimeConvertor implements SqlValueConversionInterface
    {
        public function requiresBaseConversion()
        {
            // We don't want the Doctrine type to pre-convert the value for us.
            return false;
        }

        public function convertValue($input, DBALType $type, Connection $connection, $isDql)
        {
            return $connection->quote($input->format('Y-m-d H:i:s'));
        }
    }

Now we need to register our converter in the service container.

.. configuration-block::

    .. code-block:: yaml

        services:
            acme_invoice.record_filter.datetime_value_converter:
                class: Acme\RecordFilter\Converter\Value\DateTimeConvertor

    .. code-block:: xml

        <service id="acme_invoice.record_filter.datetime_value_converter"
            class="Acme\RecordFilter\Converter\Value\DateTimeConvertor" />

    .. code-block:: php

        $container->setDefinition(
            'acme_invoice.record_filter.datetime_value_converter',
            new Definition('Acme\RecordFilter\Converter\Value\DateTimeConvertor')
        );

Then when we want to use the converter for our filtering field
we refer to it by using the RecordFilter\SqlConversion annotation and service name.

.. code-block:: php-annotations

    /**
     * @ORM\Column(type="datetime")
     *
     * @RecordFilter\Field("invoice_date", type="date")
     * @RecordFilter\SqlConversion("acme_invoice.record_filter.datetime_value_converter")
     */
    public $pubdate;
