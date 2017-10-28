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

namespace Rollerworks\Component\Search\ConditionOptimizer;

use Rollerworks\Component\Search\ValueComparator;

/**
 * The ValueSortCompare compares value for the uasort() function.
 *
 * This replaces the using of a Closure (which should not be created in a loop).
 *
 * @internal
 */
final class ValueSortCompare
{
    private $comparator;
    private $options;

    public function __construct(ValueComparator $comparator, array $options)
    {
        $this->comparator = $comparator;
        $this->options = $options;
    }

    public function __invoke($a, $b): int
    {
        if ($this->comparator->isEqual($a, $b, $this->options)) {
            return 0;
        }

        return $this->comparator->isLower($a, $b, $this->options) ? -1 : 1;
    }
}
