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

namespace Rollerworks\Bundle\SearchBundle\Type;

use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Rollerworks\Component\Search\Field\OrderFieldType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

final class TranslatableOrderFieldTypeExtension extends AbstractFieldTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->addAllowedTypes('label', TranslatableInterface::class);
    }

    public function getExtendedType(): string
    {
        return OrderFieldType::class;
    }
}
