<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\ValueComparison;

use Rollerworks\Component\Search\ValueComparisonInterface;

/**
 * Default ValueComparison implementation, only able to compare equality.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SimpleValueComparison implements ValueComparisonInterface
{
    /**
     * {@inheritDoc}
     */
    public function isHigher($value, $nextValue, array $options)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isLower($value, $nextValue, $options)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isEqual($value, $nextValue, $options)
    {
        // This does not work for objects, so it should have its comparison class
        return $value === $nextValue;
    }
}
