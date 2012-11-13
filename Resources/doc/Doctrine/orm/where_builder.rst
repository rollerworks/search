WhereBuilder
============

WhereBuilder searches in an SQL relational database like PostgreSQL, MySQL, SQLite
and Oracle database using a WHERE case, the WHERE case can be either SQL or DQL.

Both NativeSql and the Doctrine Query Language (DQL) are supported.

Using the WhereBuilder is very simple.

Every filtering preference must be provided by the formatter,
see :doc:`getting started` for more information.

.. code-block:: php

    /* ... */

    $formatter = $container->get('rollerworks_record_filter.formatter');
    if (!$formatter->formatInput($input)) {
        /* ... */
    }

    // The "rollerworks_record_filter.doctrine.orm.where_builder" service always returns a new instance
    // So any changes you make only apply to this instance
    $whereBuilder = $container->get('rollerworks_record_filter.doctrine.orm.where_builder');
    $wereCase = $whereBuilder->getWhereClause($formatter);

    // Now you can use the $whereCase value in your query
    // But don't for get to include the WHERE part!

When selecting from multiple tables or using DQL you must specify the class relation to alias mapping.

.. caution::

    Searching with joined entities might cause duplicate results.
    Use DISTINCT on the unique id of the 'parent' table to remove duplicates.

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

    You can also use the short AcmeUserBundle:User notation.

By default values are embedded in the query (except for DQL), if you want the values
to be provided as parameters you must provide an ORM Query object as third parameter.

.. tip::

    You can let the WhereBuilder update your query object using the 4th parameter.

    Only when there is an actual filtering the value of the 4th parameter is
    placed before your search. If there is no filtering the query is untouched
    and can be executed as it is.

    .. code-block:: php

        /* ... */

        $em = $this->getDoctrine()->getManager();
        $query = $em->createNativeQuery('SELECT u.username username, u.id uid, u.email email, g.id group_id FROM users u, user_groups g WHERE g.id = u.group');

        $whereBuilder->getWhereClause($formatter, $entityAliases, $query, " AND ");

The parameters are set on the Query object as "field_name_x" (x is an incrementing number).

.. code-block:: php

    /* ... */

    $wereCase = $whereBuilder->getWhereClause($formatter, array(), $query);

.. caution::

    Calling getWhereClause() will reset the parameter incrementation counter.
    To preserve the old value set the 5th parameter to false.

Caching
~~~~~~~

To cache the generated result for the next page load,
you can use CacheWhereBuilder. If the query-result is in the cache its used,
else the Where case is generated and cached.

.. caution::

    Caching is not possible if the conversions are not static, if they depend
    on something that varies per page request, caching should not be used.

.. warning::

    Any changes to the metadata or Entity mapping are **not automatically** detected.
    Always use a Cache Driver that can be easily invalidated, like a PHP session.

.. code-block:: php

    /* ... */

    $cacheFormatter = $container->get('rollerworks_record_filter.cache_formatter');
    if (!$cacheFormatter->formatInput($input)) {
        /* ... */
    }

    $whereBuilder = $container->get('rollerworks_record_filter.doctrine.orm.where_builder');
    $wereCase = $container->get('rollerworks_record_filter.doctrine.orm.cache_where_builder')
        ->getWhereClause($cacheFormatter, $whereBuilder, /* $entityAliases, $query, $appendQuery, $resetParameterIndex */);

.. note::

    Conversions can only be set on the $whereBuilder, not the CacheWhereBuilder.

Doctrine Query Language
~~~~~~~~~~~~~~~~~~~~~~~

If you want to use the Doctrine Query Language instead of NativeSql,
the procedure is slightly different.

You **must** set the Alias mapping and provide an ORM Query object.

.. code-block:: php

    /* ... */

    $em = $this->getDoctrine()->getManager();
    $query = $em->createQuery("SELECT u, g FROM MyProject\Model\User u, MyProject\Model\Group g WHERE g.id = u.group AND ");

    $entityAliases = array(
        'AcmeUserBundle\Entity\User' => 'u'
        'AcmeUserBundle\Entity\Group' => 'g'
    );

    $wereCase = $whereBuilder->getWhereClause($formatter, $entityAliases, $query);

Factory
~~~~~~~

Your where cases are generated using the provided FieldSet's.

As most of your FieldSets will be known at forehand, you can save some processing time
by placing them in your config file instead of in your application code.

After this you can start using the WhereBuilder factory which will
create the structure for your where cases and reduce generation time.

.. note::

    You don't have to place your FieldSets in the config file.
    But doing so will make the system create the WhereBuilder
    classes during cache warming.

    Generating WhereBuilder classes based on 'dynamic' FieldSets
    is possible, but not recommended.

Replacing the WhereBuilder with the factory version is very straightforward.

You only need to replace the "rollerworks_record_filter.doctrine.orm.where_builder" with the
"rollerworks_record_filter.doctrine.orm.wherebuilder_factory" service and call
getWhereBuilder() with the FieldSet - which you can get from the Formatter.

.. caution::

    You can only use the FieldSet that was used for generating,
    using anything else will throw an exception.

.. code-block:: php

    /* ... */

    $whereBuilder = $container->get('rollerworks_record_filter.doctrine.orm.wherebuilder_factory')->getWhereBuilder($formatter->getFieldSet());
    $whereCase = $whereBuilder->getWhereClause($formatter);

