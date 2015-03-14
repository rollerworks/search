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

use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Rollerworks\Component\Search\ValueIncrementerInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class MoneyValueComparison implements ValueIncrementerInterface
{
    /**
     * Returns whether the first value is higher then the second value.
     *
     * @param MoneyValue $higher
     * @param MoneyValue $lower
     * @param array      $options
     *
     * @return bool
     */
    public function isHigher($higher, $lower, array $options)
    {
        if ($lower->currency !== $higher->currency) {
            return false;
        }

        return $higher->value > $lower->value;
    }

    /**
     * Returns whether the first value is lower then the second value.
     *
     * @param MoneyValue $lower
     * @param MoneyValue $higher
     * @param array      $options
     *
     * @return bool
     */
    public function isLower($lower, $higher, $options)
    {
        if ($lower->currency !== $higher->currency) {
            return false;
        }

        return $lower->value < $higher->value;
    }

    /**
     * Returns whether the first value equals the second value.
     *
     * @param MoneyValue $value
     * @param MoneyValue $nextValue
     * @param array      $options
     *
     * @return bool
     */
    public function isEqual($value, $nextValue, $options)
    {
        return $value == $nextValue;
    }

    /**
     * Returns the incremented value of the input.
     *
     * The value should returned in the normalized format.
     *
     * @param MoneyValue $value      The value to increment
     * @param array      $options    Array of options passed with the field
     * @param int        $increments Number of increments
     *
     * @return MoneyValue
     */
    public function getIncrementedValue($value, array $options, $increments = 1)
    {
        $newValue = clone $value;

        if (isset($options['increase_by_decimal'])) {
            $newValue->value += (float) ("0.$increments");
        } else {
            $newValue->value += $increments;
        }

        return $newValue;
    }
}
