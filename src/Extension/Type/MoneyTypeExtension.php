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
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\MoneyValueConversion;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Configures the MoneyValueConversion for Doctrine DBAL.
 *
 * Note: Due to technical limitations currently the option
 * "doctrine_dbal_with_currency" has no effect and the
 * currency comparisons is always ignored.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class MoneyTypeExtension extends AbstractFieldTypeExtension
{
    /**
     * @var MoneyValueConversion
     */
    private $conversion;

    /**
     * @param MoneyValueConversion $conversion
     */
    public function __construct(MoneyValueConversion $conversion)
    {
        $this->conversion = $conversion;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'doctrine_dbal_conversion' => $this->conversion,
                'doctrine_dbal_with_currency' => false,
            ]
        );

        $resolver->setAllowedTypes(
            ['doctrine_dbal_with_currency' => ['bool']]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'money';
    }
}
