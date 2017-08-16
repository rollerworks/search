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
 * Default ValueComparator implementation, only able to compare equality.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SimpleValueComparator implements ValueComparator
{
    /**
     * {@inheritdoc}
     */
    public function isHigher($value, $nextValue, array $options): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($lower, $higher, array $options): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEqual($value, $nextValue, array $options): bool
    {
        // This does not work for objects, so they should have
        // there own comparison classes.
        return $value === $nextValue;
    }
}
