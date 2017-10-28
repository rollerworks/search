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

namespace Rollerworks\Component\Search;

/**
 * ValueComparator.
 *
 * Each ValueComparator class must implement this interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ValueComparator
{
    /**
     * Returns whether the first value is higher then the second value.
     *
     * @param mixed $higher
     * @param mixed $lower
     * @param array $options
     *
     * @return bool
     */
    public function isHigher($higher, $lower, array $options): bool;

    /**
     * Returns whether the first value is lower then the second value.
     *
     * @param mixed $lower
     * @param mixed $higher
     * @param array $options
     *
     * @return bool
     */
    public function isLower($lower, $higher, array $options): bool;

    /**
     * Returns whether the first value equals the second value.
     *
     * @param mixed $value
     * @param mixed $nextValue
     * @param array $options
     *
     * @return bool
     */
    public function isEqual($value, $nextValue, array $options): bool;
}
