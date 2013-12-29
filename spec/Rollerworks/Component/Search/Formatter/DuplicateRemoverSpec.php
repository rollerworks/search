<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search\Formatter;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

class DuplicateRemoverSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Formatter\DuplicateRemover');
        $this->shouldImplement('Rollerworks\Component\Search\FormatterInterface');
    }

    public function it_removes_all_duplicated_singleValues(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::type('array'))->will(function ($args) {
            return $args[0] === $args[1];
        });

        $currentValues = array(
            new SingleValue('2013-08-25 00:00:00'),
            new SingleValue('2013-08-30 00:00:00'),
            new SingleValue('2013-08-25 00:00:00'),
            new SingleValue('2013-08-10 00:00:00'),
            new SingleValue('2013-08-25 00:00:00'),
            new SingleValue('2013-08-15 00:00:00'),
            new SingleValue('2013-08-15 00:00:00'),
        );

        $valuesBag->getSingleValues()->willReturn($currentValues);
        $valuesBag->hasSingleValues()->willReturn(true);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(false);
        $valuesBag->hasExcludedRanges()->willReturn(false);
        $valuesBag->hasComparisons()->willReturn(false);
        $valuesBag->hasPatternMatchers()->willReturn(false);

        $valuesBag->removeSingleValue(2)->shouldBeCalledTimes(2);
        $valuesBag->removeSingleValue(4)->shouldBeCalledTimes(2);
        $valuesBag->removeSingleValue(6)->shouldBeCalledTimes(2);

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getOptions()->willReturn(array());
        $dateField->getValueComparison()->willReturn($comparison);

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('date', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('date', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    public function it_removes_all_duplicated_excludedValues(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::type('array'))->will(function ($args) {
            return $args[0] === $args[1];
        });

        $currentValues = array(
            new SingleValue('2013-08-25 00:00:00'),
            new SingleValue('2013-08-30 00:00:00'),
            new SingleValue('2013-08-25 00:00:00'),
            new SingleValue('2013-08-10 00:00:00'),
            new SingleValue('2013-08-25 00:00:00')
        );

        $valuesBag->getExcludedValues()->willReturn($currentValues);
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(true);
        $valuesBag->hasRanges()->willReturn(false);
        $valuesBag->hasExcludedRanges()->willReturn(false);
        $valuesBag->hasComparisons()->willReturn(false);
        $valuesBag->hasPatternMatchers()->willReturn(false);

        $valuesBag->removeExcludedValue(2)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedValue(4)->shouldBeCalledTimes(2);

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getOptions()->willReturn(array());
        $dateField->getValueComparison()->willReturn($comparison);

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('date', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('date', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    public function it_removes_all_duplicated_ranges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::type('array'))->will(function ($args) {
            return $args[0] === $args[1];
        });

        $currentValues = array(
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00'),
            new Range('2013-09-25 00:00:00', '2013-09-30 00:00:00'),
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00'),
            new Range('2013-10-25 00:00:00', '2013-10-30 00:00:00'),
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00'),
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00', false), // duplicated but exclusive
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00', true, false), // duplicated but exclusive
        );

        $valuesBag->getRanges()->willReturn($currentValues);
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(true);
        $valuesBag->hasExcludedRanges()->willReturn(false);
        $valuesBag->hasComparisons()->willReturn(false);
        $valuesBag->hasPatternMatchers()->willReturn(false);

        $valuesBag->removeRange(2)->shouldBeCalledTimes(2);
        $valuesBag->removeRange(4)->shouldBeCalledTimes(2);

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getOptions()->willReturn(array());
        $dateField->getValueComparison()->willReturn($comparison);

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('date', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('date', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    public function it_removes_all_duplicated_excludedRanges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::type('array'))->will(function ($args) {
            return $args[0] === $args[1];
        });

        $currentValues = array(
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00'),
            new Range('2013-09-25 00:00:00', '2013-09-30 00:00:00'),
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00'),
            new Range('2013-10-25 00:00:00', '2013-10-30 00:00:00'),
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00'),
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00', false), // duplicated but exclusive
            new Range('2013-08-25 00:00:00', '2013-08-30 00:00:00', true, false), // duplicated but exclusive
        );

        $valuesBag->getExcludedRanges()->willReturn($currentValues);
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(false);
        $valuesBag->hasExcludedRanges()->willReturn(true);
        $valuesBag->hasComparisons()->willReturn(false);
        $valuesBag->hasPatternMatchers()->willReturn(false);

        $valuesBag->removeExcludedRange(2)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedRange(4)->shouldBeCalledTimes(2);

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getOptions()->willReturn(array());
        $dateField->getValueComparison()->willReturn($comparison);

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('date', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('date', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    public function it_removes_all_duplicated_comparison(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::type('array'))->will(function ($args) {
            return $args[0] === $args[1];
        });

        $currentValues = array(
            new Compare('2013-08-25 00:00:00', '>'),
            new Compare('2013-08-25 00:00:00', '<'),
            new Compare('2013-08-25 00:00:00', '>'),
            new Compare('2013-08-25 10:00:00', '>'),
            new Compare('2013-08-25 00:00:00', '>'),
            new Compare('2013-08-25 00:00:00', '>='),
            new Compare('2013-08-25 00:00:00', '>='),
        );

        $valuesBag->getComparisons()->willReturn($currentValues);
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(false);
        $valuesBag->hasExcludedRanges()->willReturn(false);
        $valuesBag->hasComparisons()->willReturn(true);
        $valuesBag->hasPatternMatchers()->willReturn(false);

        $valuesBag->removeComparison(2)->shouldBeCalledTimes(2);
        $valuesBag->removeComparison(4)->shouldBeCalledTimes(2);
        $valuesBag->removeComparison(6)->shouldBeCalledTimes(2);

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getOptions()->willReturn(array());
        $dateField->getValueComparison()->willReturn($comparison);

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('date', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('date', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    public function it_removes_all_duplicated_matchers(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::type('array'))->will(function ($args) {
            return $args[0] === $args[1];
        });

        $currentValues = array(
            new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS),
            new PatternMatch('bar', PatternMatch::PATTERN_CONTAINS),
            new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS),
            new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH),
            new PatternMatch('foo', PatternMatch::PATTERN_ENDS_WITH),
            new PatternMatch('bla', PatternMatch::PATTERN_CONTAINS),
            new PatternMatch('bar', PatternMatch::PATTERN_CONTAINS),
            new PatternMatch('who', PatternMatch::PATTERN_CONTAINS),
            new PatternMatch('who', PatternMatch::PATTERN_CONTAINS, true),
        );

        $valuesBag->getPatternMatchers()->willReturn($currentValues);
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(false);
        $valuesBag->hasExcludedRanges()->willReturn(false);
        $valuesBag->hasComparisons()->willReturn(false);
        $valuesBag->hasPatternMatchers()->willReturn(true);

        $valuesBag->removePatternMatch(2)->shouldBeCalledTimes(2);
        $valuesBag->removePatternMatch(4)->shouldBeCalledTimes(2);
        $valuesBag->removePatternMatch(6)->shouldBeCalledTimes(2);

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getOptions()->willReturn(array());
        $dateField->getValueComparison()->willReturn($comparison);

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('date', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('date', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }
}
