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
 * Compare filter structure
 */
class Compare extends Value
{
    /**
     * Comparison operator
     *
     * @var string
     */
    protected $operator;

    /**
     * Constructor
     *
     * @param mixed   $value
     * @param string  $operator
     * @param mixed   $originalValue
     *
     * @api
     */
    public function __construct($value, $operator, $originalValue = null)
    {
        parent::__construct($value, $originalValue);

        if (!in_array($operator, array('>=', '<=', '<>', '<', '>'))) {
            throw new \InvalidArgumentException('Unknown operator: ' . $operator);
        }

        $this->operator = $operator;
    }

    /**
     * Get the comparison operator
     *
     * @return string
     *
     * @api
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->operator . $this->value;
    }
}