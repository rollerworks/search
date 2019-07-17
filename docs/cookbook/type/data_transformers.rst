.. index::
   single: Field; Data transformers

How to Use Data Transformers
============================

.. caution::

    This page is not updated for Rollerworks v2.0 yet.

You'll often find the need to transform the data the user entered in a field into
something else for use in your search processor. You could do this in the processor
but then you will loose some powerful features (like removing duplicates or overlapping
values).

Say you have an invoice number that is provided in a special format
like '2015-134', the first part is the year and second a number relative
to the year. 2015-134 means number 134 of the year 2015.

The system has no idea how to handle this format. It looks like a number but it's not.
This is where DataTransformers come into play.

A DataTransformer serves two purposes, it reverse-transformer a user-input
value into a Model value format (like an object or a PHP primitive type).

And can transform this Model format back into a user friendly output.

The InvoiceNumber object in this example is such a model value.

.. include:: invoice_number.rst.inc

Creating the Transformer
------------------------

Create an ``InvoiceNumberTransformer`` class - this class will be responsible
for converting to and from the invoice number and the ``InvoiceNumber`` object::

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

Now that you have the transformer built, you need to add it to your
invoice field type::

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
            $config->setViewTransformer(new InvoiceNumberTransformer());
        }

        public function getName()
        {
            return 'invoice_number';
        }
    }

Cool, you're done! Your user will be able to enter an invoice number into the
field and it will be transformed back into an ``InvoiceNumber`` object. This means
after a successful transformation, the system will pass an ``InvoiceNumber``
object instead of a string value.

And when exporting the value the original format is shown.

View or Norm DataTransformer
----------------------------

A DataTransformer always transformer between two different formats
the input to model (``reverseTransform``) and model to original format
(``transform``).

But there is more to DataTransformers, depending on the input format
you work with either a view format or a normalized (norm) format.

*The view is for a localized representation of a value like a local data,
while the norm format allows to provide the value in an export friendly
format like an ISO data format.*

The view-format is used mainly by the StringQuery input format, while
*all* other input processors use a normalized (or norm) format.

*Which DataTransformer and format will be used depends on the input processor
implementation.*

But as a rule of thumb, unless a norm DataTransformer is set for the field,
each input processor will use the view DataTransformer of the field.
And fallback to a simple string conversion if neither are set.

So why Use the Data Transformer?
--------------------------------

Transforming user input into a normalized value is done for multiple reasons.

* It makes using the value in the processor easier.
* It allows for localized input (like numbers on a none-latin charset).
* And most of all it makes it possible to optimize the search condition.

One very good example for usage is :doc:`Value Comparisons <value_comparison>`.
