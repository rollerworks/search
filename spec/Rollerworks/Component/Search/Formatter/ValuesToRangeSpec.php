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
use Rollerworks\Component\Search\ValueIncrementerInterface;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

class ValuesToRangeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Formatter\ValuesToRange');
        $this->shouldImplement('Rollerworks\Component\Search\FormatterInterface');
    }

    public function it_converts_single_proceeding_values_to_ranges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, ValuesBag $valuesBag, ValueIncrementerInterface $comparison)
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
        $comparison->getIncrementedValue(Argument::type('int'), Argument::type('array'))->will(function ($args) {
            return $args[0] + 1;
        });

        $currentValues = array(
            new SingleValue(1),
            new SingleValue(2),
            new SingleValue(3),
            new SingleValue(4),
            new SingleValue(5),
            new SingleValue(10),
            new SingleValue(7),
        );

        $valuesBag->getSingleValues()->willReturn($currentValues);
        $valuesBag->hasSingleValues()->willReturn(true);
        $valuesBag->hasExcludedValues()->willReturn(false);

        $valuesBag->removeSingleValue(0)->shouldBeCalledTimes(2);
        $valuesBag->removeSingleValue(1)->shouldBeCalledTimes(2);
        $valuesBag->removeSingleValue(2)->shouldBeCalledTimes(2);
        $valuesBag->removeSingleValue(3)->shouldBeCalledTimes(2);
        $valuesBag->removeSingleValue(4)->shouldBeCalledTimes(2);

        $valuesBag->addRange(new Range(1, 5))->shouldBeCalledTimes(2);

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

    public function it_converts_excluded_proceeding_values_to_ranges(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, ValuesBag $valuesBag, ValueIncrementerInterface $comparison)
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
        $comparison->getIncrementedValue(Argument::type('int'), Argument::type('array'))->will(function ($args) {
            return $args[0] + 1;
        });

        $currentValues = array(
            new SingleValue(1),
            new SingleValue(2),
            new SingleValue(3),
            new SingleValue(4),
            new SingleValue(5),
            new SingleValue(10),
            new SingleValue(7),
        );

        $valuesBag->getExcludedValues()->willReturn($currentValues);
        $valuesBag->hasExcludedValues()->willReturn(true);
        $valuesBag->hasSingleValues()->willReturn(false);

        $valuesBag->removeExcludedValue(0)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedValue(1)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedValue(2)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedValue(3)->shouldBeCalledTimes(2);
        $valuesBag->removeExcludedValue(4)->shouldBeCalledTimes(2);

        $valuesBag->addExcludedRange(new Range(1, 5))->shouldBeCalledTimes(2);

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
