Type
====

The RecordFilter is more just then just a simple search engine.

As you probably know, searching with the system works by defining filtering conditions.
But did you know that each field can have a special filtering type for working with values?

Using an special type for the field allows validation/sanitizing and matching.
Basic types like Date/Time and numbers are built-in, but you can also build your own types.

.. note::

    All built-in types are locale aware and require the Symfony Locale component.
    When working with non-ASCII characters, the International Extension must be installed.

Secondly, all built-in types support comparison and optimizing when possible.

It is possible, but not recommended, to overwrite the built-in types by using
the same alias in the service definition.
You should only consider doing this when absolutely necessary.

see ``Resources/config/services.xml`` for the corresponding names.

Configuration
-------------

Types implementing ``ConfigurableTypeInterface`` can be configured with extra options
using the ``setOptions()`` method of the type.

When building the ``FieldSet``.

.. code-block:: php

    /* ... */

    use Rollerworks\Bundle\RecordFilterBundle\Type\Date;

    $fieldSet->set(new FilterField('name', new Date(array('max' => '2015-10-14'))));

Or when changing an existing Field.

.. code-block:: php

    /* ... */

    $fieldSet->get('field_name')->getType()->setOptions(array('max' => '2015-10-14'));

.. note::

    Just because a type supports range or comparison does not automatically
    enable it for the field configuration. You must always do this **explicitly**.

Text
----

Handles text values as-is. This type can be seen as an 'abstract' for more strict handling.

DateTime
--------

DateTime related types can be used for working with either date/time
or a combination of both. Both support ranges and comparison.

The following options can be set for Date, DateTime and Time.

+-------------------+--------------------------------------------------------------------+-------------------------------+
| Option            | Description                                                        | Accepted values               |
+===================+====================================================================+===============================+
| min               | Minimum value. Must be lower than max (default is ``null``)        | ``DateTime`` object, ``null`` |
+-------------------+--------------------------------------------------------------------+-------------------------------+
| max               | Maximum value. Must be higher than min (default is ``null``)       | ``DateTime`` object, ``null`` |
+-------------------+--------------------------------------------------------------------+-------------------------------+
| time_optional     | If the time is optional (``DateTime`` type only)                   | ``boolean``                   |
+-------------------+--------------------------------------------------------------------+-------------------------------+

Birthday
--------

The Birthday type can be used for birthday and actual age.

Any date equal or lower then 'today' is accepted, but you can also use someones age.

.. note::

    For this to work correctly, the storage layer must convert a date value to an age for comparison.

    For Doctrine ORM you can use the "rollerworks_record_filter.doctrine.orm.conversion.age_date" service
    for both field and value conversion.

    See also :doc:`Doctrine ORM WhereBuilder </Doctrine/orm/index>`.

Number
------

Handles localized numeric values.

Supports ranges and comparison.

.. note::

    When working with big numbers (beyond the maximum php integer value),
    either `bcmath <http://php.net/manual/en/book.bc.php>`_ or `GMP <http://php.net/manual/en/book.gmp.php>`_ must be installed and the option value **must** be a string.

The following options can be set for number.

+-------------------+-----------------------------------------------------------------+-----------------------------------+
| Option            | Description                                                     | Accepted values                   |
+===================+=================================================================+===================================+
| min               | Minimum value. Must be lower than max (default is ``NULL``)     | ``string``, ``integer``, ``NULL`` |
+-------------------+-----------------------------------------------------------------+-----------------------------------+
| max               | Maximum value. Must be higher than min (default is ``NULL``)    | ``string``, ``integer``, ``NULL`` |
+-------------------+-----------------------------------------------------------------+-----------------------------------+

Decimal
-------

Handles (localized) decimal values.

Supports ranges and comparison.

.. note::

    When working with big numbers (beyond the maximum php integer value),
    either `bcmath <http://php.net/manual/en/book.bc.php>`_ or `GMP <http://php.net/manual/en/book.gmp.php>`_ must be installed and the option value **must** be a string.

The following options can be set.

+-------------------+-----------------------------------------------------------------+---------------------------------+
| Option            | Description                                                     | Accepted values                 |
+===================+=================================================================+=================================+
| min               | Minimum value. Must be lower than max (default is ``NULL``)     | ``string``, ``float``, ``NULL`` |
+-------------------+-----------------------------------------------------------------+---------------------------------+
| max               | Maximum value. Must be higher than min (default is ``NULL``)    | ``string``, ``float``, ``NULL`` |
+-------------------+-----------------------------------------------------------------+---------------------------------+

