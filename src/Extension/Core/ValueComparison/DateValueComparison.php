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
class DateValueComparison implements ValueIncrementerInterface
{
    /**
     * Returns whether the first value is higher then the second value.
     *
     * @param \DateTime $higher
     * @param \DateTime $lower
     * @param array     $options
     *
     * @return bool
     */
    public function isHigher($higher, $lower, array $options)
    {
        return $higher > $lower;
    }

    /**
     * Returns whether the first value is lower then the second value.
     *
     * @param \DateTime $lower
     * @param \DateTime $higher
     * @param array     $options
     *
     * @return bool
     */
    public function isLower($lower, $higher, $options)
    {
        return $lower < $higher;
    }

    /**
     * Returns whether the first value equals the second value.
     *
     * @param \DateTime $value
     * @param \DateTime $nextValue
     * @param array     $options
     *
     * @return bool
     */
    public function isEqual($value, $nextValue, $options)
    {
        return $value->getTimestamp() === $nextValue->getTimestamp();
    }

    /**
     * Returns the incremented value of the input.
     *
     * The value should returned in the normalized format.
     *
     * @param \DateTime $value      The value to increment
     * @param array     $options    Array of options passed with the field
     * @param int       $increments Number of increments
     *
     * @return \DateTime
     */
    public function getIncrementedValue($value, array $options, $increments = 1)
    {
        $newValue = clone $value;
        $newValue->modify('+'.$increments.' days');

        return $newValue;
    }
}
