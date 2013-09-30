<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Core\ValueComparison;

use Rollerworks\Component\Search\ValueIncrementerInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class NumberValueComparison implements ValueIncrementerInterface
{
    /**
     * Returns whether the first value is higher then the second value.
     *
     * @param integer|float $higher
     * @param integer|float $lower
     * @param array     $options
     *
     * @return boolean
     */
    public function isHigher($higher, $lower, array $options)
    {
        return $higher > $lower;
    }

    /**
     * Returns whether the first value is lower then the second value.
     *
     * @param integer|float $lower
     * @param integer|float $higher
     * @param array     $options
     *
     * @return boolean
     */
    public function isLower($lower, $higher, $options)
    {
        return $lower < $higher;
    }

    /**
     * Returns whether the first value equals the second value.
     *
     * @param integer|float $value
     * @param integer|float $nextValue
     * @param array     $options
     *
     * @return boolean
     */
    public function isEqual($value, $nextValue, $options)
    {
        return $value === $nextValue;
    }

    /**
     * Returns the incremented value of the input.
     *
     * The value should returned in the normalized format.
     *
     * @param integer|float $value      The value to increment.
     * @param array         $options    Array of options passed with the field
     * @param integer       $increments Number of increments
     *
     * @return integer|float
     */
    public function getIncrementedValue($value, array $options, $increments = 1)
    {
        if (isset($options['increase_by_decimal'])) {
            return $value + floatval("0.$increments");
        }

        return $value + $increments;
    }
}
