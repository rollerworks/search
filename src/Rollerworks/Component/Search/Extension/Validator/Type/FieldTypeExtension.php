<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Validator\Type;

use Rollerworks\Component\Search\AbstractFieldTypeExtension;
use Rollerworks\Component\Search\FieldConfigInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldTypeExtension extends AbstractFieldTypeExtension
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
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $optionsResolver)
    {
        $optionsResolver->setDefaults(array(
            'constraints' => array()
        ));

        $optionsResolver->setAllowedTypes(array(
            'constraints' => array('array', 'string')
        ));
    }
}
