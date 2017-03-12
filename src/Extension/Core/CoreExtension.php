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

namespace Rollerworks\Component\Search\Extension\Core;

use Rollerworks\Component\Search\AbstractExtension;

/**
 * Represents the main search extension, which loads the core functionality.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CoreExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadTypes(): array
    {
        $dateTimeComparator = new ValueComparator\DateTimeValueValueComparator();
        $numberComparator = new ValueComparator\NumberValueComparator();

        return [
            new Type\SearchFieldType(new ValueComparator\SimpleValueComparator()),
            new Type\DateType(new ValueComparator\DateValueComparator()),
            new Type\DateTimeType($dateTimeComparator),
            new Type\TimeType($dateTimeComparator),
            new Type\TimestampType($dateTimeComparator),
            new Type\BirthdayType(new ValueComparator\BirthdayValueComparator()),
            new Type\IntegerType($numberComparator),
            new Type\MoneyType(new ValueComparator\MoneyValueComparator()),
            new Type\NumberType($numberComparator),
        ];
    }
}
