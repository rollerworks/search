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
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

class RangeOptimizerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Formatter\RangeOptimizer');
        $this->shouldImplement('Rollerworks\Component\Search\FormatterInterface');
    }

    function it_removes_singleValues_overlapping_in_ranges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] == $args[1];
        });
        $comparison->isLower(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] < $args[1];
        });
        $comparison->isHigher(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] > $args[1];
        });

        $currentValues = array(
            new SingleValue(90),
            new SingleValue(21),
            new SingleValue(15), // overlapping in ranges[0]
            new SingleValue(65), // overlapping in ranges[2]
            new SingleValue(40),
            new SingleValue(1), // this is overlapping, but the range lower-bound is exclusive
        );

        $ranges = array(
            new Range(11, 20),
            new Range(25, 30),
            new Range(50, 70),
            new Range(1, 10, false),
        );

        $valuesBag->getSingleValues()->willReturn($currentValues);
        $valuesBag->getRanges()->willReturn($ranges);
        $valuesBag->hasSingleValues()->willReturn(true);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(true);
        $valuesBag->hasExcludedRanges()->willReturn(false);

        $valuesBag->removeSingleValue(2)->shouldBeCalledTimes(2);
        $valuesBag->removeSingleValue(3)->shouldBeCalledTimes(2);

        $idField->acceptRanges()->willReturn(true);
        $idField->getOptions()->willReturn(array());
        $idField->getValueComparison()->willReturn($comparison);

        $fieldSet->all()->willReturn(array('id' => $idField));
        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('id', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('id', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    function it_removes_ranges_overlapping_in_ranges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] == $args[1];
        });
        $comparison->isLower(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] < $args[1];
        });
        $comparison->isHigher(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] > $args[1];
        });

        $currentValues = array(
            new Range(1, 10),
            new Range(20, 30),
            new Range(2, 5), // overlapping in 0
            new Range(3, 7), // overlapping in 0
            new Range(50, 70),
            new Range(51, 71, true, false), // overlapping with bounds
            new Range(51, 69),
            new Range(52, 69),
            new Range(51, 71),
            new Range(49, 71, false, false), // 9
            new Range(51, 71, false), // overlapping but lower-bound is exclusive
        );

        $valuesBag->getRanges()->willReturn($currentValues);
        $valuesBag->getSingleValues()->willReturn(array());
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(true);
        $valuesBag->hasExcludedRanges()->willReturn(false);

        $valuesBag->removeRange(2)->shouldBeCalledTimes(2);
        $valuesBag->removeRange(3)->shouldBeCalledTimes(2);
        $valuesBag->removeRange(5)->shouldBeCalledTimes(2);
        $valuesBag->removeRange(6)->shouldBeCalledTimes(2);
        $valuesBag->removeRange(7)->shouldBeCalledTimes(2);
        $valuesBag->removeRange(9)->shouldBeCalledTimes(2);

        $idField->acceptRanges()->willReturn(true);
        $idField->getOptions()->willReturn(array());
        $idField->getValueComparison()->willReturn($comparison);

        $fieldSet->all()->willReturn(array('id' => $idField));
        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('id', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('id', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    function it_removes_excludedValues_overlapping_in_excludedRanges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] == $args[1];
        });
        $comparison->isLower(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] < $args[1];
        });
        $comparison->isHigher(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] > $args[1];
        });

        $currentValues = array(
            new SingleValue(90),
            new SingleValue(21),
            new SingleValue(15), // overlapping in ranges[0]
            new SingleValue(65), // overlapping in ranges[2]
            new SingleValue(40),
            new SingleValue(1), // this is overlapping, but the range lower-bound is exclusive
        );

        $ranges = array(
            new Range(11, 20),
            new Range(25, 30),
            new Range(50, 70),
            new Range(1, 10, false),
        );

        $valuesBag->getExcludedValues()->willReturn($currentValues);
        $valuesBag->getExcludedRanges()->willReturn($ranges);
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(true);
        $valuesBag->hasRanges()->willReturn(false);
        $valuesBag->hasExcludedRanges()->willReturn(true);

        $valuesBag->removeExcludedValue(2)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedValue(3)->shouldBeCalledTimes(2);

        $idField->acceptRanges()->willReturn(true);
        $idField->getOptions()->willReturn(array());
        $idField->getValueComparison()->willReturn($comparison);

        $fieldSet->all()->willReturn(array('id' => $idField));
        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('id', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('id', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    function it_removes_excludedRanges_overlapping_in_excludedRanges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, ValuesBag $valuesBag, ValueComparisonInterface $comparison)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] == $args[1];
        });
        $comparison->isLower(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] < $args[1];
        });
        $comparison->isHigher(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] > $args[1];
        });

        $currentValues = array(
            new Range(1, 10),
            new Range(20, 30),
            new Range(2, 5), // overlapping in 0
            new Range(3, 7), // overlapping in 0
            new Range(50, 70),
            new Range(51, 71, true, false), // overlapping with bounds
            new Range(51, 69),
            new Range(52, 69),
            new Range(51, 71),
            new Range(49, 71, false, false), // 9
            new Range(51, 71, false), // overlapping but lower-bound is exclusive
        );

        $valuesBag->getExcludedRanges()->willReturn($currentValues);
        $valuesBag->getExcludedValues()->willReturn(array());
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(false);
        $valuesBag->hasExcludedRanges()->willReturn(true);

        $valuesBag->removeExcludedRange(2)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedRange(3)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedRange(5)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedRange(6)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedRange(7)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedRange(9)->shouldBeCalledTimes(2);

        $fieldSet->all()->willReturn(array('id' => $idField));
        $idField->acceptRanges()->willReturn(true);
        $idField->getOptions()->willReturn(array());
        $idField->getValueComparison()->willReturn($comparison);

        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('id', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('id', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    function it_merges_connected_ranges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, ValuesBag $valuesBag, ValueComparisonInterface $comparison, Range $range1, Range $range2, Range $range3, Range $range4, Range $range5)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] == $args[1];
        });
        $comparison->isLower(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] < $args[1];
        });
        $comparison->isHigher(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] > $args[1];
        });

        $range1->getLower()->willReturn(10);
        $range1->getUpper()->willReturn(20);
        $range1->isLowerInclusive()->willReturn(true);
        $range1->isUpperInclusive()->willReturn(true);

        $range2->getLower()->willReturn(30);
        $range2->getUpper()->willReturn(40);
        $range2->isLowerInclusive()->willReturn(true);
        $range2->isUpperInclusive()->willReturn(true);

        $range3->getLower()->willReturn(20);
        $range3->getUpper()->willReturn(25);
        $range3->isLowerInclusive()->willReturn(true);
        $range3->isUpperInclusive()->willReturn(true);

        // this should not be changed as the bounds do not equal $range1
        $range4->getLower()->willReturn(20);
        $range4->getUpper()->willReturn(28);
        $range4->isLowerInclusive()->willReturn(false);
        $range4->isUpperInclusive()->willReturn(true);

        // ensures the range is always updated with the highest value
        $range5->getLower()->willReturn(20);
        $range5->getUpper()->willReturn(26);
        $range5->isLowerInclusive()->willReturn(true);
        $range5->isUpperInclusive()->willReturn(true);

        $currentValues = array(
            $range1,
            $range2,
            $range3,
            $range4,
            $range5,
        );

        $valuesBag->getRanges()->willReturn($currentValues);
        $valuesBag->getSingleValues()->willReturn(array());
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(true);
        $valuesBag->hasExcludedRanges()->willReturn(false);

        $range1->setUpper(25)->shouldBeCalledTimes(2);
        $range1->setUpper(26)->shouldBeCalledTimes(2);

        $valuesBag->removeRange(2)->shouldBeCalledTimes(2);
        $valuesBag->removeRange(4)->shouldBeCalledTimes(2);

        $idField->acceptRanges()->willReturn(true);
        $idField->getOptions()->willReturn(array());
        $idField->getValueComparison()->willReturn($comparison);

        $fieldSet->all()->willReturn(array('id' => $idField));
        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('id', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('id', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }

    function it_merges_connected_excludedRanges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, ValuesBag $valuesBag, ValueComparisonInterface $comparison, Range $range1, Range $range2, Range $range3, Range $range4, Range $range5)
    {
        $comparison->isEqual(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] == $args[1];
        });
        $comparison->isLower(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] < $args[1];
        });
        $comparison->isHigher(Argument::any(), Argument::any(), Argument::any())->will(function ($args) {
            return $args[0] > $args[1];
        });

        $range1->getLower()->willReturn(10);
        $range1->getUpper()->willReturn(20);
        $range1->isLowerInclusive()->willReturn(true);
        $range1->isUpperInclusive()->willReturn(true);

        $range2->getLower()->willReturn(30);
        $range2->getUpper()->willReturn(40);
        $range2->isLowerInclusive()->willReturn(true);
        $range2->isUpperInclusive()->willReturn(true);

        $range3->getLower()->willReturn(20);
        $range3->getUpper()->willReturn(25);
        $range3->isLowerInclusive()->willReturn(true);
        $range3->isUpperInclusive()->willReturn(true);

        // this should not be changed as the bounds do not equal $range1
        $range4->getLower()->willReturn(20);
        $range4->getUpper()->willReturn(28);
        $range4->isLowerInclusive()->willReturn(false);
        $range4->isUpperInclusive()->willReturn(true);

        // ensures the range is always updated with the highest value
        $range5->getLower()->willReturn(20);
        $range5->getUpper()->willReturn(26);
        $range5->isLowerInclusive()->willReturn(true);
        $range5->isUpperInclusive()->willReturn(true);

        $currentValues = array(
            $range1,
            $range2,
            $range3,
            $range4,
            $range5,
        );

        $valuesBag->getExcludedRanges()->willReturn($currentValues);
        $valuesBag->getExcludedValues()->willReturn(array());
        $valuesBag->getSingleValues()->willReturn(array());
        $valuesBag->hasSingleValues()->willReturn(false);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(false);
        $valuesBag->hasExcludedRanges()->willReturn(true);

        $range1->setUpper(25)->shouldBeCalledTimes(2);
        $range1->setUpper(26)->shouldBeCalledTimes(2);

        $valuesBag->removeExcludedRange(2)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedRange(4)->shouldBeCalledTimes(2);

        $idField->acceptRanges()->willReturn(true);
        $idField->getOptions()->willReturn(array());
        $idField->getValueComparison()->willReturn($comparison);

        $fieldSet->all()->willReturn(array('id' => $idField));
        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('id', $valuesBag->getWrappedObject());

        $valuesGroup2 = new ValuesGroup();
        $valuesGroup2->addField('id', $valuesBag->getWrappedObject());
        $valuesGroup->addGroup($valuesGroup2);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->format($condition);
    }
}
