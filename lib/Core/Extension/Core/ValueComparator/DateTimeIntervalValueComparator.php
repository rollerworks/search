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

use Carbon\CarbonInterval;
use Rollerworks\Component\Search\ValueComparator;

final class DateTimeIntervalValueComparator implements ValueComparator
{
    /**
     * @param \DateTimeImmutable|CarbonInterval $higher
     * @param \DateTimeImmutable|CarbonInterval $lower
     */
    public function isHigher($higher, $lower, array $options): bool
    {
        if ($lower instanceof CarbonInterval && $higher instanceof CarbonInterval) {
            return $higher->greaterThan($lower);
        }

        $lower = $this->ensureDateTime($lower);
        $higher = $this->ensureDateTime($higher);

        return $higher > $lower;
    }

    /**
     * @param \DateTimeImmutable|CarbonInterval $value
     */
    private function ensureDateTime(object $value): \DateTimeImmutable
    {
        if ($value instanceof CarbonInterval) {
            $value = (new \DateTimeImmutable())->add($value);
        }

        return $value;
    }

    /**
     * @param \DateTimeImmutable|CarbonInterval $lower
     * @param \DateTimeImmutable|CarbonInterval $higher
     */
    public function isLower($lower, $higher, array $options): bool
    {
        if ($lower instanceof CarbonInterval && $higher instanceof CarbonInterval) {
            return $lower->lessThan($higher);
        }

        $lower = $this->ensureDateTime($lower);
        $higher = $this->ensureDateTime($higher);

        return $lower < $higher;
    }

    /**
     * @param \DateTimeImmutable|CarbonInterval $value
     * @param \DateTimeImmutable|CarbonInterval $nextValue
     */
    public function isEqual($value, $nextValue, array $options): bool
    {
        // Note that only values of the same type can be compared.
        // As an interval is never equal to "now".
        if (!is_a($value, \get_class($nextValue))) {
            return false;
        }

        if ($value instanceof CarbonInterval) {
            return $value->equalTo($nextValue);
        }

        return $value->getTimestamp() === $nextValue->getTimestamp();
    }
}