Conversion
----------

In most cases you can just use the Doctrine\Orm component without any special configuration.

But there are cases when you need to perform some special things,
like *converting* the input or database value. In this chapter we will get to that.

You also add field and value conversion to the same class.

.. note::

    When using conversions with DQL, the custom DQL functions must be configured as
    described in the configuration of this section.

.. note::

    Its only possible to register *one* converter per *field* per *type*,
    you can both apply one field and value converter.

    But not two value/field converters.

If you want to use the class Metadata for conversions,
you need to add the conversion service to your config file.

.. note::

    Parameter names can not start with '__',
    '__' is reserved for internal configuration and is set by the system.

You can use any service name you like, but for readability
its best to prefix it with a vendor and domain.

.. configuration-block::

    .. code-block:: yaml

        services:
            acme_invoice.record_filter.orm.converter_name:
                class: Acme\RecordFilter\Orm\Converter\ClassName

    .. code-block:: xml

        <service id="acme_invoice.record_filter.orm.converter_name"
            class="Acme\RecordFilter\Orm\Converter\ClassName" />

    .. code-block:: php

        use Symfony\Component\DependencyInjection\Definition;

        // ...
        $container->setDefinition(
            'acme_invoice.record_filter.orm.converter_name',
            new Definition('Acme\RecordFilter\Orm\Converter\ClassName')
        );

The first value of the annotation is the service name,
other parameters are passed to $parameters of the conversion method.

.. note::

    Conversions that are set using metadata can overwritten by calling
    setFieldConversion() and setValueConversion() respectively.

    You can disable conversions be giving null instead of an object.

Field Conversion
~~~~~~~~~~~~~~~~

When the value in the database is not in the desired format
it can be converted to a more workable version.

For example: you want to get the 'age' in years of some person.

.. tip::

    The bundle has a "build-in" type for birthday conversion.

    We can use the "rollerworks_record_filter.doctrine.orm.conversion.birthday"
    service for handling age and birthday.

    If the input is a date its used as-is, else the database value is converted to an age.

Normally you wouldn't really store someones age but there date of birth,
so we need to convert the date to an age.

PostgreSQL supports getting the age of an date by using the age() database function,
unless you (also) need to support a database that does not support this directly,
this is very simple.

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
            } else {
                // Return unconverted
                return $fieldName;
            }
        }
    }

Then we add the converter to the WhereBuilder by.

.. code-block:: php

    /* ... */
    $whereBuilder->setFieldConversion('user_age', new AgeConversion());

Or using the Metadata.

.. configuration-block::

    .. code-block:: php-annotations

        /**
         * @ORM\Column(type="datetime")
         *
         * @RecordFilter\Field("user_age", type="date")
         * @RecordFilter\Doctrine\SqlFieldConversion("acme_invoice.record_filter.orm.datetime_value_conversion")
         */
        public $birthday;

    .. code-block:: yaml

        # src/Acme/StoreBundle/Resources/config/record_filter/Entity.Customer.yml
        birthday:
            name: user_age
            type: date
            doctrine:
                orm:
                    field-conversion: acme_invoice.record_filter.orm.datetime_value_conversion

    .. code-block:: xml

        <!-- src/Acme/StoreBundle/Resources/config/record_filter/Entity.Customer.xml -->
        <properties>
            <!-- ... -->

            <property id="birthday" name="user_age">
                <type name="date" />
                <doctrine>
                    <orm>
                        <conversion>
                            <field service="acme_invoice.record_filter.orm.datetime_value_conversion" />
                        </conversion>
                    </orm>
                </doctrine>

            </property>
        </properties>

Value Conversion
~~~~~~~~~~~~~~~~

Value conversion is similar to Field conversion,
but works on the *user-input* instead of the *database value*.

In this example we will convert an DateTime object to an scalar value.

.. note::

    Doctrine can already handle a DateTime object,
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

Then we add the converter to the WhereBuilder by.

.. code-block:: php

    /* ... */;
    $whereBuilder->setValueConversion('user_age', new AgeConverter());

Or using the Metadata.

.. configuration-block::

    .. code-block:: php-annotations

        /**
         * @ORM\Column(type="datetime")
         *
         * @RecordFilter\Field("user_age", type="date")
         * @RecordFilter\Doctrine\SqlValueConversionInterface("acme_invoice.record_filter.orm.datetime_value_conversion")
         */
        public $birthday;

    .. code-block:: yaml

        # src/Acme/StoreBundle/Resources/config/record_filter/Entity.Customer.yml
        birthday:
            name: user_age
            type: date
            doctrine:
                orm:
                    value-conversion: acme_invoice.record_filter.orm.datetime_value_conversion

    .. code-block:: xml

        <!-- src/Acme/StoreBundle/Resources/config/record_filter/Entity.Customer.xml -->
        <properties>
            <!-- ... -->

            <property id="birthday" name="user_age">
                <type name="date" />
                <doctrine>
                    <orm>
                        <conversion>
                            <value service="acme_invoice.record_filter.orm.datetime_value_conversion" />
                        </conversion>
                    </orm>
                </doctrine>

            </property>
        </properties>
