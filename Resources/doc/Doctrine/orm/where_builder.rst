WhereBuilder
============

WhereBuilder searches in an SQL relational database like PostgreSQL, MySQL, SQLite
and Oracle database using an WHERE case, the WHERE case can be either SQL or DQL.

For this component to work `Doctrine ORM <http://symfony.com/doc/current/book/doctrine.html>`_
must be installed en properly configured.

Both nativeSql and the Doctrine Query Language (or DQL for short) are supported.

.. warning::

    Use at least version 2.2.4 of Doctrine ORM, older versions have a bug
    that makes using field conversion fail.

.. note::

    The returned result does not include the actual ``WHERE`` starting part.

Using the WhereBuilder is pretty simple.

Every filtering preference must be provided by the formatter,
see the :doc:`getting started` chapter for more information.

.. code-block:: php

    /* ... */

    $formatter = $container->get('rollerworks_record_filter.formatter');
    if (!$formatter->formatInput($input)) {
        /* ... */
    }

    // The "rollerworks_record_filter.doctrine.orm.where_builder" service always returns an new instance
    // So any changes we make only apply to this instance
    $whereBuilder = $container->get(rollerworks_record_filter.doctrine.orm.where_builder);
    $wereCase = $whereBuilder->getWhereClause($formatter);

    // Now we can use the $whereCase value in your query, don't for get to include the WHERE part.

When selecting from multiple tables or using DQL we must specify the class relation to alias mapping.

.. caution::

    Searching with joined entities might cause duplicate results.
    Use either GROUP BY or DISTINCT on the unique id of the parent to remove duplicates.

    Duplicate results happen because we ask the database to return all matching
    records, one parent record can have multiple matching children.

.. code-block:: php

    /* ... */

    $sql = 'SELECT u.username username, u.id uid, u.email email, g.id group_id FROM users as u, user_groups as g WHERE g.id = u.group AND ';

    $entityAliases = array(
        'AcmeUserBundle\Entity\User' => 'u'
        'AcmeUserBundle\Entity\Group' => 'g'
    );

    $sql .= $whereBuilder->getWhereClause($formatter, $entityAliases);

.. tip::

    We can also the short AcmeUserBundle:User notation.


By default values are embedded in the query (except for DQL), if we want the values to provided as parameters
we must provide an Doctrine ORM Query object as third parameter.

The parameter are set on the Query object as "field_name_x" (x is an incrementing number).

.. caution::

    Calling getWhereClause() will reset the parameter incrementation counter.
    To preserve the old value set the 5th parameter to false.

.. code-block:: php

    /* ... */

    $wereCase = $whereBuilder->getWhereClause($formatter, array(), $query);

Doctrine Query Language
~~~~~~~~~~~~~~~~~~~~~~~

If we want to use the Doctrine Query Language instead of nativeSql
the procedure is slightly different.

We **must** set the Alias mapping and provide an Doctrine ORM Query object.

.. code-block:: php

    $em = $this->getDoctrine()->getManager();
    $query = $em->createQuery("SELECT u, g FROM MyProject\Model\User u, MyProject\Model\Group g WHERE g.id = u.group AND ");

    $entityAliases = array(
        'AcmeUserBundle\Entity\User' => 'u'
        'AcmeUserBundle\Entity\Group' => 'g'
    );

    $wereCase = $whereBuilder->getWhereClause($formatter, $entityAliases, $query);

.. tip::

    We can let the WhereBuilder update our query using the 4th parameter.

    Only when there is an actual filtering the value of the 4th parameter is
    placed before our search (saving us some coding).
    When there is no filtering the query is untouched an be executed as it is.

    .. code-block:: php

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery("SELECT u, g FROM MyProject\Model\User u, MyProject\Model\Group g WHERE g.id = u.group");

        $entityAliases = array(
            'AcmeUserBundle\Entity\User' => 'u'
            'AcmeUserBundle\Entity\Group' => 'g'
        );

        $whereBuilder->getWhereClause($formatter, $entityAliases, $query, " AND ");

Factory
~~~~~~~

Our where case is generated primarily using the FieldSet we are providing.

As most of our FieldSets will be known at forehand, we can save some processing time
by moving them to our application configuration instead of placing them in our code.

After this we can start using the WhereBuilder factory which will
create the primary structure for our where case and cut back generation time.

.. note::

    We don't have to place our FieldSets in the application configuration.
    But doing so will make the system create the WhereBuilder
    classes during cache warming.

    Generating WhereBuilder classes based on 'dynamic' FieldSets
    is possible but not recommended.

Using WhereBuilder factory is pretty straightforward.

We only have to replace the "rollerworks_record_filter.doctrine.orm.where_builder" with the
"rollerworks_record_filter.doctrine.orm.wherebuilder_factory" service and call
getWhereBuilder() with the FieldSet, which we can get from the Formatter.