EnumType
--------

EnumType is similar to ENUM in SQL; it only allows a fixed set of possible
values (labels) to be used. The label is then converted back to the internal value.

For this to work, you must register a new service with the options and value.

The first parameter of the ``EnumType`` constructor is an associative array as `value => label`, optionally
followed by the `translator` service and the translator domain.

.. note::

    You can use any service name you like. For readability
    it is best to prefix it with a vendor and domain.

.. configuration-block::

    .. code-block:: yaml

        services:
            acme_invoice.record_filter.filter_type.customer_gender:
                class: %rollerworks_record_filter.filter_type.enum.class%
                scope: prototype
                arguments:
                    - @translator
                    -
                        - gender_type.unknown
                        - gender_type.female
                        - gender_type.male
                    - customer
                tags:
                    - { name: rollerworks_record_filter.filter_type, alias: person_gender }

    .. code-block:: xml

        <service id="acme_invoice.record_filter.filter_type.customer_gender" class="%rollerworks_record_filter.filter_type.enum.class%" scope="prototype">
            <argument type="collection">
                <argument key="0"></argument>
                <argument key="1">gender_type.female</argument>
                <argument key="2">gender_type.male</argument>
            </argument>
            <argument type="service" id="translator" />
            <argument type="string">customer</argument>

            <tag name="rollerworks_record_filter.filter_type" alias="person_gender" />
        </service>

    .. code-block:: php

        use Symfony\Component\DependencyInjection\Definition;

        // ...

        $container->setDefinition(
            'acme_invoice.record_filter.filter_type.customer_gender',
            new Definition('%rollerworks_record_filter.filter_type.enum.class%',
                array(
                    array('gender_type.unknown', 'gender_type.female', 'gender_type.male'),
                    new Reference('translator'),
                    'customer'
                )
            )
            ->addTag('kernel.cache_warmer', array('priority' => 0))
        );

Making your own
---------------

You may find that the build-in types do not meet your needs and you want use your own.
Luckily, making your own type is very easy. The following sections explain the different options
available to achieve this.

Extending
~~~~~~~~~

If you need a type that is only slightly different from the built-in ones,
you can save yourself some work by extending an existing one.

For example: you want to handle client numbers that are like `C30320`.

Using the ``Number`` type and overwriting the ``validateValue()`` and ``sanitizeString()``
is all you need to do.

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

        public function validateValue($value, MessageBag $messageBag)
        {
            $value = ltrim($value, 'Cc');

            parent::validateValue($value, $messageBag);
        }
    }

.. note::

    Not all types may use strings. ``DateTime`` types use an extended
    ``\DateTime`` class for passing information between methods.

From Scratch
~~~~~~~~~~~~

For this little tutorial we are going to create an ``InvoiceType`` that can handle an invoice value.

The value is made up from a year and incrementing number, like 2012-0259.

As we really want to use the power of the ``RecordFilter``, we are also adding
support for ranges and comparisons.

First we create the value class for holding the information of our invoice.

.. code-block:: php
    :linenos:

    namespace Acme\Invoice;

    class InvoiceValue
    {
        private $year;
        private $number;

        public function __construct($input)
        {
            if (!preg_match('/^(?P<year>\d{4})-(?P<number>\d+)$/s', $input, $matches)) {
                throw new \InvalidArgumentException('This not a valid invoice value.');
            }

            $this->year = (int) $matches['year'];
            $this->number = (int) ltrim($matches['number'], '0');
        }

        public function getYear()
        {
            return $this->year;
        }

        public function getNumber()
        {
            return $this->number;
        }

        public function __toString()
        {
            // Return the invoice number with leading zero
            return sprintf('%d-%04d', $this->year, $this->number);
        }
    }

Now we can create our filtering type.

.. note::

    If you want to know more about the interfaces used by the type, see below.

