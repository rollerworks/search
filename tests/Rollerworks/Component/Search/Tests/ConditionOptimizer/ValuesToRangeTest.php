<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\ConditionOptimizer;

use Rollerworks\Component\Search\ConditionOptimizer\ValuesToRange;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\FormatterTestCase;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;

final class ValuesToRangeTest extends FormatterTestCase
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
                ->addSingleValue(new SingleValue(1))
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(3))
                ->addSingleValue(new SingleValue(4))
                ->addSingleValue(new SingleValue(5))
                ->addSingleValue(new SingleValue(10))
                ->addSingleValue(new SingleValue(7))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag
            ->addSingleValue(new SingleValue(10))
            ->addSingleValue(new SingleValue(7))
            ->addRange(new Range(1, 5))
        ;

        $this->assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('id'));
    }

    public function it_converts_excluded_proceeding_values_to_ranges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedValue(new SingleValue(1))
                ->addExcludedValue(new SingleValue(2))
                ->addExcludedValue(new SingleValue(3))
                ->addExcludedValue(new SingleValue(4))
                ->addExcludedValue(new SingleValue(5))
                ->addExcludedValue(new SingleValue(10))
                ->addExcludedValue(new SingleValue(7))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag
            ->addExcludedValue(new SingleValue(10))
            ->addExcludedValue(new SingleValue(7))
            ->addExcludedRange(new Range(1, 5))
        ;

        $this->assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('id'));
    }
}
