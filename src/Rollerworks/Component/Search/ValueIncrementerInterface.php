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
 * ValueIncrementerInterface allows for finding the incremented value.
 *
 * Increments can be used for optimizing and such.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ValueIncrementerInterface extends ValueComparisonInterface
{
    /**
     * Returns the incremented value of the input.
     *
     * The value should be returned in the normalized format.
     *
     * @param mixed   $value      The value to increment.
     * @param array   $options    Array of options passed with the field
     * @param integer $increments Number of increments
     *
     * @return mixed
     */
    public function getIncrementedValue($value, array $options, $increments = 1);
}
