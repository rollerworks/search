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

namespace Rollerworks\Component\Search\Tests\ConditionOptimizer;

use Rollerworks\Component\Search\ConditionOptimizer\ValuesToRange;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\SearchConditionOptimizerTestCase;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\Range;

/**
 * @internal
 */
final class ValuesToRangeTest extends SearchConditionOptimizerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->optimizer = new ValuesToRange();
    }

    /**
     * @test
     */
    public function it_converts_single_proceeding_values_to_ranges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addSimpleValue(1)
                ->addSimpleValue(2)
                ->addSimpleValue(3)
                ->addSimpleValue(4)
                ->addSimpleValue(5)
                ->addSimpleValue(10)
                ->addSimpleValue(7)
                ->addSimpleValue(12)
                ->addSimpleValue(13)
                ->addSimpleValue(14)
                ->addSimpleValue(15)
                // edge-case where first non-previous increment, is the start of a new increment
                ->addSimpleValue(17)
                ->addSimpleValue(18)
                ->addSimpleValue(19)
                ->addSimpleValue(20)

            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addSimpleValue(10)
                ->addSimpleValue(7)
                ->add(new Range(1, 5))
                ->add(new Range(12, 15))
                ->add(new Range(17, 20))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_converts_excluded_proceeding_values_to_ranges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedSimpleValue(1)
                ->addExcludedSimpleValue(2)
                ->addExcludedSimpleValue(3)
                ->addExcludedSimpleValue(4)
                ->addExcludedSimpleValue(5)
                ->addExcludedSimpleValue(10)
                ->addExcludedSimpleValue(7)
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedSimpleValue(10)
                ->addExcludedSimpleValue(7)
                ->add(new ExcludedRange(1, 5))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }
}
