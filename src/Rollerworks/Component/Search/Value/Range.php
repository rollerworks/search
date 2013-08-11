<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Value;

class Range
{
    protected $lower;
    protected $upper;
    protected $inclusiveLower;
    protected $inclusiveUpper;

    /**
     * Constructor.
     *
     * @param mixed   $lower
     * @param mixed   $upper
     * @param boolean $inclusiveLower
     * @param boolean $inclusiveUpper
     */
    public function __construct($lower, $upper, $inclusiveLower = true, $inclusiveUpper = true)
    {
        $this->lower = $lower;
        $this->upper = $upper;

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
     * @return boolean
     */
    public function isLowerInclusive()
    {
        return $this->inclusiveLower;
    }

    /**
     * Return whether the upper-value of the range is inclusive.
     *
     * @return boolean
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
}
