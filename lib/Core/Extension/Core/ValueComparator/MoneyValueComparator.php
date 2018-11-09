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

use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Rollerworks\Component\Search\ValueComparator;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class MoneyValueComparator implements ValueComparator
{
    /**
     * @param MoneyValue $higher
     * @param MoneyValue $lower
     */
    public function isHigher($higher, $lower, array $options): bool
    {
        if (!$higher->value->isSameCurrency($lower->value)) {
            return false;
        }

        return $higher->value->greaterThan($lower->value);
    }

    /**
     * @param MoneyValue $lower
     * @param MoneyValue $higher
     */
    public function isLower($lower, $higher, array $options): bool
    {
        if (!$higher->value->isSameCurrency($lower->value)) {
            return false;
        }

        return $lower->value->lessThan($higher->value);
    }

    /**
     * @param MoneyValue $value
     * @param MoneyValue $nextValue
     */
    public function isEqual($value, $nextValue, array $options): bool
    {
        return $value->value->equals($nextValue->value);
    }
}
