Type
====

The RecordFilter is more just then simple an search engine.

As your properly know, searching works by filtering conditions,
configured by fields in sets.

Each field can have a special filtering type for working with values.
Using an special type for the field allows validation/sanitizing and matching.

Basic types like Date/Time and numbers are built-in,
but you can also build your own types.

.. note::

    All built-in types are local aware and require the Symfony Locale component.
    When working with none ASCII charters the International Extension must be installed.

Secondly, all built-in types support comparison optimizing when possible.

Its possible but not recommended to overwrite the build-in types by using
the same alias. You should only considering doing this when absolutely needed.

see ``Resources/config/services.xml`` for there corresponding names.

Configuration
-------------

Types implementing ConfigurableTypeInterface
can be configured with extra options using the setOptions() method of the type.

When building the FieldSet.

.. code-block:: php

    /* ... */

    use Rollerworks\Bundle\RecordFilterBundle\Type\Date;

    $fieldSet->set(new FilterField('name', new Date(array('max' => '2015-10-14'))));

Changing an existing Field.

.. code-block:: php

    /* ... */

    $fieldSet->get('field_name')->getType()->setOptions(array('max' => '2015-10-14'));

.. note::

    Just because an type supports range or comparison does not automatically
    enable it for the field configuration. You must always do this **explicitly**.

Text
----

Handles text values as-is, this type can be seen as 'abstract' for more strict handling.

DateTime
--------

DateTime related types can be used for working with either date/time
or a combination of both. They support ranges and comparison.

The following options can be set for Date, DateTime and Time.

+-------------------+----------------------------------------------------------------+----------------------+
| Option            | Description                                                    | Accepted values      |
+===================+================================================================+======================+
| min               | Minimum value. must be lower then max (default is NULL)        | DateTime object,NULL |
+-------------------+----------------------------------------------------------------+----------------------+
| max               | Maximum value. must be higher then min (default is NULL)       | DateTime object,NULL |
+-------------------+----------------------------------------------------------------+----------------------+
| time_optional     | If the time is optional (DateTime type only)                   | Boolean              |
+-------------------+----------------------------------------------------------------+----------------------+

Birthday
--------

The Birthday type can be used for birthdays and ages.

Any date equal or lower then 'today' is accepted, but you can also use someones age.

.. note::

    For this to work completely, the storage layer must convert a birthday value to an age for comparison.

    For Doctrine ORM you use the "rollerworks_record_filter.doctrine.orm.conversion.age_date" service
    for both field and value conversion.

    See also :doc:`Doctrine ORM WhereBuilder <Doctrine/orm/where_builder>`.

Number
------

Handles numeric values, can be localized.
They support ranges and comparison.

.. note::

    When working with big numbers (beyond maximum php value),
    either bcmath or GMP must be installed and the option values **must** be strings.

The following options can be set for number.

+-------------------+-----------------------------------------------------------------+----------------------+
| Option            | Description                                                     | Accepted values      |
+===================+=================================================================+======================+
| min               | Minimum value. must be lower then max (default is NULL)         | string,integer,NULL  |
+-------------------+-----------------------------------------------------------------+----------------------+
| max               | Maximum value. must be higher then min (default is NULL)        | string,integer,NULL  |
+-------------------+-----------------------------------------------------------------+----------------------+

Decimal
-------

Handles decimal values, can be localized.
They support ranges and comparison.

    When working with big numbers (beyond maximum php value),
    either bcmath or GMP must be installed and the option values **must** be strings.

The following options can be set.

+-------------------+------------------------------------------------------------------+----------------------+
| Option            | Description                                                      | Accepted values      |
+===================+==================================================================+======================+
| min               | Minimum value. must be lower then max (default is NULL)          | string,float,NULL    |
+-------------------+------------------------------------------------------------------+----------------------+
| max               | Maximum value. must be higher then min (default is NULL)         | string,float,NULL    |
+-------------------+------------------------------------------------------------------+----------------------+

EnumType
--------

EnumType is similar to ENUM of SQL, it only allows an fixed set of possible
values (labels) to be used. The label are then converted back to the internal value.

For this to work, TODO.

Making your own
---------------

Often you will find that the build-in types are not enough, and you want use your own.
Luckily making your own type is very ease, in this chapter we will get to that.

Extending
~~~~~~~~~

If you only need an type that is slightly different then the build-in ones,
you can save your self some work, by extending an existing one.

For example: you want to handle client numbers that are prefixed like C30320.

Using the Number type and overwriting the validateValue() and sanitizeString()
should be enough.

.. code-block:: php

    use Rollerworks\Bundle\RecordFilterBundle\Type\Number;
    use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

    class CustomerType extends Number
    {
        public function sanitizeString($value)
        {
            $value = ltrim($value, 'Cc');

            return parent::sanitizeString($value);
        }

        public function validateValue($value, &$message = null, MessageBag $messageBag = null)
        {
            $value = ltrim($value, 'Cc');

            return parent::validateValue($value, $message, $messageBag);
        }
    }

.. note::

    Not all types may use strings, DateTime types use an extended
    \DateTime class for passing information between methods.

From Scratch
~~~~~~~~~~~~

For this little tutorial we are going to create an type that can handle an status flag.

    The status can be localized and converted back to an label,
    and as a little bonus the Value can matched for usage with FilterQuery input.

.. tip::

    This is an old example, it better to use the EnumType instead.

