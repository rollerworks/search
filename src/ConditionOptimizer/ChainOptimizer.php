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

use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\SearchConditionOptimizerInterface;

/**
 * ChainOptimizer performs the registered optimizers in order of priority.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ChainOptimizer implements SearchConditionOptimizerInterface
{
    /**
     * @var array<SearchConditionOptimizerInterface[]>
     */
    private $optimizers = [];

    /**
     * @param SearchConditionOptimizerInterface $optimizer
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function addOptimizer(SearchConditionOptimizerInterface $optimizer)
    {
        // Ensure we got no end-less loops
        if ($optimizer === $this) {
            throw new \InvalidArgumentException(
                'Unable to add optimizer to its own chain.'
            );
        }

        if (!isset($this->optimizers[$optimizer->getPriority()])) {
            $this->optimizers[$optimizer->getPriority()] = [];
        }

        $this->optimizers[$optimizer->getPriority()][] = $optimizer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(SearchConditionInterface $condition)
    {
        if ($condition->getValuesGroup()->hasErrors(true)) {
            return;
        }

        krsort($this->optimizers, SORT_NUMERIC);

        foreach ($this->optimizers as $optimizers) {
            /** @var SearchConditionOptimizerInterface[] $optimizers */
            foreach ($optimizers as $optimizer) {
                $optimizer->process($condition);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 0;
    }
}
