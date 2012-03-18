<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

    protected $higher;

    /**
     * Original lower range value
     *
     * @var string|float|integer
     */
    protected $originalLower;

    /**
     * Original higher range value
     *
     * @var string|float|integer
     */
    protected $originalHigher;

    /**
     * Constructor
     *
     * @param mixed $lower
     * @param mixed $higher
     * @param mixed $originalLower
     * @param mixed $originalHigher
     *
     * @api
     */
    public function __construct($lower, $higher, $originalLower = null, $originalHigher = null)
    {
        if (!is_string($lower) && !is_float($lower) && !is_integer($lower)) {
            throw new \UnexpectedValueException('Lower value type for is not accepted, only string, float and integer are accepted.');
        }
        elseif (!is_string($higher) && !is_float($higher) && !is_integer($higher)) {
            throw new \UnexpectedValueException('Higher value type for is not accepted, only string, float and integer are accepted.');
        }

        $this->lower  = $lower;
        $this->higher = $higher;

        if (is_null($originalLower)) {
            $originalLower = $lower;
        }

        if (is_null($originalHigher)) {
            $originalHigher = $higher;
        }

        $this->originalLower  = $originalLower;
        $this->originalHigher = $originalHigher;
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
     * Get the higher value of the range
     *
     * @return string|float|integer
     *
     * @api
     */
    public function getHigher()
    {
        return $this->higher;
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
     * Get the original higher value of the range
     *
     * @return string|float|integer
     *
     * @api
     */
    public function getOriginalHigher()
    {
        return $this->originalHigher;
    }

    /**
     * Set the lower value of the range
     *
     * @param string|float|integer $pmValue
     *
     * @api
     */
    public function setLower($pmValue)
    {
        if (!is_string($pmValue) && !is_float($pmValue) && !is_integer($pmValue)) {
            throw new \UnexpectedValueException('Lower value type for is not accepted, only string, float and integer are accepted.');
        }

        $this->lower = $pmValue;
    }

    /**
     * Set the higher value of the range
     *
     * @param string|float|integer $pmValue
     *
     * @api
     */
    public function setHigher($pmValue)
    {
        if (!is_string($pmValue) && !is_float($pmValue) && !is_integer($pmValue)) {
            throw new \UnexpectedValueException('Higher value type for is not accepted, only string, float and integer are accepted.');
        }

        $this->higher = $pmValue;
    }
}