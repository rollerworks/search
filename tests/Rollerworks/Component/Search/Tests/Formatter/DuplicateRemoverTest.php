<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Formatter;

use Rollerworks\Component\Search\Formatter\DuplicateRemover;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;

final class DuplicateRemoverTest extends FormatterTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->formatter = new DuplicateRemover();
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_singleValues()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addSingleValue(new SingleValue(10))
                ->addSingleValue(new SingleValue(3))
                ->addSingleValue(new SingleValue(3))
                ->addSingleValue(new SingleValue(4))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag->addSingleValue(new SingleValue(10));
        $expectedValuesBag->addSingleValue(new SingleValue(3));
        $expectedValuesBag->addSingleValue(new SingleValue(4));

        $this->assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('id'));
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_excludedValues()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedValue(new SingleValue(10))
                ->addExcludedValue(new SingleValue(3))
                ->addExcludedValue(new SingleValue(3))
                ->addExcludedValue(new SingleValue(4))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag
            ->addExcludedValue(new SingleValue(10))
            ->addExcludedValue(new SingleValue(3))
            ->addExcludedValue(new SingleValue(4))
        ;

        $this->assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('id'));
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_ranges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addRange(new Range(10, 50))
                ->addRange(new Range(60, 70))
                ->addRange(new Range(60, 70)) // duplicate
                ->addRange(new Range(100, 300))
                ->addRange(new Range(200, 300, false, false))
                ->addRange(new Range(200, 300, false, false)) // duplicate
                // duplicated but inclusive differs
                ->addRange(new Range(100, 400, false))
                ->addRange(new Range(100, 400, true))
                ->addRange(new Range(1000, 3000, false, true))
                ->addRange(new Range(1000, 3000, true, false))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag
            ->addRange(new Range(10, 50))
            ->addRange(new Range(60, 70))
            ->addRange(new Range(100, 300))
            ->addRange(new Range(200, 300, false, false))
            // duplicated but inclusive differs
            ->addRange(new Range(100, 400, false))
            ->addRange(new Range(100, 400, true))
            ->addRange(new Range(1000, 3000, false, true))
            ->addRange(new Range(1000, 3000, true, false))
        ;

        $this->assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('id'));
    }

    public function it_removes_all_duplicated_excludedRanges()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedRange(new Range(10, 50))
                ->addExcludedRange(new Range(60, 70))
                ->addExcludedRange(new Range(60, 70)) // duplicate
                ->addExcludedRange(new Range(100, 300))
                ->addExcludedRange(new Range(200, 300, false, false))
                ->addExcludedRange(new Range(200, 300, false, false)) // duplicate
                // duplicated but inclusive differs
                ->addExcludedRange(new Range(100, 400, false))
                ->addExcludedRange(new Range(100, 400, true))
                ->addExcludedRange(new Range(1000, 3000, false, true))
                ->addExcludedRange(new Range(1000, 3000, true, false))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag
            ->addExcludedRange(new Range(10, 50))
            ->addExcludedRange(new Range(60, 70))
            ->addExcludedRange(new Range(100, 300))
            ->addExcludedRange(new Range(200, 300, false, false))
            // duplicated but inclusive differs
            ->addExcludedRange(new Range(100, 400, false))
            ->addExcludedRange(new Range(100, 400, true))
            ->addExcludedRange(new Range(1000, 3000, false, true))
            ->addExcludedRange(new Range(1000, 3000, true, false))
        ;

        $this->assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('id'));
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_comparison()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addComparison(new Compare(10, '>'))
                ->addComparison(new Compare(20, '>'))
                ->addComparison(new Compare(20, '>')) // duplicate
                ->addComparison(new Compare(20, '<'))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag
            ->addComparison(new Compare(10, '>'))
            ->addComparison(new Compare(20, '>'))
            ->addComparison(new Compare(20, '<'))
        ;

        $this->assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('id'));
    }

    /**
     * @test
     */
    public function it_removes_all_duplicated_matchers()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('name')
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS))
                ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_CONTAINS))
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS)) // duplicate
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH))
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH)) // duplicate
                ->addPatternMatch(new PatternMatch('bla', PatternMatch::PATTERN_CONTAINS))
                ->addPatternMatch(new PatternMatch('who', PatternMatch::PATTERN_CONTAINS))
                ->addPatternMatch(new PatternMatch('who', PatternMatch::PATTERN_CONTAINS, true))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $expectedValuesBag = new ValuesBag();
        $expectedValuesBag
            ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_CONTAINS))
            ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS))
            ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH))
            ->addPatternMatch(new PatternMatch('bla', PatternMatch::PATTERN_CONTAINS))
            ->addPatternMatch(new PatternMatch('who', PatternMatch::PATTERN_CONTAINS))
            ->addPatternMatch(new PatternMatch('who', PatternMatch::PATTERN_CONTAINS, true))
        ;

        $this->assertValueBagsEqual($expectedValuesBag, $valuesGroup->getField('name'));
    }
}
