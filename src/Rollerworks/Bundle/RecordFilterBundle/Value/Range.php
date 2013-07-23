<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Value;

/**
 * Range value.
 */
class Range
{
    protected $lower;
    protected $upper;

    protected $originalLower;
    protected $originalUpper;

    /**
     * Constructor.
     *
     * @param mixed $lower
     * @param mixed $upper
     * @param mixed $originalLower
     * @param mixed $originalUpper
     *
     * @throws \UnexpectedValueException
     *
     * @api
     */
    public function __construct($lower, $upper, $originalLower = null, $originalUpper = null)
    {
        $this->lower = $lower;
        $this->upper = $upper;

        if (is_null($originalLower)) {
            $originalLower = $lower;
        }

        if (is_null($originalUpper)) {
            $originalUpper = $upper;
        }

        $this->originalLower = $originalLower;
        $this->originalUpper = $originalUpper;
    }

    /**
     * Get the lower value of the range.
     *
     * @return mixed
     *
     * @api
     */
    public function getLower()
    {
        return $this->lower;
    }

    /**
     * Get the upper value of the range.
     *
     * @return mixed
     *
     * @api
     */
    public function getUpper()
    {
        return $this->upper;
    }

    /**
     * Get the original lower value of the range.
     *
     * @return mixed
     *
     * @api
     */
    public function getOriginalLower()
    {
        return $this->originalLower;
    }

    /**
     * Get the original upper value of the range.
     *
     * @return mixed
     *
     * @api
     */
    public function getOriginalUpper()
    {
        return $this->originalUpper;
    }

    /**
     * Set the lower value of the range.
     *
     * @param mixed $value
     *
     * @api
     */
    public function setLower($value)
    {
        $this->lower = $value;
    }

    /**
     * Set the upper value of the range.
     *
     * @param mixed $value
     *
     * @api
     */
    public function setUpper($value)
    {
        $this->upper = $value;
    }
}
