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

use Money\Money;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Rollerworks\Component\Search\ValueIncrementer;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class MoneyValueComparator implements ValueIncrementer
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
    public function isHigher($higher, $lower, array $options): bool
    {
        if (!$higher->value->isSameCurrency($lower->value)) {
            return false;
        }

        return $higher->value->greaterThan($lower->value);
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
    public function isLower($lower, $higher, array $options): bool
    {
        if (!$higher->value->isSameCurrency($lower->value)) {
            return false;
        }

        return $lower->value->lessThan($higher->value);
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
    public function isEqual($value, $nextValue, array $options): bool
    {
        return $value->value->equals($nextValue->value);
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
    public function getIncrementedValue($value, array $options, int $increments = 1): MoneyValue
    {
        if (!isset($options['increase_by'])) {
            $options['increase_by'] = 'cent';
        }

        // NB. Amount is in cents.
        if ('amount' === $options['increase_by']) {
            $amount = ceil($value->value->getAmount() / 100) * 100;

            if ($increments > 1) {
                $amount += ($increments - 1) * 100;
            }

            $newValue = new Money($amount, $value->value->getCurrency());
        } else {
            // Increase with n cent.
            $newValue = $value->value->add(new Money($increments, $value->value->getCurrency()));
        }

        return new MoneyValue($newValue, $value->withCurrency);
    }
}
