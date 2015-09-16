<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Symfony\Validator\Type;

use Rollerworks\Component\Search\AbstractFieldTypeExtension;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorInterface as LegacyValidatorInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldTypeValidatorExtension extends AbstractFieldTypeExtension
{
    /**
     * @var ValidatorInterface|LegacyValidatorInterface
     */
    private $validator;

    /**
     * Constructor.
     *
     * @param ValidatorInterface|LegacyValidatorInterface $validator
     */
    public function __construct($validator)
    {
        if (!$validator instanceof ValidatorInterface && !$validator instanceof LegacyValidatorInterface) {
            throw new UnexpectedTypeException(
                $validator,
                'Symfony\Component\Validator\Validator\ValidatorInterface" or '.
                '"Symfony\Component\Validator\ValidatorInterface'
            );
        }

        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $builder, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'field';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $validator = $this->validator;

        $optionsResolver->setDefaults(
            [
                'validation_groups' => ['Default'],
                'constraints' => function (Options $options) use ($validator) {
                    if (null === $options['model_class'] || null === $options['model_property']) {
                        return [];
                    }

                    // Its possible getting of the Metadata gives an error,
                    // but that means the model class was invalid already.
                    // Getting a property without metadata will give an empty array

                    /** @var ClassMetadata $classMetadata */
                    $classMetadata = $validator->getMetadataFor($options['model_class']);
                    $propertyMetadata = $classMetadata->getPropertyMetadata($options['model_property']);
                    $constraints = [];

                    foreach ($propertyMetadata as $metadata) {
                        $constraints += $metadata->getConstraints();
                    }

                    return $constraints;
                },
            ]
        );

        // BC layer for Symfony 2.7 and 3.0
        if ($optionsResolver instanceof OptionsResolverInterface) {
            $optionsResolver->setAllowedTypes(
                [
                    'constraints' => ['array', 'Symfony\Component\Validator\Constraint'],
                    'validation_groups' => ['array'],
                ]
            );
        } else {
            $optionsResolver->setAllowedTypes('constraints', ['array', 'Symfony\Component\Validator\Constraint']);
            $optionsResolver->setAllowedTypes('validation_groups', ['array']);
        }
    }
}
