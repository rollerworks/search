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
final class BirthdayValueComparator implements ValueComparator
{
    /**
     * @param \DateTimeInterface|int $higher
     * @param \DateTimeInterface|int $lower
     */
    public function isHigher($higher, $lower, array $options): bool
    {
        if (!\is_object($higher) xor !\is_object($lower)) {
            return false;
        }

        return $higher > $lower;
    }

    /**
     * @param \DateTimeInterface|int $lower
     * @param \DateTimeInterface|int $higher
     */
    public function isLower($lower, $higher, array $options): bool
    {
        if (!\is_object($higher) xor !\is_object($lower)) {
            return false;
        }

        return $higher < $lower;
    }

    /**
     * @param \DateTimeInterface|int $value
     * @param \DateTimeInterface|int $nextValue
     */
    public function isEqual($value, $nextValue, array $options): bool
    {
        if (!\is_object($value) xor !\is_object($nextValue)) {
            return false;
        }

        return $value == $nextValue;
    }
}
