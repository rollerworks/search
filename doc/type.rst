Type
====

Types are used for configuring a search field.

As you probably know, searching with the system works by defining searching conditions.
But in order to known how the value should be handled each field has a type specialized
in handling the value type/format.

The type is used for configuring the field so you don't have to apply all the configuration
for each field individually.

For a list of build-in types see :doc:`reference/types`.


How to Create a Custom Field Type
---------------------------------

You may find that the build-in types do not always meet your needs and you want use your own.
Fortunately making your own field-types is very easy, this section explains how to achieve this.

The first example shows how to reuse an existing type for building your field types.
And the second one shows a more advanced example how you can create your own type
with ValueComparison and options.

.. note::

    All the methods in the type are optional, you don't have to "implement"
    them all.

    Its advised to always extend the ``Rollerworks\Component\Search\AbstractFieldType`` class
    to ensure you're types are forward compatible to any additions.

ClientId
~~~~~~~~

This example shows how you can reuse the integer-type to create the ClientNumberType which
handles client-ids with the special format: ``C30320``.

The ClientId begins with a 'C' prefix, and is prepended with zeros until its at least 4 digits.

Example: ``C30320, C0001, C442482, C0020``.

.. note::

    Inheritance is done using a special builder, its advised not to extend type-classes directly.
    Using this special type of inheritance allows to reuse types with options-building
    and type-extensions handling.

The Namespace does not really mather, but the convention is to use
``VendorName\Search\Extension\ExtensionName\Type`` for types.

The VendorName in this example is 'Acme', and the Extension is called 'Client'.

Because the type handel's client-ids in a special format, the type needs a DataTransformer for this.
DataTransformer's are reusable classes which reverseTransform the input data to a normalized format,
and transform the normalized data back to a localized version (better known as View representation).

ClientIdTransformer
*******************

.. code-block:: php
    :linenos:

    // src/Acme/Search/Extension/Client/DataTransformer/ClientIdTransformer.php

    namespace Acme\Client\Search\Extension\Client\DataTransformer;

    use Rollerworks\Component\Search\DataTransformerInterface;
    use Rollerworks\Component\Search\Exception\TransformationFailedException;

    class ClientIdTransformer implements DataTransformerInterface
    {
        public function transform($value)
        {
            return sprintf('C%04d', $value);
        }

        public function reverseTransform($value)
        {
            if (null !== $value && !is_scalar($value)) {
                throw new TransformationFailedException('Expected a scalar.');
            }

            return ltrim('C0');
        }
    }

ClientIdType
************

.. code-block:: php
    :linenos:

    // src/Acme/Search/Extension/Client/Type/ClientIdType.php

    namespace Acme\Client\Search\Extension\Client\Type;

    use Rollerworks\Component\Search\AbstractFieldType;
    use Acme\Search\Extension\Client\DataTransformer\ClientIdTransformer;

    class ClientIdType extends AbstractFieldType
    {
        /**
         * {@inheritDoc}
         */
        public function buildType(FieldConfigInterface $config, array $options)
        {
            // The integer-type sets a special transformer for localized integers
            // This type doesn't need this so remove the transformers
            $config->resetViewTransformers();

            $config->addViewTransformer(new ClientIdTransformer());
        }

        public function getName()
        {
            return 'client_id';
        }

        // This type inherits the integer-type, so define it as the parent
        public function getParent()
        {
            return 'integer';
        }
    }

Now the type is created, the SearchFactory needs to know it exists.
This can be done using two methods: Using ``SearchFactoryBuilder->addType(new ClientIdType())`` or the
recommended way using a SearchExtension;

.. code-block:: php
    :linenos:

    // src/Acme/Client/Search/Extension/Client/ClientExtension.php

    namespace Acme\Client\Search\Extension\Client;

    use Rollerworks\Component\Search\AbstractExtension;

    class ClientExtension extends AbstractExtension
    {
        protected function loadTypes()
        {
            return array(
                new Type\ClientIdType(),
            );
        }
    }

And then registering at the FactoryBuilder.

.. code-block:: php
    :linenos:

    /* ... */

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new ClientExtension())
        ->getSearchFactory();

That's it the type is now ready for usage.

InvoiceNumber
~~~~~~~~~~~~~

This example shows an advanced example for creating a field-type,
the InvoiceNumber consists of a year and leading-zero digits like: ``2013-0120``.

Because the format is very custom, and you'd really want get the most out of the
search system, this example shows all the details on creating a
type using all features available.

From top to bottom it shows how to make the:

1. InvoiceNumber value-class for holding the invoice number.
2. The DataTransformer for the format handling.
3. A Validator Constraint to prevent users from passing in invalid values.
4. A ValueComparison used for validating and optimizing.
5. The InvoiceNumberType and the SearchExtension class.

First create the value class for holding the InvoiceNumber.
The InvoiceNumber value-class is similar to an array but ensures
the proper values is always used, the properties are public for fast access.

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/InvoiceValue.php

    namespace Acme\Invoice;

    class InvoiceValue
    {
        public $year;
        public $number;

        public function __construct($input)
        {
            if (!preg_match('/^(?P<year>\d{4})-(?P<number>\d+)$/s', $input, $matches)) {
                throw new \InvalidArgumentException('This not a valid invoice number.');
            }

            $this->year = (int) $matches['year'];
            $this->number = (int) ltrim($matches['number'], '0');
        }

        public function equals(InvoiceValue $input)
        {
            return $input == $this;
        }

        public function isHigher(InvoiceValue $input)
        {
            if ($this->year > $input->year) {
                return true;
            }

            if ($input->getYear === $this->getYear && $this->getNumber > $input->number) {
                return true;
            }

            return false;
        }

        public function isLower(InvoiceValue $input)
        {
            if ($this->year < $input->year) {
                return true;
            }

            if ($input->year === $this->year && $this->getNumber < $input->number) {
                return true;
            }

            return false;
        }

        public function __toString()
        {
            // Return the invoice number with leading zero
            return sprintf('%d-%04d', $this->year, $this->number);
        }
    }

