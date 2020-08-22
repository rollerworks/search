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

use Rollerworks\Component\Search\Doctrine\Orm\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Orm\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldTypeExtension extends AbstractFieldTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['doctrine_orm_conversion' => null]);
        $resolver->setAllowedTypes(
            'doctrine_orm_conversion',
            [
                'null',
                \Closure::class,
                ColumnConversion::class,
                ValueConversion::class,
            ]
        );
    }

    public function getExtendedType(): string
    {
        return SearchFieldType::class;
    }
}
