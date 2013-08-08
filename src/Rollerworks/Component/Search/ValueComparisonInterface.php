<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

/**
 * ValueComparisonInterface.
 *
 * Each ValueComparison class must implement this class.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ValueComparisonInterface
{
    /**
     * Returns whether the first value is higher then the second value.
     *
     * @param mixed $value
     * @param mixed $nextValue
     * @param array $options
     *
     * @return boolean
     */
    public function isHigher($value, $nextValue, array $options);

    /**
     * Returns whether the first value is lower then the second value.
     *
     * @param mixed $value
     * @param mixed $nextValue
     * @param array $options
     *
     * @return boolean
     */
    public function isLower($value, $nextValue, $options);

    /**
     * Returns whether the first value equals the second value.
     *
     * @param mixed $value
     * @param mixed $nextValue
     * @param array $options
     *
     * @return boolean
     */
    public function isEqual($value, $nextValue, $options);
}
