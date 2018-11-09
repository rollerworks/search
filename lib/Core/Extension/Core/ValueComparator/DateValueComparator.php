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
class DateValueComparator implements ValueComparator
{
    /**
     * @param \DateTimeInterface $higher
     * @param \DateTimeInterface $lower
     */
    public function isHigher($higher, $lower, array $options): bool
    {
        return $higher > $lower;
    }

    /**
     * @param \DateTimeInterface $lower
     * @param \DateTimeInterface $higher
     */
    public function isLower($lower, $higher, array $options): bool
    {
        return $lower < $higher;
    }

    /**
     * @param \DateTimeInterface $value
     * @param \DateTimeInterface $nextValue
     */
    public function isEqual($value, $nextValue, array $options): bool
    {
        return $value->getTimestamp() === $nextValue->getTimestamp();
    }
}
