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

use Rollerworks\Component\Search\Elasticsearch\ChildOrderConversion;
use Rollerworks\Component\Search\Elasticsearch\QueryConversion;
use Rollerworks\Component\Search\Elasticsearch\ValueConversion;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Rollerworks\Component\Search\Field\OrderFieldType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderTypeExtension extends AbstractFieldTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'elasticsearch_child_order_conversion' => static fn (Options $options) => $options['type_options']['elasticsearch_child_order_conversion'] ?? null,
                'elasticsearch_conversion' => null,
            ])
            ->setAllowedTypes('elasticsearch_child_order_conversion', ['null', ChildOrderConversion::class])
            ->setAllowedTypes(
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
        return OrderFieldType::class;
    }
}
