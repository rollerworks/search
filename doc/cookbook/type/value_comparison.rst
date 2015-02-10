How to Use Value Comparisons
============================

A powerful feature of RollerworksSearch is the ability to optimize
search conditions and perform basic validation of user input.

But in order to do this the system needs to understand which values
are equal or lower/higher to other values. Especially when you are
working with objects.

.. note::

    Fields with range support enabled must have a Value Comparer
    in order to work properly. A missing Comparer will mark
    every range in the field invalid!

Assuming you have a field that handles invoice numbers as an InvoiceNumber
and you configured that the field supports ranges.

.. include:: invoice_number.rst.inc

.. tip::

    See :doc:`data_transformers` on how to transform a user input to
    an InvoiceNumber.

Creating the Comparer
---------------------

Create an ``InvoiceNumberComparison`` class - this class will be responsible
for comparing values for equality and lower/higher ``InvoiceNumber`` objects:

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Search/ValueComparison/InvoiceNumberComparison.php

    namespace Acme\Invoice\Search\ValueComparison;

    use Acme\Invoice\InvoiceNumber;
    use Rollerworks\Component\Search\ValueComparisonInterface;

    class InvoiceNumberComparison implements ValueComparisonInterface
    {
        public function isHigher($higher, $lower, array $options)
        {
            return $higher->isHigher($lower);
        }

        public function isLower($lower, $higher, array $options)
        {
            return $lower->isLower($higher);
        }

        public function isEqual($value, $nextValue, array $options)
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

Using the Comparer
------------------

Now that you have the comparer built, you just need to add it to your
invoice field type.

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Search/Type/InvoiceNumberType.php

    namespace Acme\Invoice\Search\Type;

    use Acme\Invoice\Search\DataTransformer\InvoiceNumberTransformer;
    use Rollerworks\Component\Search\AbstractFieldType;
    use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
    use Rollerworks\Component\Search\FieldConfigInterface;
    use Rollerworks\Component\Search\ValueComparisonInterface;

    class InvoiceNumberType extends AbstractFieldType
    {
        private $valueComparison;

        public function __construct(ValueComparisonInterface $valueComparison)
        {
            $this->valueComparison = $valueComparison;
        }

        public function buildType(FieldConfigInterface $config, array $options)
        {
            $config->setValueComparison($this->valueComparison);
            $config->addViewTransformer(new InvoiceNumberTransformer());
        }

        public function hasRangeSupport()
        {
            return true;
        }

        public function hasCompareSupport()
        {
            return true;
        }

        public function getName()
        {
            return 'invoice_number';
        }
    }

Cool, you're done! Input processors can now validate the bounds of ranges
and optimizers can optimize the generated search condition.

Optimizing incremented values
-----------------------------

Now that your type supports comparing values, you can extend the comparer with
the ability to calculate increments.

Calculating increments is helps with optimizing single incremented values.
For example: ``1, 2, 3, 4, 5`` can be converted to a ``1-5`` range which will decrease
the size of the search condition and speed-up the search operation.

.. note::

    Optimizing incremented values is done by the
    :class:``Rollerworks\\Component\\Search\\ConditionOptimizer\\ValuesToRange``
    optimizer. So make sure its enabled.

Instead of implementing the ``ValueComparisonInterface`` implement the
``ValueIncrementerInterface`` which extends the ``ValueComparisonInterface``
and adds the ``getIncrementedValue`` method for calculating increments.

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Search/ValueComparison/InvoiceNumberComparison.php

    namespace Acme\Invoice\Search\ValueComparison;

    use Acme\Invoice\InvoiceNumber;
    use Rollerworks\Component\Search\ValueIncrementerInterface;

    class InvoiceNumberComparison implements ValueIncrementerInterface
    {
        public function isHigher($higher, $lower, array $options)
        {
            return $higher->isHigher($lower);
        }

        public function isLower($lower, $higher, array $options)
        {
            return $lower->isLower($higher);
        }

        public function isEqual($value, $nextValue, array $options)
        {
            return $value->equals($nextValue);
        }

        public function getIncrementedValue($value, array $options, $increments = 1)
        {
            return new InvoiceNumber($value->getYear(), $value->getNumber() + $increments);
        }
    }

.. note::

    Technically it's possible to optimize ``"2015-099", "2015-100", "2015-0001"``
    to ``"2015-099"-"2015-0001"``, but only when we know if "2015-100" is last
    invoice for the year 2015.

Cool, you're done! The new ``InvoiceNumberComparison`` can be registered
as any normal value comparer.
