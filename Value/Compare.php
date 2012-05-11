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
 * Compare value structure
 */
class Compare extends SingleValue
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
     * @throws \InvalidArgumentException When the operator is invalid
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
}