InvoiceNumberTransformer
************************

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Search/Extension/Invoice/DataTransformer/InvoiceNumberTransformer.php

    namespace Acme\Invoice\Search\Extension\Invoice\DataTransformer;

    use Rollerworks\Component\Search\DataTransformerInterface;
    use Rollerworks\Component\Search\Exception\TransformationFailedException;
    use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
    use Acme\Invoice\InvoiceValue;

    class InvoiceNumberTransformer implements DataTransformerInterface
    {
        public function transform($value)
        {
            if (!$value instanceof InvoiceValue) {
                throw new UnexpectedTypeException($value, 'Acme\Invoice\InvoiceValue');
            }

            return (string) $value;
        }

        public function reverseTransform($value)
        {
            if (null !== $value && !is_scalar($value)) {
                throw new TransformationFailedException('Expected a scalar.');
            }

            try {
                return new InvoiceValue();
            } catch (\Exception $e) {
                throw new TransformationFailedException('This not a valid invoice number.')
            }
        }
    }

InvoiceNumberConstraint
***********************

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Constraints/InvoiceNumberConstraint.php

    namespace Acme\Invoice\Constraints;

    use Symfony\Component\Validator\Constraint;

    class InvoiceNumberConstraint extends Constraint
    {
        public $message = 'This value is not a valid invoice number.';
    }

InvoiceNumberConstraintValidator
********************************

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Constraints/InvoiceNumberConstraintValidator.php

    namespace Acme\Invoice\Constraints;

    use Symfony\Component\Validator\Constraint;
    use Symfony\Component\Validator\Exception\UnexpectedTypeException;
    use Acme\Invoice\InvoiceValue;

    class InvoiceNumberConstraintValidator extends ConstraintValidator
    {
        public function validate($value, Constraint $constraint)
        {
            if (null === $value || '' === $value) {
                return;
            }

            if (!$value instanceof InvoiceValue) {
                throw new UnexpectedTypeException($value, 'Acme\Invoice\InvoiceValue');
            }

            // Ensure the invoice year is not to far in the future
            if ($value->year > (date('Y') + 1)) {
                $this->context->addViolation($constraint->message, array(), $value));
            }
        }
    }

InvoiceNumberComparison
***********************

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Search/Extension/Invoice/ValueComparison/InvoiceNumberComparison.php

    namespace Acme\Invoice\Search\Extension\Invoice\ValueComparison;

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

        /**
         * Returns the incremented value of the input.
         *
         * The value should returned in the normalized format.
         */
        public function getIncrementedValue($value, array $options, $increments = 1)
        {
            $newValue = clone $value;
            $newValue->number += $increments;

            return $newValue;
        }
    }

InvoiceNumberType
*****************

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Search/Extension/Invoice/Type/InvoiceNumberType.php

    namespace Acme\Invoice\Search\Extension\Invoice\Type;

    use Rollerworks\Component\Search\AbstractFieldType;
    use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
    use Rollerworks\Component\Search\FieldConfigInterface;
    use Rollerworks\Component\Search\ValueComparisonInterface;
    use Symfony\Component\OptionsResolver\OptionsResolverInterface;
    use Acme\Search\Extension\Client\DataTransformer\ClientIdTransformer;
    use Acme\Invoice\Constraints\InvoiceNumberConstraint;

    class InvoiceNumberType extends AbstractFieldType
    {
        protected $valueComparison;

        public function __construct(ValueComparisonInterface $valueComparison)
        {
            $this->valueComparison = $valueComparison;
        }

        public function buildType(FieldConfigInterface $config, array $options)
        {
            $config->setValueComparison($this->valueComparison);
            $config->addViewTransformer(new InvoiceNumberTransformer());
        }

        public function setDefaultOptions(OptionsResolverInterface $resolver)
        {
            $resolver->setDefaults(array(
                'constraints' => array(new InvoiceNumberConstraint()),
            ));
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

Now the type is created, the SearchFactory needs to know it exists.

.. code-block:: php
    :linenos:

    // src/Acme/Invoice/Search/Extension/Invoice/InvoiceExtension.php

    namespace Acme\Invoice\Search\Extension\Invoice;

    use Rollerworks\Component\Search\AbstractExtension;

    class InvoiceExtension extends AbstractExtension
    {
        protected function loadTypes()
        {
            return array(
                new Type\InvoiceNumberType(new ValueComparison\InvoiceNumberComparison()),
            );
        }
    }

And then registering at the FactoryBuilder.

.. note::

    Because the InvoiceNumberType uses the 'constraints' options the
    validator extension must be enabled as well.

.. code-block:: php
    :linenos:

    /* ... */

    use Rollerworks\Component\Search\Extension\Validator\ValidatorExtension;
    use Acme\Invoice\Search\Extension\Invoice\InvoiceExtension;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->addExtension(new ValidatorExtension())
        ->addExtension(new InvoiceExtension())
        ->getSearchFactory();

That's it the type is now ready for usage.
