<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

/**
 * SearchCondition optimizer interface.
 *
 * This interface needs to be implemented by all search condition optimizers.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SearchConditionOptimizerInterface
{
    /**
     * Optimizes a {@link \Rollerworks\Component\Search\SearchConditionInterface} instance.
     *
     * Optimizing may remove duplicated values, normalize overlapping values, etc.
     *
     * If the search condition has errors the optimizer is should
     * ignore the condition and do nothing.
     *
     * @param SearchConditionInterface $condition
     */
    public function process(SearchConditionInterface $condition);

    /**
     * Priority of the optimizer.
     *
     * Must return value between -10 and 10.
     *
     * @return int
     */
    public function getPriority();
}
