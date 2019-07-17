How to Use Value Comparators
============================

.. caution::

    This page is not updated for Rollerworks v2.0 yet.

A powerful feature of RollerworksSearch is the ability to optimize
search conditions and perform basic validation of user input.

But in order to do this the system needs to understand which values
are equal or lower/higher to other values. Especially when you are
working with objects.

.. note::

    Fields with range support enabled must have a Value Comparator
    in order to work properly. A missing Comparator will mark
    every range in the field invalid!

Assuming you have a field that handles invoice numbers as an InvoiceNumber
and you configured that the field supports ranges.

.. include:: invoice_number.rst.inc

.. tip::

    See :doc:`data_transformers` on how to transform a user input to
    an ``InvoiceNumber``.

Creating the Comparator
-----------------------

Create an ``InvoiceNumberComparator`` class - this class will be responsible
for comparing values for equality and lower/higher ``InvoiceNumber`` objects::

    // src/Acme/Invoice/Search/ValueComparator/InvoiceNumberComparator.php

    namespace Acme\Invoice\Search\ValueComparator;

    use Acme\Invoice\InvoiceNumber;
    use Rollerworks\Component\Search\ValueComparator;

    final class InvoiceNumberComparator implements ValueComparator
    {
        public function isHigher($higher, $lower, array $options): bool
        {
            return $higher->isHigher($lower);
        }

        public function isLower($lower, $higher, array $options): bool
        {
            return $lower->isLower($higher);
        }

        public function isEqual($value, $nextValue, array $options): bool
        {
            return $value->equals($nextValue);
        }
    }

.. tip::

    A comparison method will only receive values that are returned by
    the field's data transformer.

    You don't have to check if the input is what you expect, but
    if the input is invalid you need look at the configured data transformers.

.. note::

    When ``isLower()`` and ``isHigher()`` are not supported, then both
    methods should be return ``false``.

Using the Comparator
--------------------

Now that you have the Comparator built, you need to add it to your
invoice field type::

    // src/Acme/Invoice/Search/Type/InvoiceNumberType.php

    namespace Acme\Invoice\Search\Type;

    use Acme\Invoice\Search\DataTransformer\InvoiceNumberTransformer;
    use Rollerworks\Component\Search\AbstractFieldType;
    use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
    use Rollerworks\Component\Search\FieldConfigInterface;
    use Rollerworks\Component\Search\Value\{Compare, Range};

    final class InvoiceNumberType extends AbstractFieldType
    {
        private $valueComparator;

        public function __construct()
        {
            $this->valueComparator = new InvoiceNumberComparator();
        }

        public function buildType(FieldConfigInterface $config, array $options)
        {
            $config->setValueComparator($this->valueComparator);
            $config->setValueTypeSupport(Compare::class, true);
            $config->setValueTypeSupport(Range::class, true);

            $config->addViewTransformer(new InvoiceNumberTransformer());
        }
    }

Cool, you're done! Input processors can now validate the bounds of ranges
and optimizers can optimize the generated search condition.

Optimizing incremented values
-----------------------------

Now that your type supports comparing values, you can extend the Comparator with
the ability to calculate increments.

Calculating increments helps with optimizing single incremented values.
For example: ``1, 2, 3, 4, 5`` can be converted to a ``1 ~ 5`` range which will
simplify the search condition and speed-up the search operation.

.. note::

    Optimizing incremented values is done by the
    :class:``Rollerworks\\Component\\Search\\ConditionOptimizer\\ValuesToRange``
    optimizer. So make sure its enabled.

Instead of implementing the ``ValueComparator`` interface, you implement the
``ValueIncrementer`` interface (which extends the ``ValueComparator`` interface)
and add the ``getIncrementedValue`` method for calculating increments::

    // src/Acme/Invoice/Search/ValueComparison/InvoiceNumberComparison.php

    namespace Acme\Invoice\Search\ValueComparison;

    use Acme\Invoice\InvoiceNumber;
    use Rollerworks\Component\Search\ValueIncrementerInterface;

    class InvoiceNumberComparison implements ValueIncrementerInterface
    {
        public function isHigher($higher, $lower, array $options): bool
        {
            return $higher->isHigher($lower);
        }

        public function isLower($lower, $higher, array $options): bool
        {
            return $lower->isLower($higher);
        }

        public function isEqual($value, $nextValue, array $options): bool
        {
            return $value->equals($nextValue);
        }

        public function getIncrementedValue($value, array $options, int $increments = 1)
        {
            return new InvoiceNumber($value->getYear(), $value->getNumber() + $increments);
        }
    }

.. note::

    Technically it's possible to optimize ``2015-099, 2015-100, 2015-000"``
    to ``2015-099 ~ 2015-0001``, but only when we know if "2015-100" is the last
    invoice for the year 2015.

Cool, you're done! The new ``InvoiceNumberComparison`` can be set as your
field's ValueComparator.
