<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Value;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Range
{
    protected $viewLower;
    protected $viewUpper;

    protected $lower;
    protected $upper;
    protected $inclusiveLower;
    protected $inclusiveUpper;

    /**
     * Constructor.
     *
     * @param mixed  $lower
     * @param mixed  $upper
     * @param bool   $inclusiveLower
     * @param bool   $inclusiveUpper
     * @param string $viewLower
     * @param string $viewUpper
     */
    public function __construct($lower, $upper, $inclusiveLower = true, $inclusiveUpper = true, $viewLower = null, $viewUpper = null)
    {
        $this->lower = $lower;
        $this->upper = $upper;

        $this->viewLower = null !== $viewLower ? $viewLower : $lower;
        $this->viewUpper = null !== $viewUpper ? $viewUpper : $upper;

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
     * Set the lower value of the range.
     *
     * @param mixed $value
     */
    public function setLower($value)
    {
        $this->lower = $value;
    }

    /**
     * Set the upper value of the range.
     *
     * @param mixed $value
     */
    public function setUpper($value)
    {
        $this->upper = $value;
    }

    /**
     * @param string $value
     */
    public function setViewLower($value)
    {
        $this->viewLower = $value;
    }

    /**
     * @param string $value
     */
    public function setViewUpper($value)
    {
        $this->viewUpper = $value;
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
