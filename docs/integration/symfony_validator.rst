Symfony InputValidator
======================

The RollerworksSearch Symfony InputValidator facilitates the validating
of input values using the `Symfony Validator component`_.

This validation is meant to be used for business rules like a minimum/maximum
value range or disallowing specific patterns. The data transformers already
ensure the value is properly transformed.

Installation
------------

Following the :doc:`installation instructions </installing>` install the
Validation extension by running:

.. code-block:: bash

    $ php composer.phar require rollerworks/search-symfony-validator

And enable the ``Rollerworks\Component\Search\Extension\Symfony\Validator\ValidatorExtension``
in the ``SearchFactoryBuilder`` and pass the Input Validator to your Input Processor.

.. note::

    The RollerworksSearchBundle already enables the validator extension when
    you install the extension as described above.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Searches;
    use Rollerworks\Component\Search\Extension\Validator\ValidatorExtension;
    use Rollerworks\Component\Search\Extension\Validator\InputValidator;
    use Rollerworks\Component\Search\Input\StringQueryInput;
    use Symfony\Component\Validator\Validation;

    $searchFactory = Searches::createSearchFactoryBuilder();
        ->addExtension(new ValidatorExtension())
        // ...

        ->getSearchFactory();

    $validatorBuilder = Validation::createValidatorBuilder();
    $validator = $validatorBuilder->getValidator();

    $inputProcessor = new StringQueryInput(new InputValidator($validator));

That's it, you can now use the Validator. But note only search fields with
``constraints`` set will be actually validated by the validator.

Setting validation constraints
------------------------------

Before you continue make sure you have a good understanding of what Constraints
are and how they are to be used. See `Symfony Validator component`_ for
more information.

You can configure the constraint on a per-field basis when building your FieldSet::

    use Symfony\Component\Validator\Constraints as Assert;
    use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;

    // ...

    $fieldSetBuilder = $searchFactory->createFieldSetBuilder()
    $fieldSetBuilder->add('id', IntegerType::class, ['constraints' => new Assert\Range(['min' => 5])]);

Or when your (custom) type always needs these specific constraints make the constraints
part of the field type using the ``configureOptions`` method of the field type. Using::

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'constraints' => new Assert\Length(array('min' => 101)),
            ]
        );
    }

.. _`Symfony Validator component`: http://symfony.com/doc/current/validation.html
