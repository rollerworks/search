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

use Rollerworks\Component\Search\Extension\Core\Type\MoneyType;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\Conversion\MoneyValueConversion;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MoneyTypeExtension extends AbstractFieldTypeExtension
{
    /**
     * @var MoneyValueConversion
     */
    private $conversion;

    public function __construct()
    {
        $this->conversion = new MoneyValueConversion();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('doctrine_orm_conversion', $this->conversion);
    }

    public function getExtendedType(): string
    {
        return MoneyType::class;
    }
}
