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

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm\Type;

use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\Conversion\DateIntervalConversion;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DateTimeTypeExtension extends AbstractFieldTypeExtension
{
    /** @var DateIntervalConversion */
    private $conversion;

    public function __construct()
    {
        $this->conversion = new DateIntervalConversion();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('doctrine_orm_conversion', $this->conversion);
    }

    public function getExtendedType(): string
    {
        return DateTimeType::class;
    }
}
