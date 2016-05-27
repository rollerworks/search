<?php

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
    private $viewLower;
    private $viewUpper;
    private $lower;
    private $upper;
    private $inclusiveLower;
    private $inclusiveUpper;

    /**
     * Constructor.
     *
     * @param mixed  $lower
     * @param mixed  $upper
     * @param string $viewLower
     * @param string $viewUpper
     * @param bool   $inclusiveLower
     * @param bool   $inclusiveUpper
     */
    public function __construct(
        $lower,
        $upper,
        $inclusiveLower = true,
        $inclusiveUpper = true,
        $viewLower = null,
        $viewUpper = null
    ) {
        $this->lower = $lower;
        $this->upper = $upper;

        $this->viewLower = (string) (null !== $viewLower ? $viewLower : $lower);
        $this->viewUpper = (string) (null !== $viewUpper ? $viewUpper : $upper);

        $this->inclusiveLower = (bool) $inclusiveLower;
        $this->inclusiveUpper = (bool) $inclusiveUpper;
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

    /**
     * @return string
     */
    public function getViewLower()
    {
        return $this->viewLower;
    }

    /**
     * @return string
     */
    public function getViewUpper()
    {
        return $this->viewUpper;
    }
}
