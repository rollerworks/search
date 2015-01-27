<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
     * @param mixed $value      The value to increment.
     * @param array $options    Array of options passed with the field
     * @param int   $increments Number of increments
     *
     * @return mixed
     */
    public function getIncrementedValue($value, array $options, $increments = 1);
}
