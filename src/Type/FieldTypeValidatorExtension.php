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
        $optionsResolver->setDefaults(
            array(
                'constraints' => null,
                'validation_groups' => array('Default'),
            )
        );

        $self = $this;

        $constraintsNormalizer = function (Options $options, $constraints) use ($self) {
            if (null === $constraints) {
                return array();
            }

            if (!is_array($constraints)) {
                $constraints = array($constraints);
            }

            if (count($constraints) > 0) {
                return $constraints;
            }

            return $self->loadConstraints($options);
        };

        // BC layer for Symfony 2.7 and 3.0
        if ($optionsResolver instanceof OptionsResolverInterface) {
            $optionsResolver->setAllowedTypes(
                array(
                    'constraints' => array('array', 'Symfony\Component\Validator\Constraint', 'null'),
                    'validation_groups' => array('array'),
                )
            );

            $optionsResolver->setNormalizers(
                array(
                    'constraints' => $constraintsNormalizer,
                )
            );
        } else {
            $optionsResolver->setAllowedTypes(
                'constraints',
                array('array', 'Symfony\Component\Validator\Constraint', 'null')
            );
            $optionsResolver->setAllowedTypes('validation_groups', array('array'));
            $optionsResolver->setNormalizer('constraints', $constraintsNormalizer);
        }
    }

    /**
     * @internal
     */
    public function loadConstraints(Options $options)
    {
        if (null === $options['model_class'] || null === $options['model_property']) {
            return array();
        }

        // Its possible getting of the Metadata gives an error,
        // but that means the model class was invalid already.
        // Getting a property without metadata will give an empty array

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->validator->getMetadataFor($options['model_class']);
        $propertyMetadata = $classMetadata->getPropertyMetadata($options['model_property']);
        $constraints = array();

        foreach ($propertyMetadata as $metadata) {
            $constraints += $metadata->getConstraints();
        }

        return $constraints;
    }
}
