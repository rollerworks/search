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
 * Loos-value filter structure
 */
class Value
{
    /**
     * The actual value
     *
     * @var string|integer|float
     */
    protected $value;

    /**
     * The actual value (copy)
     *
     * @var string|integer|float
     */
    protected $originalValue;

    /**
     * Constructor
     *
     * @param mixed $value
     * @param mixed $original Original value (only used in test cases)
     *
     * @api
     */
    public function __construct($value, $original = null)
    {
        if (!is_string($value) && !is_float($value) && !is_integer($value)) {
            throw new \UnexpectedValueException('Value type is not accepted, only string, float and integer are accepted.');
        }

        if (is_null($original)) {
            $original = $value;
        }

        $this->value         = $value;
        $this->originalValue = $original;
    }

    /**
     * Set/overwrite the singe value
     *
     * @param mixed $value
     *
     * @api
     */
    public function setValue($value)
    {
        if (!is_string($value) && !is_float($value) && !is_integer($value)) {
            throw new \UnexpectedValueException('Value type is not accepted, only string, float and integer are accepted.');
        }

        $this->value = $value;
    }

    /**
     * Get the single value
     *
     * @return mixed
     *
     * @api
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the original loose value
     *
     * @return mixed
     *
     * @api
     */
    public function getOriginalValue()
    {
        return $this->originalValue;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}