.. code-block:: php

    namespace Acme\Invoice\RecordFilter\Type;

    use Symfony\Component\Translation\TranslatorInterface;
    use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;
    use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
    use Rollerworks\Bundle\RecordFilterBundle\Type\ValueMatcherInterface;

    class InvoiceStatusType implements FilterTypeInterface, ValueMatcherInterface
    {
        private $statusToString = array();
        private $stringToStatus = array();
        private $match;

        public function setTranslator(TranslatorInterface $translator)
        {
            foreach (array('concept', 'unpaid', 'paid') as $status) {
                // Get the label using the translator
                $label = $translator->trans($status, array(), 'invoice');

                $this->stringToStatus[$label] = $status;
                $this->statusToString[$status] = $label;
            }
        }

        public function sanitizeString($value)
        {
            // Normally its better to use mb_strtolower()
            $value = strtolower($value);

            if (isset($this->stringToStatus[$value])) {
                $this->stringToStatus[$value];
            }

            return $value;
        }

        public function formatOutput($value)
        {
            return isset($this->statusToString[$value]) ? $this->statusToString[$value] : $value;
        }

        public function dumpValue($value)
        {
            return $value;
        }

        /**
         * Not used.
         */
        public function isHigher($input, $nextValue)
        {
            return false;
        }

        /**
         * Not used.
         */
        public function isLower($input, $nextValue)
        {
            return true;
        }

        public function isEqual($input, $nextValue)
        {
            return ($input === $nextValue);
        }

        public function validateValue($value, &$message = null, MessageBag $messageBag = null)
        {
            $message = 'This is not an legal invoice status.';

            $value = strtolower($value);

            if (!isset($this->stringToStatus[$value])) {
                return false;
            }

            return true;
        }

        public function getMatcherRegex()
        {
            // This method gets called multiple times so cache the outcome
            if (null === $this->match) {
                $labels = $this->stringToStatus;

                // Escape the label to prevent mistaken regex-match
                array_map(function ($label) { return preg_quote($label, '#'); }, $labels);

                // Match must be an none-capturing group
                $this->match = sprintf('(?:%s)', implode('|', $labels));
            }

            return $this->match;
        }
    }

Registering Type your as a Service
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to use your type in the Class metadata or
FieldSet configuration of the application the type must be
registered in the service container.

Continuing from our InvoiceStatusType.

.. note::

    The service must be tagged as "rollerworks_record_filter.filter_type"
    and have an alias that will identify it.

.. configuration-block::

    .. code-block:: yaml

        services:
            acme_invoice.record_filter.status_type:
                class: Acme\Invoice\RecordFilter\Type\InvoiceStatusType
                calls:
                    - [ setTranslator, [ @translator ] ]
                tags:
                    -  { name: rollerworks_record_filter.filter_type, alias: acme_invoice_type }

    .. code-block:: xml

        <service id="acme_invoice.record_filter.status_type" class="Acme\Invoice\RecordFilter\Type\InvoiceStatusType">
            <!-- Our Type needs the Translator -->
            <call method="setContainer">
                <argument type="service" id="translator"/>
            </call>

            <tag name="rollerworks_record_filter.filter_type" alias="acme_invoice_type" />
        </service>

    .. code-block:: php

        $container->setDefinition(
            'acme_invoice.record_filter.status_type',
            new Definition('Acme\Invoice\RecordFilter\Type\InvoiceStatusType'),
            array(new Reference('translator'))
        )
        ->addMethodCall('setTranslator', array(new Reference('translator')))
        ->addTag('rollerworks_record_filter.filter_type', array('alias' => 'acme_invoice_type'));

Advanced types
--------------

An type can be *extended* with extra functionality for
more advanced optimization and/or handling.

Look at the build-in types if you need help implementing them.

.. note::

    You must always implement ``Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface``.
    The following interfaces are optional.

ValueMatcherInterface
~~~~~~~~~~~~~~~~~~~~~

Implement the ``Rollerworks\Bundle\RecordFilterBundle\Type\ValueMatcherInterface``
to provide an regex-based matcher for the value.

This is only used for FilterQuery, so its not required to 'always'
use quotes when the value contains a dash or comma.

ConfigurableTypeInterface
~~~~~~~~~~~~~~~~~~~~~~~~~

Implement the ``Rollerworks\Bundle\RecordFilterBundle\Type\ConfigurableTypeInterface``
when the type supports dynamic configuration for an example an maximum value or such.

.. note::

    The constructor should accept setting options, for ease of use.

This uses the Symfony OptionsResolver component.

OptimizableInterface
~~~~~~~~~~~~~~~~~~~~

Implement the ``Rollerworks\Bundle\RecordFilterBundle\Formatter\OptimizableInterface``
if the values can be further optimized.

Optimizing includes removing redundant values and changing the filtering strategy.

An example can be, where you have an 'Status' type which only accepts 'active', 'not-active' and 'remove'.
If ***all*** the possible values are chosen, the values are redundant and the filter should be removed.

ValuesToRangeInterface
~~~~~~~~~~~~~~~~~~~~~~

Implement the ``Rollerworks\Bundle\RecordFilterBundle\Formatter\ValuesToRangeInterface``
to converted an connected-list of values to ranges.

Connected values are values where the current value increased by one equals the next value.

1,2,3,4,5,8,10 is converted to 1-5,8,10
