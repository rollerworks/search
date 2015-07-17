<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\ConditionOptimizer;

use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValueComparisonInterface;

/**
 * The ValueSortCompare compares value for the uasort() function.
 *
 * This replaces the using of a Closure (which should not be created in a loop).
 *
 * @internal
 */
final class ValueSortCompare
{
    private $comparison;
    private $options;

    public function __construct(ValueComparisonInterface $comparison, array $options)
    {
        $this->comparison = $comparison;
        $this->options = $options;
    }

    public function __invoke(SingleValue $first, SingleValue $second)
    {
        $a = $first->getValue();
        $b = $second->getValue();

        if ($this->comparison->isEqual($a, $b, $this->options)) {
            return 0;
        }

        return $this->comparison->isLower($a, $b, $this->options) ? -1 : 1;
    }
}
