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

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToTimestampTransformer;
use Rollerworks\Component\Search\Extension\Core\ValueComparator\DateTimeValueComparator;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\Range;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class TimestampType extends AbstractFieldType
{
    private $valueComparator;

    public function __construct()
    {
        $this->valueComparator = new DateTimeValueComparator();
    }

    public function buildType(FieldConfig $config, array $options): void
    {
        $config->setValueComparator($this->valueComparator);
        $config->setValueTypeSupport(Range::class, true);
        $config->setValueTypeSupport(Compare::class, true);

        $config->setViewTransformer(
            new DateTimeToTimestampTransformer(
                $options['model_timezone'],
                $options['view_timezone']
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'model_timezone' => null,
            'view_timezone' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'timestamp';
    }
}
