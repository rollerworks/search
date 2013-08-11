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

/**
 * Compare value structure.
 */
class Compare extends SingleValue
{
    /**
     * Comparison operator.
     *
     * @var string
     */
    protected $operator;

    /**
     * Constructor.
     *
     * @param mixed  $value
     * @param string $operator
     * @param mixed  $originalValue
     *
     * @throws \InvalidArgumentException When the operator is invalid
     */
    public function __construct($value, $operator, $originalValue = null)
    {
        parent::__construct($value, $originalValue);

        if (!in_array($operator, array('>=', '<=', '<>', '<', '>'))) {
            throw new \InvalidArgumentException(sprintf('Unknown operator "%s".', $operator));
        }

        $this->operator = $operator;
    }

    /**
     * Gets the comparison operator.
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }
}
