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

namespace Rollerworks\Component\Search\Value;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Range implements RequiresComparatorValueHolder
{
    private $lower;
    private $upper;
    private $inclusiveLower;
    private $inclusiveUpper;

    public function __construct($lower, $upper, bool $inclusiveLower = true, bool $inclusiveUpper = true)
    {
        $this->lower = $lower;
        $this->upper = $upper;
        $this->inclusiveLower = $inclusiveLower;
        $this->inclusiveUpper = $inclusiveUpper;
    }

    public function getLower()
    {
        return $this->lower;
    }

    public function getUpper()
    {
        return $this->upper;
    }

    public function isLowerInclusive(): bool
    {
        return $this->inclusiveLower;
    }

    public function isUpperInclusive(): bool
    {
        return $this->inclusiveUpper;
    }
}
