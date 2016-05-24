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
use Rollerworks\Component\Search\Value\ExcludedRange;
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
    public function it_allows_adding_simple_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addSimpleValue('value');
        $valuesBag->addSimpleValue('value2');

        $this->assertTrue($valuesBag->hasSimpleValues());
        $this->assertEquals(['value', 'value2'], $valuesBag->getSimpleValues());

        // To be removed in 2.0
        $this->assertTrue($valuesBag->hasSingleValues());
        $this->assertEquals([new SingleValue('value'), new SingleValue('value2')], $valuesBag->getSingleValues());
    }

    /**
     * @test
     */
    public function it_allows_adding_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->add($val1 = new Range(10, 20));
        $valuesBag->add($val2 = new Compare(10, '>'));

        $this->assertEquals(2, $valuesBag->count());

        $this->assertTrue($valuesBag->has('Rollerworks\Component\Search\Value\Range'));
        $this->assertTrue($valuesBag->has('Rollerworks\Component\Search\Value\Compare'));
        $this->assertFalse($valuesBag->has('Rollerworks\Component\Search\Value\PatternMatch'));

        $this->assertEquals([$val1], $valuesBag->get('Rollerworks\Component\Search\Value\Range'));
        $this->assertEquals([$val2], $valuesBag->get('Rollerworks\Component\Search\Value\Compare'));
        $this->assertEquals([], $valuesBag->get('Rollerworks\Component\Search\Value\PatternMatch'));
    }

    /**
     * @test
     */
    public function it_allows_removing_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->add($val1 = new Range(10, 20));
        $valuesBag->add($val2 = new Compare(10, '>'));

        $this->assertEquals(2, $valuesBag->count());

        $valuesBag->remove('Rollerworks\Component\Search\Value\Range', 0);
        $valuesBag->remove('Rollerworks\Component\Search\Value\Range', 1); // should not decrease the counter

        $this->assertEquals(1, $valuesBag->count());

        $this->assertFalse($valuesBag->has('Rollerworks\Component\Search\Value\Range'));
        $this->assertEquals([], $valuesBag->get('Rollerworks\Component\Search\Value\Range'));
        $this->assertEquals([$val2], $valuesBag->get('Rollerworks\Component\Search\Value\Compare'));
    }

    /**
     * @test
     */
    public function it_allows_removing_simple_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addSimpleValue('value');
        $valuesBag->addSimpleValue('value2');

        $valuesBag->removeSimpleValue(0);

        $this->assertTrue($valuesBag->hasSimpleValues());
        $this->assertEquals([1 => 'value2'], $valuesBag->getSimpleValues());

        // To be removed in 2.0
        $this->assertTrue($valuesBag->hasSingleValues());
        $this->assertEquals([1 => new SingleValue('value2')], $valuesBag->getSingleValues());
    }

    /**
     * @test
     */
    public function it_allows_adding_excluded_simple_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedSimpleValue('value');
        $valuesBag->addExcludedSimpleValue('value2');

        $this->assertTrue($valuesBag->hasExcludedSimpleValues());
        $this->assertEquals(['value', 'value2'], $valuesBag->getExcludedSimpleValues());

        // To be removed in 2.0
        $this->assertTrue($valuesBag->hasExcludedValues());
        $this->assertEquals([new SingleValue('value'), new SingleValue('value2')], $valuesBag->getExcludedValues());
    }

    /**
     * @test
     */
    public function it_allows_removing_excluded_simple_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedSimpleValue('value');
        $valuesBag->addExcludedSimpleValue('value2');

        $valuesBag->removeExcludedSimpleValue(0);

        $this->assertTrue($valuesBag->hasExcludedSimpleValues());
        $this->assertEquals([1 => 'value2'], $valuesBag->getExcludedSimpleValues());

        // To be removed in 2.0
        $this->assertTrue($valuesBag->hasExcludedValues());
        $this->assertEquals([1 => new SingleValue('value2')], $valuesBag->getExcludedValues());
    }

    /**
     * @test
     * @group legacy
     */
    public function it_allows_adding_single_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addSingleValue(new SingleValue('value'));
        $valuesBag->addSimpleValue('value2');

        $this->assertTrue($valuesBag->hasSingleValues());
        $this->assertEquals([new SingleValue('value'), new SingleValue('value2')], $valuesBag->getSingleValues());
    }

    /**
     * @test
     * @group legacy
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
     * @group legacy
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
     * @group legacy
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
     * @group legacy
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
     * @group legacy
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
     * @group legacy
     */
    public function it_allows_adding_excluded_ranges()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedRange(new Range(1, 10));
        $valuesBag->addExcludedRange(new Range(11, 20));

        $this->assertEquals([new ExcludedRange(1, 10), new ExcludedRange(11, 20)], $valuesBag->getExcludedRanges());
    }

    /**
     * @test
     * @group legacy
     */
    public function it_allows_removing_excluded_ranges()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedRange(new Range(1, 10));
        $valuesBag->addExcludedRange(new ExcludedRange(11, 20));

        $valuesBag->removeExcludedRange(0);

        $this->assertEquals([1 => new ExcludedRange(11, 20)], $valuesBag->getExcludedRanges());
    }

    /**
     * @test
     * @group legacy
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
     * @group legacy
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
     * @group legacy
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
     * @group legacy
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
     * @group legacy
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
     * @group legacy
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
