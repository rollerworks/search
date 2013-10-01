<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldTypeExtensionInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Allow to configure Doctrine ORM conversions.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldTypeExtension implements FieldTypeExtensionInterface
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setAllowedTypes(array(
            'doctrine_dbal_conversion' => array(
                'Closure',
                'Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface',
                'Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface',
                'Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface'
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'field';
    }
}
