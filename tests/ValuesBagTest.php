<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests;

use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;

final class ValuesBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_contains_no_single_values_when_initialized()
    {
        $valuesBag = new ValuesBag();

        $this->assertEquals([], $valuesBag->getSingleValues());
        $this->assertFalse($valuesBag->hasSingleValues());
    }

    /**
     * @test
     */
    public function it_allows_adding_single_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addSingleValue(new SingleValue('value'));
        $valuesBag->addSingleValue(new SingleValue('value2'));

        $this->assertTrue($valuesBag->hasSingleValues());
        $this->assertEquals([new SingleValue('value'), new SingleValue('value2')], $valuesBag->getSingleValues());
    }

    /**
     * @test
     */
    public function it_allows_removing_single_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addSingleValue(new SingleValue('value'));
        $valuesBag->addSingleValue(new SingleValue('value2'));

        $valuesBag->removeSingleValue(0);

        $this->assertTrue($valuesBag->hasSingleValues());
        $this->assertEquals([1 => new SingleValue('value2')], $valuesBag->getSingleValues());
    }

    /**
     * @test
     */
    public function it_contains_no_excluded_values_when_initialized()
    {
        $valuesBag = new ValuesBag();

        $this->assertFalse($valuesBag->hasExcludedValues());
        $this->assertEquals([], $valuesBag->getExcludedValues());
    }

    /**
     * @test
     */
    public function it_allows_adding_excluded_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedValue(new SingleValue('value'));
        $valuesBag->addExcludedValue(new SingleValue('value2'));

        $this->assertTrue($valuesBag->hasExcludedValues());
        $this->assertEquals([new SingleValue('value'), new SingleValue('value2')], $valuesBag->getExcludedValues());
    }

    /**
     * @test
     */
    public function it_allows_removing_excluded_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedValue(new SingleValue('value'));
        $valuesBag->addExcludedValue(new SingleValue('value2'));

        $valuesBag->removeExcludedValue(0);

        $this->assertTrue($valuesBag->hasExcludedValues());
        $this->assertEquals([1 => new SingleValue('value2')], $valuesBag->getExcludedValues());
    }

    /**
     * @test
     */
    public function it_contains_ranges_by_default()
    {
        $valuesBag = new ValuesBag();

        $this->assertEquals([], $valuesBag->getRanges());
        $this->assertFalse($valuesBag->hasRanges());
    }

    /**
     * @test
     */
    public function it_allows_adding_ranges()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addRange(new Range(1, 10));
        $valuesBag->addRange(new Range(11, 20));

        $this->assertEquals([new Range(1, 10), new Range(11, 20)], $valuesBag->getRanges());
    }

    /**
     * @test
     */
    public function it_allows_removing_ranges()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addRange(new Range(1, 10));
        $valuesBag->addRange(new Range(11, 20));

        $valuesBag->removeRange(0);

        $this->assertEquals([1 => new Range(11, 20)], $valuesBag->getRanges());
    }

    /**
     * @test
     */
    public function it_contains_no_excluded_ranges_when_initialized()
    {
        $valuesBag = new ValuesBag();

        $this->assertEquals([], $valuesBag->getExcludedRanges());
        $this->assertFalse($valuesBag->hasExcludedRanges());
    }

    /**
     * @test
     */
    public function it_should_allow_adding_excluded_ranges()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedRange(new Range(1, 10));
        $valuesBag->addExcludedRange(new Range(11, 20));

        $this->assertEquals([new Range(1, 10), new Range(11, 20)], $valuesBag->getExcludedRanges());
    }

    /**
     * @test
     */
    public function it_allows_removing_excluded_ranges()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedRange(new Range(1, 10));
        $valuesBag->addExcludedRange(new Range(11, 20));

        $valuesBag->removeExcludedRange(0);

        $this->assertEquals([1 => new Range(11, 20)], $valuesBag->getExcludedRanges());
    }

    /**
     * @test
     */
    public function it_contains_no_comparisons_when_initialized()
    {
        $valuesBag = new ValuesBag();

        $this->assertFalse($valuesBag->hasComparisons());
        $this->assertEquals([], $valuesBag->getComparisons());
    }

    /**
     * @test
     */
    public function it_allows_adding_comparisons()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addComparison(new Compare(10, '>'));
        $valuesBag->addComparison(new Compare(5, '>'));

        $this->assertTrue($valuesBag->hasComparisons());
        $this->assertEquals([new Compare(10, '>'), new Compare(5, '>')], $valuesBag->getComparisons());
    }

    /**
     * @test
     */
    public function it_allows_removing_comparisons()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addComparison(new Compare(10, '>'));
        $valuesBag->addComparison(new Compare(5, '>'));

        $valuesBag->removeComparison(0);

        $this->assertTrue($valuesBag->hasComparisons());
        $this->assertEquals([1 => new Compare(5, '>')], $valuesBag->getComparisons());
    }

    /**
     * @test
     */
    public function it_contains_pattern_matchers_by_default()
    {
        $valuesBag = new ValuesBag();

        $this->assertFalse($valuesBag->hasPatternMatchers());
        $this->assertEquals([], $valuesBag->getPatternMatchers());
    }

    /**
     * @test
     */
    public function it_allows_pattern_matchers()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS));
        $valuesBag->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH));

        $this->assertTrue($valuesBag->hasPatternMatchers());
        $this->assertEquals(
            [
                new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS),
                new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH),
            ],
            $valuesBag->getPatternMatchers()
        );
    }

    /**
     * @test
     */
    public function it_allows_removing_pattern_matchers()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS));
        $valuesBag->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH));

        $valuesBag->removePatternMatch(0);

        $this->assertTrue($valuesBag->hasPatternMatchers());
        $this->assertEquals(
            [1 => new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH)],
            $valuesBag->getPatternMatchers()
        );
    }

    /**
     * @test
     */
    public function it_has_no_errors_when_initialized()
    {
        $valuesBag = new ValuesBag();

        $this->assertFalse($valuesBag->hasErrors());
        $this->assertEquals([], $valuesBag->getErrors());
    }

    /**
     * @test
     */
    public function it_allows_adding_errors()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addError($error = new ValuesError('ranges[0].lower', 'invalid'));

        $this->assertTrue($valuesBag->hasErrors());
        $this->assertEquals([$error->getHash() => $error], $valuesBag->getErrors());
    }

    /**
     * @test
     */
    public function it_allows_removing_errors()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addError($error = new ValuesError('ranges[0].lower', 'invalid'));

        $this->assertTrue($valuesBag->hasErrors());

        $valuesBag->removeError($error);

        $this->assertFalse($valuesBag->hasErrors());
    }
}
