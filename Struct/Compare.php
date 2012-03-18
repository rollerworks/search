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