.. note::

    we can only use the FieldSet that was used for generating,
    using anything else will throw an exception.

.. code-block:: php

    $whereBuilder = $container->get('rollerworks_record_filter.doctrine.orm.wherebuilder_factory')->getWhereBuilder($formatter->getFieldSet());
    $whereCase = $whereBuilder->getWhereClause($formatter);

Conversion
----------

Most times we can just use the Doctrine\Orm component without any special configuration.

But there are cases when you need to do some special things,
like *converting* the input or field value. In this chapter we will get to that.

We can even mix field and value conversion on the same class.

.. note::

    When using conversions with DQL, the custom functions must be configured as described in:
    :doc:`configuration.rst`#DoctrineOrmWhereBuilder

.. note::

    Its only possible to register one converter per field per type,
    so we can both have one field and value converter.

    But not two value or field converters.

When we want to use the Metadata for conversion,
we need to add the service to our application configuration.

We can use any service name we like,
but for sake of readability we prefix it using an vendor and domain.

.. configuration-block::

    .. code-block:: yaml

        services:
            acme_invoice.record_filter.orm.converter_name:
                class: Acme\RecordFilter\Orm\Converter\ClassName

    .. code-block:: xml

        <service id="acme_invoice.record_filter.orm.converter_name"
            class="Acme\RecordFilter\Orm\Converter\ClassName" />

    .. code-block:: php

        $container->setDefinition(
            'acme_invoice.record_filter.orm.converter_name',
            new Definition('Acme\RecordFilter\Orm\Converter\ClassName')
        );

The first value of the annotation will always the service name,
other parameters are passed to $parameters of the method.

Field Conversion
~~~~~~~~~~~~~~~~

When the value in the database is not in the desired format
it can be converted to something that does work.

For example: we want to get the 'age' in years of some person.

.. tip::

    There is an "build-in" type for birthday.

    We can use the "rollerworks_record_filter.doctrine.orm.conversion.birthday"
    service for handling age and birthday.

    If the input is an date is used as-is,
    else the database value is converted to an age.

Normally we don't really store the age but the date of birth,
so we need to convert the date to an actual age.

PostgreSQL supports getting the age of an date by using the age() database function,
unless we (also) need to use a database that does not support this directly,
this is very simple.

.. note::

    For calculating the age by date (other then PostgreSQL or MySQL)
    please resort to the documentation of your Database vendor.

First we must make a Conversion class for handling this.

.. code-block:: php

    namespace Acme\RecordFilter\Orm\Conversion;

    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\Types\Type as DBALType;
    use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\FieldConversionInterface;

    class AgeFieldConversion implements FieldConversionInterface
    {
        public function convertField($fieldName, DBALType $type, Connection $connection, array $parameters = array())
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

Then we configure our converter.

We either configure it at the WhereBuilder.

.. code-block:: php

    $whereBuilder = /* ... */;
    $whereBuilder->setFieldConversion('user_age', new AgeConversion());

Or using the Entity metadata.

.. code-block:: php-annotations

    /**
     * @ORM\Column(type="datetime")
     *
     * @RecordFilter\Field("user_age", type="date")
     * @RecordFilter\Doctrine\SqlFieldConversion("acme_invoice.record_filter.orm.datetime_value_conversion")
     */
    public $birthday;

Value Conversion
~~~~~~~~~~~~~~~~

The value conversion is similar to Field conversion,
but works on the user-input instead of the *database value*.

In this example we will convert an DateTime object to an scalar value.

.. note::

    Doctrine can already handle an DateTime object,
    so normally we don't have to convert this.

.. code-block:: php

    namespace Acme\RecordFilter\Orm\Conversion;

    use Doctrine\DBAL\Connection;
    use Doctrine\DBAL\Types\Type as DBALType;
    use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\ValueConversionInterface;

    class DateTimeValueConversion implements ValueConversionInterface
    {
        public function requiresBaseConversion()
        {
            // We don't want the Doctrine type to pre-convert the value for us.
            return false;
        }

        public function convertValue($input, DBALType $type, Connection $connection, $isDql, array $parameters = array())
        {
            return $connection->quote($input->format('Y-m-d H:i:s'));
        }
    }

Then we configure our converter.

We either configure it at the WhereBuilder.

.. code-block:: php

    $whereBuilder = /* ... */;
    $whereBuilder->setValueConversion('user_age', new AgeConverter());

Or using the Entity metadata.

.. code-block:: php-annotations

    /**
     * @ORM\Column(type="datetime")
     *
     * @RecordFilter\Field("user_age", type="date")
     * @RecordFilter\Doctrine\ValueConversionInterface("acme_invoice.record_filter.orm.datetime_value_conversion")
     */
    public $birthday;