.. code-block:: php
    :linenos:

    namespace Acme\Invoice\RecordFilter\Type;

    use Symfony\Component\Translation\TranslatorInterface;
    use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;
    use Rollerworks\Bundle\RecordFilterBundle\Type\ValueMatcherInterface;
    use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
    use Acme\Invoice\InvoiceValue;

    class InvoiceType implements FilterTypeInterface, ValueMatcherInterface, ValuesToRangeInterface
    {
        public function sanitizeString($value)
        {
            return new InvoiceValue($value);
        }

        public function formatOutput($value)
        {
            return (string) $value;
        }

        public function dumpValue($value)
        {
            return (string) $value;
        }

        public function isHigher($input, $nextValue)
        {
            if ($input->getYear() > $nextValue->getYear()) {
                return true;
            }

            if ($input->getYear() === $nextValue->getYear() && $input->getNumber() > $nextValue->getNumber()) {
                return true;
            }

            return false;
        }

        public function isLower($input, $nextValue)
        {
            if ($input->getYear() < $nextValue->getYear()) {
                return true;
            }

            if ($input->getYear() === $nextValue->getYear() && $input->getNumber() < $nextValue->getNumber()) {
                return true;
            }

            return false;
        }

        public function isEqual($input, $nextValue)
        {
            return ($input->getYear() === $nextValue->getYear() && $input->getNumber() === $nextValue->getNumber());
        }

        public function validateValue($value, MessageBag $messageBag)
        {
            if (!preg_match('/^(\d{4})-(\d+)$/s', $value)) {
                $messageBag->addError('This is not an legal invoice number.');
            }
        }

        public function getMatcherRegex()
        {
            return '(?:\d{4}-\d+)';
        }

        public function getHigherValue($value)
        {
            return new InvoiceValue($value->getYear() . '-' . ($value->getNumber()+1));
        }
    }

Registering a Type as a Service
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to use the new type in the Class metadata or ``FieldSet`` configuration
of the application the type must be registered in the service container.

Continuing from our ``InvoiceType``.

.. note::

    The service must be tagged as "rollerworks_record_filter.filter_type",
    with an alias that will identify it.

.. configuration-block::

    .. code-block:: yaml

        services:
            acme_invoice.record_filter.invoice_type:
                class: Acme\Invoice\RecordFilter\Type\InvoiceType
                tags:
                    -  { name: rollerworks_record_filter.invoice_type, alias: acme_invoice_type }

    .. code-block:: xml

        <service id="acme_invoice.record_filter.invoice_type" class="Acme\Invoice\RecordFilter\Type\InvoiceType">
            <tag name="rollerworks_record_filter.filter_type" alias="acme_invoice_type" />
        </service>

    .. code-block:: php

        $container->setDefinition(
            'acme_invoice.record_filter.invoice_type',
            new Definition('Acme\Invoice\RecordFilter\Type\InvoiceType'))
        )
        ->addTag('rollerworks_record_filter.filter_type', array('alias' => 'acme_invoice_type'));

Advanced types
--------------

A type can be *extended* with extra functionality for more advanced optimization and/or handling.

Look at the built-in types for help implementing them.

.. note::

    You must always implement ``Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface``.

    The other interfaces are optional.

ValueMatcherInterface
~~~~~~~~~~~~~~~~~~~~~

Implement the ``Rollerworks\Bundle\RecordFilterBundle\Type\ValueMatcherInterface``
to provide an regex-based matcher for the value.

This is only used for ``FilterQuery``. It is not necessary to use quotes when the value
contains a dash or comma.

ConfigurableTypeInterface
~~~~~~~~~~~~~~~~~~~~~~~~~

Implement the ``Rollerworks\Bundle\RecordFilterBundle\Type\ConfigurableTypeInterface``
when the type supports dynamic configuration for an example an maximum value or such.

.. note::

    The constructor (for ease of use) should also accept setting options.

This uses the Symfony ``OptionsResolver`` component.

OptimizableInterface
~~~~~~~~~~~~~~~~~~~~

Implement the ``Rollerworks\Bundle\RecordFilterBundle\Formatter\OptimizableInterface``
if the values can be further optimized.

Optimizing includes removing redundant values and changing the filtering strategy.

An example of this is when you have an 'Status' type which only accepts 'active', 'not-active' and 'remove'.
If **all** the possible values are chosen, the values are redundant and the filter should be removed.

ValuesToRangeInterface
~~~~~~~~~~~~~~~~~~~~~~

Implement the ``Rollerworks\Bundle\RecordFilterBundle\Formatter\ValuesToRangeInterface``
to convert a connected-list of values to ranges.

Connected values are values where the current value increased by one equals the next value.

1,2,3,4,5,8,10 is converted to 1-5,8,10
