.. index::
   single: Field; Data transformers

How to Use Data Transformers
============================

You'll often find the need to transform the data the user entered in a field into
something else for use in your search processor. You could do this in the processor
but then you will loose some power full features (including removing duplicates
and validating ranges).

Say you have an invoice number that is provided in a special format
like '2015-134', the first part is the year and second a number relative
to the year. 2015-134 means number 134 of the year 2015.

Passing this value to the system it a bit troubling, because the system
has no idea how to handle this format. It looks like a number but it's not.

It would be better if this value was in an InvoiceNumber value object,
so the system can work with. This is where Data Transformers come into play.

The InvoiceNumber object is known as a normalized value.

.. include:: invoice_number.rst.inc

Creating the Transformer
------------------------

Create an ``InvoiceNumberTransformer`` class - this class will be responsible
for converting to and from the invoice number and the ``InvoiceNumber`` object:

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Search/DataTransformer/InvoiceNumberTransformer.php

    namespace Acme\Invoice\Search\DataTransformer;

    use Acme\Invoice\InvoiceNumber;
    use Rollerworks\Component\Search\DataTransformerInterface;
    use Rollerworks\Component\Search\Exception\TransformationFailedException;
    use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

    class InvoiceNumberTransformer implements DataTransformerInterface
    {
        public function transform($value)
        {
            if (!$value instanceof InvoiceNumber) {
                throw new UnexpectedTypeException($value, 'Acme\Invoice\InvoiceNumber');
            }

            return (string) $value;
        }

        public function reverseTransform($value)
        {
            if (null === $value) {
                return null;
            }

            try {
                return InvoiceNumber::createFromString($value);
            } catch (\Exception $e) {
                throw new TransformationFailedException('This not a valid invoice number.')
            }
        }
    }

.. tip::

    If the transformer is unable to transform the input to an ``InvoiceNumber``
    a ``TransformationFailedException`` is thrown to indicate an invalid value.

    The error message that is shown to user can be controlled with the
    ``invalid_message`` field option.

.. note::

    When ``null`` is passed to the ``transform()`` method, your transformer
    should return an equivalent value of the type it is transforming to (e.g.
    an empty string, 0 for integers or 0.0 for floats).

Using the Transformer
---------------------

Now that you have the transformer built, you just need to add it to your
invoice field type.

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Search/Type/InvoiceNumberType.php

    namespace Acme\Invoice\Search\Type;

    use Acme\Invoice\Search\DataTransformer\InvoiceNumberTransformer;
    use Rollerworks\Component\Search\AbstractFieldType;
    use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
    use Rollerworks\Component\Search\FieldConfigInterface;

    class InvoiceNumberType extends AbstractFieldType
    {
        public function buildType(FieldConfigInterface $config, array $options)
        {
            $config->addViewTransformer(new InvoiceNumberTransformer());
        }

        public function getName()
        {
            return 'invoice_number';
        }
    }

Cool, you're done! Your user will be able to enter an invoice number into the
field and it will be transformed back into an InvoiceNumber object. This means
after a successful transformation, the system will pass an ``InvoiceNumber``
object instead of a string value.

And when exporting the value the original format is shown.

So why Use the Data Transformer?
--------------------------------

Transforming user input into a normalized value is done for multiple reasons.

* It makes using the value in the processor easier.
* It allows for localized input (like numbers on a none-latin charset).
* And most of all it makes it possible to optimize the search condition.

One very good example for usage is :doc:`Value Comparisons <value_comparison>`.
