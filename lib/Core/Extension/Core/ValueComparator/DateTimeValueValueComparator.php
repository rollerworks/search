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

namespace Rollerworks\Component\Search\Extension\Core\ValueComparator;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class DateTimeValueValueComparator extends DateValueComparator
{
    /**
     * Returns the incremented value of the input.
     *
     * The value should returned in the normalized format.
     *
     * @param \DateTimeImmutable|\DateTime $value      The value to increment
     * @param array                        $options    Array of options passed with the field
     * @param int                          $increments Number of increments
     *
     * @return \DateTimeImmutable|\DateTime
     */
    public function getIncrementedValue($value, array $options, int $increments = 1)
    {
        $newValue = clone $value;

        if ($options['with_seconds']) {
            $newValue = $newValue->modify('+'.$increments.' seconds');
        } elseif ($options['with_minutes']) {
            $newValue = $newValue->modify('+'.$increments.' minutes');
        } else {
            $newValue = $newValue->modify('+'.$increments.' hours');
        }

        return $newValue;
    }
}
