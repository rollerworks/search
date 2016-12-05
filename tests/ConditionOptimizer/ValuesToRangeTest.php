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
use Rollerworks\Component\Search\Value\ValuesBag;

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
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag
            ->addSimpleValue(10)
            ->addSimpleValue(7)
            ->add(new Range(1, 5))
        ;

        self::assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('id'));
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
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag
            ->addExcludedSimpleValue(10)
            ->addExcludedSimpleValue(7)
            ->add(new ExcludedRange(1, 5))
        ;

        self::assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('id'));
    }
}
