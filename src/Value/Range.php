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
class Range implements ValueHolder
{
    private $lower;
    private $upper;
    private $inclusiveLower;
    private $inclusiveUpper;

    /**
     * Constructor.
     *
     * @param mixed $lower
     * @param mixed $upper
     * @param bool  $inclusiveLower
     * @param bool  $inclusiveUpper
     */
    public function __construct(
        $lower,
        $upper,
        bool $inclusiveLower = true,
        bool $inclusiveUpper = true
    ) {
        $this->lower = $lower;
        $this->upper = $upper;
        $this->inclusiveLower = $inclusiveLower;
        $this->inclusiveUpper = $inclusiveUpper;
    }

    /**
     * Get the lower value of the range.
     *
     * @return mixed
     */
    public function getLower()
    {
        return $this->lower;
    }

    /**
     * Get the upper value of the range.
     *
     * @return mixed
     */
    public function getUpper()
    {
        return $this->upper;
    }

    /**
     * Return whether the lower-value of the range is inclusive.
     *
     * @return bool
     */
    public function isLowerInclusive()
    {
        return $this->inclusiveLower;
    }

    /**
     * Return whether the upper-value of the range is inclusive.
     *
     * @return bool
     */
    public function isUpperInclusive()
    {
        return $this->inclusiveUpper;
    }
}
