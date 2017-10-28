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

use Rollerworks\Component\Search\ConditionOptimizer\DuplicateRemover;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\SearchConditionOptimizerTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;

/**
 * @internal
 */
final class DuplicateRemoverTest extends SearchConditionOptimizerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->optimizer = new DuplicateRemover();
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_singleValues()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addSimpleValue(10)
                ->addSimpleValue(3)
                ->addSimpleValue(3)
                ->addSimpleValue(4)
            ->end()

            ->group()
                ->field('id')
                    ->addSimpleValue(3)
                    ->addSimpleValue(3)
                ->end()
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addSimpleValue(10)
                ->addSimpleValue(3)
                ->addSimpleValue(4)
            ->end()
            ->group()
                ->field('id')
                    ->addSimpleValue(3)
                ->end()
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_excludedValues()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedSimpleValue(10)
                ->addExcludedSimpleValue(3)
                ->addExcludedSimpleValue(3)
                ->addExcludedSimpleValue(4)
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedSimpleValue(10)
                ->addExcludedSimpleValue(3)
                ->addExcludedSimpleValue(4)
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_ranges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new Range(10, 50))
                ->add(new Range(60, 70))
                ->add(new Range(60, 70)) // duplicate
                ->add(new Range(100, 300))
                ->add(new Range(200, 300, false, false))
                ->add(new Range(200, 300, false, false)) // duplicate
                // duplicated but inclusive differs
                ->add(new Range(100, 400, false))
                ->add(new Range(100, 400, true))
                ->add(new Range(1000, 3000, false, true))
                ->add(new Range(1000, 3000, true, false))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new Range(10, 50))
                ->add(new Range(60, 70))
                ->add(new Range(100, 300))
                ->add(new Range(200, 300, false, false))
                // duplicated but inclusive differs
                ->add(new Range(100, 400, false))
                ->add(new Range(100, 400, true))
                ->add(new Range(1000, 3000, false, true))
                ->add(new Range(1000, 3000, true, false))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_excludedRanges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new ExcludedRange(10, 50))
                ->add(new ExcludedRange(60, 70))
                ->add(new ExcludedRange(60, 70)) // duplicate
                ->add(new ExcludedRange(100, 300))
                ->add(new ExcludedRange(200, 300, false, false))
                ->add(new ExcludedRange(200, 300, false, false)) // duplicate
                // duplicated but inclusive differs
                ->add(new ExcludedRange(100, 400, false))
                ->add(new ExcludedRange(100, 400, true))
                ->add(new ExcludedRange(1000, 3000, false, true))
                ->add(new ExcludedRange(1000, 3000, true, false))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new ExcludedRange(10, 50))
                ->add(new ExcludedRange(60, 70))
                ->add(new ExcludedRange(100, 300))
                ->add(new ExcludedRange(200, 300, false, false))
                // duplicated but inclusive differs
                ->add(new ExcludedRange(100, 400, false))
                ->add(new ExcludedRange(100, 400, true))
                ->add(new ExcludedRange(1000, 3000, false, true))
                ->add(new ExcludedRange(1000, 3000, true, false))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_comparison()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new Compare(10, '>'))
                ->add(new Compare(20, '>'))
                ->add(new Compare(20, '>')) // duplicate
                ->add(new Compare(20, '<'))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->add(new Compare(10, '>'))
                ->add(new Compare(20, '>'))
                ->add(new Compare(20, '<'))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_matchers()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('name')
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS))
                ->add(new PatternMatch('bar', PatternMatch::PATTERN_CONTAINS))
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS)) // duplicate
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH))
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH)) // duplicate
                ->add(new PatternMatch('bla', PatternMatch::PATTERN_CONTAINS))
                ->add(new PatternMatch('who', PatternMatch::PATTERN_CONTAINS))
                ->add(new PatternMatch('who', PatternMatch::PATTERN_CONTAINS, true))
            ->end()
            ->getSearchCondition()
        ;

        $this->optimizer->process($condition);

        $expectedCondition = SearchConditionBuilder::create($this->fieldSet)
            ->field('name')
                ->add(new PatternMatch('bar', PatternMatch::PATTERN_CONTAINS))
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS))
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH))
                ->add(new PatternMatch('bla', PatternMatch::PATTERN_CONTAINS))
                ->add(new PatternMatch('who', PatternMatch::PATTERN_CONTAINS))
                ->add(new PatternMatch('who', PatternMatch::PATTERN_CONTAINS, true))
            ->end()
            ->getSearchCondition()
        ;

        self::assertConditionsEquals($expectedCondition, $condition);
    }
}
