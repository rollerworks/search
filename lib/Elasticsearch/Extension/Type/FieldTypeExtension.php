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

namespace Rollerworks\Component\Search\Elasticsearch\Extension\Type;

use Rollerworks\Component\Search\Elasticsearch\QueryConversion;
use Rollerworks\Component\Search\Elasticsearch\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FieldTypeExtension.
 */
class FieldTypeExtension extends AbstractFieldTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['elasticsearch_conversion' => null]);
        $resolver->setAllowedTypes(
            'elasticsearch_conversion',
            [
                'null',
                ValueConversion::class,
                QueryConversion::class,
            ]
        );
    }

    public function getExtendedType(): string
    {
        return SearchFieldType::class;
    }
}
