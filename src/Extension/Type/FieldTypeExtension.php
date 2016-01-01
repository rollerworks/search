<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type;

use Rollerworks\Component\Search\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allow to configure Doctrine ORM conversions.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldTypeExtension extends AbstractFieldTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            ['doctrine_dbal_conversion' => null]
        );

        if ($resolver instanceof Options) {
            $resolver->setAllowedTypes(
                'doctrine_dbal_conversion',
                [
                    'null',
                    'Closure',
                    'Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface',
                    'Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface',
                    'Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface',
                ]
            );
        } else {
            $resolver->setAllowedTypes(
                [
                    'doctrine_dbal_conversion' => [
                        'null',
                        'Closure',
                        'Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface',
                        'Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface',
                        'Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface',
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'field';
    }
}
