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

use Rollerworks\Component\Search\ValueIncrementer;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DateValueComparator implements ValueIncrementer
{
    /**
     * Returns whether the first value is higher then the second value.
     *
     * @param \DateTimeInterface $higher
     * @param \DateTimeInterface $lower
     * @param array              $options
     *
     * @return bool
     */
    public function isHigher($higher, $lower, array $options): bool
    {
        return $higher > $lower;
    }

    /**
     * Returns whether the first value is lower then the second value.
     *
     * @param \DateTimeInterface $lower
     * @param \DateTimeInterface $higher
     * @param array              $options
     *
     * @return bool
     */
    public function isLower($lower, $higher, array $options): bool
    {
        return $lower < $higher;
    }

    /**
     * Returns whether the first value equals the second value.
     *
     * @param \DateTimeInterface $value
     * @param \DateTimeInterface $nextValue
     * @param array              $options
     *
     * @return bool
     */
    public function isEqual($value, $nextValue, array $options): bool
    {
        return $value->getTimestamp() === $nextValue->getTimestamp();
    }

    /**
     * Returns the incremented value of the input.
     *
     * The value should returned in the normalized format.
     *
     * @param \DateTimeImmutable|\DateTime $value      The value to increment
     * @param array                        $options    Array of options passed with the field
     * @param int                          $increments Number of increments
     *
     * @return \DateTimeInterface
     */
    public function getIncrementedValue($value, array $options, int $increments = 1)
    {
        if (!$value instanceof \DateTimeImmutable) {
            $value = clone $value;
        }

        $value = $value->modify('+'.$increments.' days');

        return $value;
    }
}
