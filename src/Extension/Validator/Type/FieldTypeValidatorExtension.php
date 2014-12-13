<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Validator\Type;

use Rollerworks\Component\Search\AbstractFieldTypeExtension;
use Rollerworks\Component\Search\FieldConfigInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldTypeValidatorExtension extends AbstractFieldTypeExtension
{
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
    public function setDefaultOptions(OptionsResolverInterface $optionsResolver)
    {
        $optionsResolver->setDefaults(
            array(
                'constraints' => array(),
                'validation_groups' => array('Default'),
            )
        );

        $optionsResolver->setAllowedTypes(
            array(
                'constraints' => array('array', 'Symfony\Component\Validator\Constraint'),
                'validation_groups' => array('array'),
            )
        );

        $optionsResolver->setNormalizers(
            array(
                'constraints' => function (Options $options, $value) {
                    return !is_array($value) ? array($value) : $value;
                },
            )
        );
    }
}
