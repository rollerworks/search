<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\ValuesBag;

class ValuesBagSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\ValuesBag');
    }

    function it_should_have_single_values()
    {
        $this->getSingleValues()->shouldReturn(array());
        $this->hasSingleValues()->shouldReturn(false);
    }

    function it_should_allow_adding_single_values()
    {
        $this->addSingleValue('value');
        $this->addSingleValue('value2');

        $this->getSingleValues()->shouldReturn(array('value', 'value2'));
        $this->hasSingleValues()->shouldReturn(true);
    }

    function it_should_allow_replacing_single_values()
    {
        $this->addSingleValue('value');
        $this->addSingleValue('value2');

        $this->replaceSingleValue(1, 'value3');

        $this->getSingleValues()->shouldReturn(array('value', 'value3'));
        $this->hasSingleValues()->shouldReturn(true);
    }

    function it_should_allow_removing_single_values()
    {
        $this->addSingleValue('value');
        $this->addSingleValue('value2');

        $this->removeSingleValue(0);

        $this->getSingleValues()->shouldReturn(array(1 => 'value2'));
        $this->hasSingleValues()->shouldReturn(true);
    }

    function it_should_have_excluded_values()
    {
        $this->getExcludedValues()->shouldReturn(array());
        $this->hasExcludedValues()->shouldReturn(false);
    }

    function it_should_allow_adding_excluded_values()
    {
        $this->addExcludedValue('value1');
        $this->addExcludedValue('value2');

        $this->getExcludedValues()->shouldReturn(array('value1', 'value2'));
        $this->hasExcludedValues()->shouldReturn(true);
    }

    function it_should_allow_replacing_excluded_values()
    {
        $this->addExcludedValue('value1');
        $this->addExcludedValue('value2');

        $this->replaceExcludedValue(1, 'value3');

        $this->getExcludedValues()->shouldReturn(array('value1', 'value3'));
        $this->hasExcludedValues()->shouldReturn(true);
    }

    function it_should_allow_removing_excluded_values()
    {
        $this->addExcludedValue('value1');
        $this->addExcludedValue('value2');

        $this->removeExcludedValue(0);

        $this->getExcludedValues()->shouldReturn(array(1 => 'value2'));
        $this->hasExcludedValues()->shouldReturn(true);
    }

    function it_should_have_ranges()
    {
        $this->getRanges()->shouldReturn(array());
        $this->hasRanges()->shouldReturn(false);
    }

    function it_should_allow_adding_ranges()
    {
        $this->addRange(1, 10);
        $this->addRange(1, 10, false, false);

        $this->getRanges()->shouldReturn(array(
            array('lower' => 1, 'upper' => 10, 'lower_inclusive' => true, 'upper_inclusive' => true),
            array('lower' => 1, 'upper' => 10, 'lower_inclusive' => false, 'upper_inclusive' => false)
        ));
    }

    function it_should_allow_replacing_ranges()
    {
        $this->addRange(1, 10);
        $this->addRange(1, 10, false, false);

        $this->replaceRange(1, 1, 10, true, false);

        $this->getRanges()->shouldReturn(array(
            array('lower' => 1, 'upper' => 10, 'lower_inclusive' => true, 'upper_inclusive' => true),
            array('lower' => 1, 'upper' => 10, 'lower_inclusive' => true, 'upper_inclusive' => false)
        ));
    }

    function it_should_allow_removing_ranges()
    {
        $this->addRange(1, 10);
        $this->addRange(1, 10, false, false);

        $this->removeRange(0);

        $this->getRanges()->shouldReturn(array(
            1 => array('lower' => 1, 'upper' => 10, 'lower_inclusive' => false, 'upper_inclusive' => false)
        ));
    }

    function it_should_have_excluded_ranges()
    {
        $this->getExcludedRanges()->shouldReturn(array());
        $this->hasExcludedRanges()->shouldReturn(false);
    }

    function it_should_allow_adding_excluded_ranges()
    {
        $this->addExcludedRange(1, 10);
        $this->addExcludedRange(1, 10, false, false);

        $this->getExcludedRanges()->shouldReturn(array(
            array('lower' => 1, 'upper' => 10, 'lower_inclusive' => true, 'upper_inclusive' => true),
            array('lower' => 1, 'upper' => 10, 'lower_inclusive' => false, 'upper_inclusive' => false)
        ));
    }

    function it_should_allow_replacing_excluded_ranges()
    {
        $this->addExcludedRange(1, 10);
        $this->addExcludedRange(1, 10, false, false);


        $this->replaceExcludedRange(1, 1, 10, true, false);

        $this->getExcludedRanges()->shouldReturn(array(
            array('lower' => 1, 'upper' => 10, 'lower_inclusive' => true, 'upper_inclusive' => true),
            array('lower' => 1, 'upper' => 10, 'lower_inclusive' => true, 'upper_inclusive' => false)
        ));
    }

    function it_should_allow_removing_excluded_ranges()
    {
        $this->addExcludedRange(1, 10);
        $this->addExcludedRange(1, 10, false, false);

        $this->removeExcludedRange(0);

        $this->getExcludedRanges()->shouldReturn(array(
            1 => array('lower' => 1, 'upper' => 10, 'lower_inclusive' => false, 'upper_inclusive' => false)
        ));
    }

    function it_should_have_comparisons()
    {
        $this->getComparisons()->shouldReturn(array());
        $this->hasComparisons()->shouldReturn(false);
    }

    function it_should_allow_adding_comparisons()
    {
        $this->addComparison(10, '>');
        $this->addComparison(5, '<');

        $this->getComparisons()->shouldReturn(array(array('value' => 10, 'operator' => '>'), array('value' => 5, 'operator' => '<')));
        $this->hasComparisons()->shouldReturn(true);
    }

    function it_should_allow_replacing_comparisons()
    {
        $this->addComparison(10, '>');
        $this->addComparison(5, '<');

        $this->replaceComparison(1, 2, '<=');

        $this->getComparisons()->shouldReturn(array(array('value' => 10, 'operator' => '>'), array('value' => 2, 'operator' => '<=')));
        $this->hasComparisons()->shouldReturn(true);
    }

    function it_should_allow_removing_comparisons()
    {
        $this->addComparison(10, '>');
        $this->addComparison(5, '<');

        $this->removeComparison(0);

        $this->getComparisons()->shouldReturn(array(1 => array('value' => 5, 'operator' => '<')));
        $this->hasComparisons()->shouldReturn(true);
    }

    function it_should_have_pattern_matchers()
    {
        $this->getPatternMatch()->shouldReturn(array());
        $this->hasPatternMatch()->shouldReturn(false);
    }

    function it_should_allow_pattern_matchers()
    {
        $this->addPatternMatch('foo', ValuesBag::PATTERN_CONTAINS);
        $this->addPatternMatch('bar', ValuesBag::PATTERN_ENDS_WITH);

        $this->getPatternMatch()->shouldReturn(array(array('value' => 'foo', 'type' => ValuesBag::PATTERN_CONTAINS), array('value' => 'bar', 'type' => ValuesBag::PATTERN_ENDS_WITH)));
        $this->hasPatternMatch()->shouldReturn(true);
    }

    function it_should_allow_replacing_pattern_matchers()
    {
        $this->addPatternMatch('foo', ValuesBag::PATTERN_CONTAINS);
        $this->addPatternMatch('bar', ValuesBag::PATTERN_STARTS_WITH);

        $this->replacePatternMatch(1, 'bar', ValuesBag::PATTERN_STARTS_WITH);

        $this->getPatternMatch()->shouldReturn(array(array('value' => 'foo', 'type' => ValuesBag::PATTERN_CONTAINS), array('value' => 'bar', 'type' => ValuesBag::PATTERN_STARTS_WITH)));
        $this->hasPatternMatch()->shouldReturn(true);
    }

    function it_should_allow_removing_pattern_matchers()
    {
        $this->addPatternMatch('foo', ValuesBag::PATTERN_CONTAINS);
        $this->addPatternMatch('bar', ValuesBag::PATTERN_ENDS_WITH);

        $this->removePatternMatch(0);

        $this->getPatternMatch()->shouldReturn(array(1 => array('value' => 'bar', 'type' => ValuesBag::PATTERN_ENDS_WITH)));
        $this->hasPatternMatch()->shouldReturn(true);
    }

    function it_should_have_violations()
    {
        $this->hasViolations()->shouldReturn(false);
        $this->getViolations()->shouldReturn(array());
    }

    /**
     * @param \Symfony\Component\Validator\ConstraintViolationInterface $constraint
     */
    function it_should_allow_setting_violations($constraint)
    {
        $this->setViolations(array($constraint));
        $this->hasViolations()->shouldReturn(true);
    }

    function it_should_allow_unsetting_violations()
    {
        $this->setViolations(array());
        $this->hasViolations()->shouldReturn(false);
    }
}
