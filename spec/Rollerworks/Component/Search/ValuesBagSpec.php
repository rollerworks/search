<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesError;

class ValuesBagSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\ValuesBag');
    }

    public function it_should_not_contain_single_values_by_default()
    {
        $this->getSingleValues()->shouldReturn(array());
        $this->hasSingleValues()->shouldReturn(false);
    }

    public function it_should_allow_adding_single_values()
    {
        $this->addSingleValue(new SingleValue('value'));
        $this->addSingleValue(new SingleValue('value2'));

        $this->getSingleValues()->shouldBeLike(array(new SingleValue('value'), new SingleValue('value2')));
        $this->hasSingleValues()->shouldReturn(true);
    }

    public function it_should_allow_removing_single_values()
    {
        $this->addSingleValue(new SingleValue('value'));
        $this->addSingleValue(new SingleValue('value2'));

        $this->removeSingleValue(0);

        $this->getSingleValues()->shouldBeLike(array(1 => new SingleValue('value2')));
        $this->hasSingleValues()->shouldReturn(true);
    }

    public function it_should_not_contain_excluded_values_by_default()
    {
        $this->getExcludedValues()->shouldReturn(array());
        $this->hasExcludedValues()->shouldReturn(false);
    }

    public function it_should_allow_adding_excluded_values()
    {
        $this->addExcludedValue(new SingleValue('value'));
        $this->addExcludedValue(new SingleValue('value2'));

        $this->getExcludedValues()->shouldBeLike(array(new SingleValue('value'), new SingleValue('value2')));
        $this->hasExcludedValues()->shouldReturn(true);
    }

    public function it_should_allow_removing_excluded_values()
    {
        $this->addExcludedValue(new SingleValue('value'));
        $this->addExcludedValue(new SingleValue('value2'));

        $this->removeExcludedValue(0);

        $this->getExcludedValues()->shouldBeLike(array(1 => new SingleValue('value2')));
        $this->hasExcludedValues()->shouldReturn(true);
    }

    public function it_should_not_contain_ranges_by_default()
    {
        $this->getRanges()->shouldReturn(array());
        $this->hasRanges()->shouldReturn(false);
    }

    public function it_should_allow_adding_ranges()
    {
        $this->addRange(new Range(1, 10));
        $this->addRange(new Range(11, 20));

        $this->getRanges()->shouldBeLike(array(new Range(1, 10), new Range(11, 20)));
    }

    public function it_should_allow_removing_ranges()
    {
        $this->addRange(new Range(1, 10));
        $this->addRange(new Range(11, 20));

        $this->removeRange(0);

        $this->getRanges()->shouldBeLike(array(1 => new Range(11, 20)));
    }

    public function it_should_not_contain_excluded_ranges_by_default()
    {
        $this->getExcludedRanges()->shouldReturn(array());
        $this->hasExcludedRanges()->shouldReturn(false);
    }

    public function it_should_allow_adding_excluded_ranges()
    {
        $this->addExcludedRange(new Range(1, 10));
        $this->addExcludedRange(new Range(11, 20));

        $this->getExcludedRanges()->shouldBeLike(array(new Range(1, 10), new Range(11, 20)));
    }

    public function it_should_allow_removing_excluded_ranges()
    {
        $this->addExcludedRange(new Range(1, 10));
        $this->addExcludedRange(new Range(11, 20));

        $this->removeExcludedRange(0);

        $this->getExcludedRanges()->shouldBeLike(array(1 => new Range(11, 20)));
    }

    public function it_should_not_contain_comparisons_by_default()
    {
        $this->getComparisons()->shouldReturn(array());
        $this->hasComparisons()->shouldReturn(false);
    }

    public function it_should_allow_adding_comparisons()
    {
        $this->addComparison(new Compare(10, '>'));
        $this->addComparison(new Compare(5, '>'));

        $this->getComparisons()->shouldBeLike(array(new Compare(10, '>'), new Compare(5, '>')));
        $this->hasComparisons()->shouldReturn(true);
    }

    public function it_should_allow_removing_comparisons()
    {
        $this->addComparison(new Compare(10, '>'));
        $this->addComparison(new Compare(5, '>'));

        $this->removeComparison(0);

        $this->getComparisons()->shouldBeLike(array(1 => new Compare(5, '>')));
        $this->hasComparisons()->shouldReturn(true);
    }

    public function it_should_not_contain_pattern_matchers_by_default()
    {
        $this->getPatternMatchers()->shouldReturn(array());
        $this->hasPatternMatchers()->shouldReturn(false);
    }

    public function it_should_allow_pattern_matchers()
    {
        $this->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS));
        $this->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH));

        $this->getPatternMatchers()->shouldBeLike(array(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS), new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH)));
        $this->hasPatternMatchers()->shouldReturn(true);
    }

    public function it_should_allow_removing_pattern_matchers()
    {
        $this->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS));
        $this->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH));

        $this->removePatternMatch(0);

        $this->getPatternMatchers()->shouldBeLike(array(1 => new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH)));
        $this->hasPatternMatchers()->shouldReturn(true);
    }

    public function it_should_not_have_error_by_default()
    {
        $this->hasErrors()->shouldReturn(false);
        $this->getErrors()->shouldReturn(array());
    }

    public function it_should_allow_adding_errors(ValuesError $error)
    {
        $this->addError($error);
        $this->hasErrors()->shouldReturn(true);
        $this->getErrors()->shouldReturn(array($error));
    }
}
