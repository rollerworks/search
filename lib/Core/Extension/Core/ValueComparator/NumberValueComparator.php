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

use Rollerworks\Component\Search\ValueComparator;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class NumberValueComparator implements ValueComparator
{
    /**
     * @param float|int $higher
     * @param float|int $lower
     */
    public function isHigher($higher, $lower, array $options): bool
    {
        return $higher > $lower;
    }

    /**
     * @param float|int $lower
     * @param float|int $higher
     */
    public function isLower($lower, $higher, array $options): bool
    {
        return $lower < $higher;
    }

    /**
     * @param float|int $value
     * @param float|int $nextValue
     */
    public function isEqual($value, $nextValue, array $options): bool
    {
        return $value === $nextValue;
    }
}
