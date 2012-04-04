<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Struct;

/**
 * Range filter structure
 */
class Range
{
    protected $lower;

    protected $upper;

    /**
     * Original lower range value
     *
     * @var string|float|integer
     */
    protected $originalLower;

    /**
     * Original upper range value
     *
     * @var string|float|integer
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
     * @return string|float|integer
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
     * @return string|float|integer
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
     * @return string|float|integer
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
     * @return string|float|integer
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
     * @param string|float|integer $value
     *
     * @api
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
     * @param string|float|integer $value
     *
     * @api
     */
    public function setUpper($value)
    {
        if (!is_string($value) && !is_float($value) && !is_integer($value)) {
            throw new \UnexpectedValueException('Upper value type for is not accepted, only string, float and integer are accepted.');
        }

        $this->upper = $value;
    }
}