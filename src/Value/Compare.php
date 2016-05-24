<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Value;

/**
 * Compare value structure.
 */
final class Compare implements ValueHolder
{
    /**
     * Comparison operator.
     *
     * @var string
     */
    private $operator;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $viewValue;

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
        if (!in_array($operator, ['>=', '<=', '<>', '<', '>'], true)) {
            throw new \InvalidArgumentException(
                sprintf('Unknown operator "%s".', $operator)
            );
        }

        $this->value = $value;
        $this->viewValue = (string) (null !== $viewValue ? $viewValue : $value);
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

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getViewValue()
    {
        return $this->viewValue;
    }
}
