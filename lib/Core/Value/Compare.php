<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Value;

final class Compare implements RequiresComparatorValueHolder
{
    private $operator;
    private $value;

    public const OPERATORS = ['>=', '<=', '<>', '<', '>'];

    /**
     * @param string $operator
     */
    public function __construct($value, $operator)
    {
        if (! \in_array($operator, self::OPERATORS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Unknown operator "%s".', $operator)
            );
        }

        $this->value = $value;
        $this->operator = $operator;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue()
    {
        return $this->value;
    }
}
