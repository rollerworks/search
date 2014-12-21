<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
     * @param mixed  $viewValue
     *
     * @throws \InvalidArgumentException When the operator is invalid
     */
    public function __construct($value, $operator, $viewValue = null)
    {
        parent::__construct($value, $viewValue);

        if (!in_array($operator, array('>=', '<=', '<>', '<', '>'), true)) {
            throw new \InvalidArgumentException(
                sprintf('Unknown operator "%s".', $operator)
            );
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
