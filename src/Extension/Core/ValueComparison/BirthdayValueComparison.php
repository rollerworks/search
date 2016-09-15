<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\ValueComparison;

use Rollerworks\Component\Search\ValueIncrementerInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BirthdayValueComparison implements ValueIncrementerInterface
{
    /**
     * Returns whether the first value is higher then the second value.
     *
     * @param \DateTime|int $higher
     * @param \DateTime|int $lower
     * @param array         $options
     *
     * @return bool
     */
    public function isHigher($higher, $lower, array $options)
    {
        if (!is_object($higher) xor !is_object($lower)) {
            return false;
        }

        return $higher > $lower;
    }

    /**
     * Returns whether the first value is lower then the second value.
     *
     * @param \DateTime|int $lower
     * @param \DateTime|int $higher
     * @param array         $options
     *
     * @return bool
     */
    public function isLower($lower, $higher, $options)
    {
        if (!is_object($higher) xor !is_object($lower)) {
            return false;
        }

        return $higher < $lower;
    }

    /**
     * Returns whether the first value equals the second value.
     *
     * @param \DateTime|int $value
     * @param \DateTime|int $nextValue
     * @param array         $options
     *
     * @return bool
     */
    public function isEqual($value, $nextValue, $options)
    {
        if (!is_object($value) xor !is_object($nextValue)) {
            return false;
        }

        return $value == $nextValue;
    }

    /**
     * Returns the incremented value of the input.
     *
     * The value should returned in the normalized format.
     *
     * @param \DateTime|int $value      The value to increment
     * @param array         $options    Array of options passed with the field
     * @param int           $increments Number of increments
     *
     * @return \DateTime
     */
    public function getIncrementedValue($value, array $options, $increments = 1)
    {
        if (is_object($value)) {
            $newValue = clone $value;
            $newValue->modify('+'.$increments.' days');

            return $newValue;
        }

        return $value + $increments;
    }
}
