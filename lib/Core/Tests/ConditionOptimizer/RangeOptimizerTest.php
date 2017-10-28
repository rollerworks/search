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

use Rollerworks\Component\Search\ConditionOptimizer\RangeOptimizer;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\SearchConditionOptimizerTestCase;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\Range;

/**
 * @internal
 */
final class RangeOptimizerTest extends SearchConditionOptimizerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->optimizer = new RangeOptimizer();
    }

    /**
     * @test
     */
    public function it_removes_singleValues_overlapping_in_ranges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addSimpleValue(90)
                ->addSimpleValue(21)
                ->addSimpleValue(15) // overlapping in ranges[0]
                ->addSimpleValue(65) // overlapping in ranges[2]
                ->addSimpleValue(40)
                ->addSimpleValue(1) // this is overlapping, but the range lower-bound is exclusive
                ->addSimpleValue(2) // overlapping in ranges[3]

                ->add(new Range(11, 20))
                ->add(new Range(25, 30))
                ->add(new Range(50, 70))
                ->add(new Range(1, 10, false))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addSimpleValue(90)
                ->addSimpleValue(21)
                ->addSimpleValue(40)
                ->addSimpleValue(1) // this is overlapping, but the range lower-bound is exclusive

                ->add(new Range(11, 20))
                ->add(new Range(25, 30))
                ->add(new Range(50, 70))
                ->add(new Range(1, 10, false))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_removes_ranges_overlapping_in_ranges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new Range(1, 10))
                ->add(new Range(20, 30))
                ->add(new Range(2, 5)) // overlapping in 0
                ->add(new Range(3, 7)) // overlapping in 0
                ->add(new Range(50, 70))
                ->add(new Range(51, 71, true, false))  // overlapping with bounds
                ->add(new Range(51, 69)) // overlapping in 4
                ->add(new Range(52, 69)) // overlapping in 4
                ->add(new Range(51, 71)) // 8
                ->add(new Range(50, 71, false, false))
                ->add(new Range(51, 71, false)) // overlapping in 8

                // exclusive bounds overlapping
                ->add(new Range(100, 150, false)) // overlapping in 14
                ->add(new Range(101, 149)) // overlapping
                ->add(new Range(105, 148, false, false)) // overlapping
                ->add(new Range(99, 151, false, false))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new Range(1, 10))
                ->add(new Range(20, 30))
                ->add(new Range(50, 70))
                ->add(new Range(51, 71)) // 8
                ->add(new Range(50, 71, false, false))

                // exclusive bounds overlapping
                ->add(new Range(99, 151, false, false))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_removes_excludedValues_overlapping_in_excludedRanges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedSimpleValue(90)
                ->addExcludedSimpleValue(21)
                ->addExcludedSimpleValue(15) // overlapping in ranges[0]
                ->addExcludedSimpleValue(65) // overlapping in ranges[2]
                ->addExcludedSimpleValue(40)
                ->addExcludedSimpleValue(1) // this is overlapping, but the range lower-bound is exclusive

                ->add(new ExcludedRange(11, 20))
                ->add(new ExcludedRange(25, 30))
                ->add(new ExcludedRange(50, 70))
                ->add(new ExcludedRange(1, 10, false))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedSimpleValue(90)
                ->addExcludedSimpleValue(21)
                ->addExcludedSimpleValue(40)
                ->addExcludedSimpleValue(1) // this is overlapping, but the range lower-bound is exclusive

                ->add(new ExcludedRange(11, 20))
                ->add(new ExcludedRange(25, 30))
                ->add(new ExcludedRange(50, 70))
                ->add(new ExcludedRange(1, 10, false))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_removes_excludedRanges_overlapping_in_excludedRanges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new ExcludedRange(1, 10))
                ->add(new ExcludedRange(20, 30))
                ->add(new ExcludedRange(2, 5)) // overlapping in 0
                ->add(new ExcludedRange(3, 7)) // overlapping in 0
                ->add(new ExcludedRange(50, 70))
                ->add(new ExcludedRange(51, 71, true, false))  // overlapping with bounds
                ->add(new ExcludedRange(51, 69)) // overlapping in 4
                ->add(new ExcludedRange(52, 69)) // overlapping in 4
                ->add(new ExcludedRange(51, 71)) // 8
                ->add(new ExcludedRange(50, 71, false, false))
                ->add(new ExcludedRange(51, 71, false)) // overlapping in 8

                // exclusive bounds overlapping
                ->add(new ExcludedRange(100, 150, false)) // overlapping in 14
                ->add(new ExcludedRange(101, 149)) // overlapping
                ->add(new ExcludedRange(105, 148, false, false)) // overlapping
                ->add(new ExcludedRange(99, 151, false, false))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new ExcludedRange(1, 10))
                ->add(new ExcludedRange(20, 30))
                ->add(new ExcludedRange(50, 70))
                ->add(new ExcludedRange(51, 71))
                ->add(new ExcludedRange(50, 71, false, false))

                // exclusive bounds overlapping
                ->add(new ExcludedRange(99, 151, false, false))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_merges_connected_ranges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new Range(10, 20))
                ->add(new Range(30, 40))
                ->add(new Range(20, 25))
                ->add(new Range(20, 28, false)) // this should not be changed as the bounds do not equal 1
                ->add(new Range(20, 26))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new Range(30, 40))
                ->add(new Range(20, 28, false)) // this should not be changed as the bounds do not equal 1
                ->add(new Range(10, 26))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_merges_connected_excludedRanges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new ExcludedRange(10, 20))
                ->add(new ExcludedRange(30, 40))
                ->add(new ExcludedRange(20, 25))
                ->add(new ExcludedRange(20, 28, false)) // this should not be changed as the bounds do not equal 1
                ->add(new ExcludedRange(20, 26))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new ExcludedRange(30, 40))
                ->add(new ExcludedRange(20, 28, false)) // this should not be changed as the bounds do not equal 1
                ->add(new ExcludedRange(10, 26))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }
}
