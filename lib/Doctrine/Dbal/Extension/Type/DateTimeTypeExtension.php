<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type;

use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\DateIntervalConversion;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeTypeExtension extends AbstractFieldTypeExtension
{
    /**
     * @var DateIntervalConversion
     */
    private $conversion;

    public function __construct()
    {
        $this->conversion = new DateIntervalConversion();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            ['doctrine_dbal_conversion' => function (Options $options) {
                if ($options['allow_relative']) {
                    return $this->conversion;
                }

                return null;
            }]
        );
    }

    public function getExtendedType(): string
    {
        return DateTimeType::class;
    }
}
