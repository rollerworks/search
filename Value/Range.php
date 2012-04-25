<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Value;

/**
 * Range value
 */
class Range
{
    protected $lower;

    protected $upper;

    /**
     * Original lower range value
     *
     * @var mixed
     */
    protected $originalLower;

    /**
     * Original upper range value
     *
     * @var mixed
     */
    protected $originalUpper;

    /**
     * Constructor
     *
     * @param mixed $lower
     * @param mixed $upper
     * @param mixed $originalLower
     * @param mixed $originalUpper
     *
     * @api
     *
     * @throws \UnexpectedValueException
     */
    public function __construct($lower, $upper, $originalLower = null, $originalUpper = null)
    {
        if (!is_string($lower) && !is_float($lower) && !is_integer($lower)) {
            throw new \UnexpectedValueException('Lower value for is not accepted, only string, float and integer are accepted.');
        }
        elseif (!is_string($upper) && !is_float($upper) && !is_integer($upper)) {
            throw new \UnexpectedValueException('Upper value for is not accepted, only string, float and integer are accepted.');
        }

        $this->lower  = $lower;
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
     * Get the lower value of the range
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
     * Get the upper value of the range
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
     * Get the original lower value of the range
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
     * Get the original upper value of the range
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
     * Set the lower value of the range
     *
     * @param mixed $value
     *
     * @api
     *
     * @throws \UnexpectedValueException
     */
    public function setLower($value)
    {
        if (!is_string($value) && !is_float($value) && !is_integer($value)) {
            throw new \UnexpectedValueException('Lower value type for is not accepted, only string, float and integer are accepted.');
        }

        $this->lower = $value;
    }

    /**
     * Set the upper value of the range
     *
     * @param mixed $value
     *
     * @api
     *
     * @throws \UnexpectedValueException
     */
    public function setUpper($value)
    {
        if (!is_string($value) && !is_float($value) && !is_integer($value)) {
            throw new \UnexpectedValueException('Upper value type for is not accepted, only string, float and integer are accepted.');
        }

        $this->upper = $value;
    }
}