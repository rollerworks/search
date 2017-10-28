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

use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionOptimizer;

/**
 * ChainOptimizer performs the registered optimizers in order of priority.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ChainOptimizer implements SearchConditionOptimizer
{
    /**
     * @var array<SearchConditionOptimizerInterface[]>
     */
    private $optimizers = [];

    /**
     * Creates a new ChainOptimizer with the build-in
     * optimizers already registered.
     *
     * @return ChainOptimizer
     */
    public static function create(): ChainOptimizer
    {
        $optimizer = new self();
        $optimizer->addOptimizer(new DuplicateRemover());
        $optimizer->addOptimizer(new ValuesToRange());
        $optimizer->addOptimizer(new RangeOptimizer());

        return $optimizer;
    }

    /**
     * @param SearchConditionOptimizer $optimizer
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function addOptimizer(SearchConditionOptimizer $optimizer)
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
    public function process(SearchCondition $condition): void
    {
        krsort($this->optimizers, SORT_NUMERIC);

        foreach ($this->optimizers as $optimizers) {
            /** @var SearchConditionOptimizer[] $optimizers */
            foreach ($optimizers as $optimizer) {
                $optimizer->process($condition);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return 0;
    }
}
