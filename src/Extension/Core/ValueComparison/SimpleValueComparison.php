<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
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
     * {@inheritdoc}
     */
    public function isHigher($value, $nextValue, array $options)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($value, $nextValue, $options)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEqual($value, $nextValue, $options)
    {
        // This does not work for objects, so they should have
        // there own comparison classes.
        return $value === $nextValue;
    }
}
