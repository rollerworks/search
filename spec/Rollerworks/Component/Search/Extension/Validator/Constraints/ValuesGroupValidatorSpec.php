<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Extension\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\Extension\Validator\Constraints\ValuesGroup as ValuesGroupConstraint;
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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

class ValuesGroupValidatorSpec extends ObjectBehavior
{
    /**
     * @var ExecutionContextInterface
     */
    protected $_executionContext;

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Validator\Constraints\ValuesGroupValidator');
        $this->shouldImplement('Symfony\Component\Validator\ConstraintValidatorInterface');
    }

    public function it_validates_all_fields_with_constraints(ExecutionContextInterface $executionContext, SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, FieldConfigInterface $dateField, FieldConfigInterface $typeField)
    {
        $this->_executionContext = $executionContext;
        $this->initialize($executionContext);

        $fieldSet->has(Argument::any())->willReturn(false);

        $idField->getOptions()->willReturn(array('constraints' => new Assert\Range(array('min' => 5))));
        $idField->getOption('constraints')->willReturn(array(new Assert\Range(array('min' => 5))));
        $idField->hasOption('constraints')->willReturn(true);
        $idField->getOption('validation_groups')->willReturn(array('Default'));
        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $dateField->getOptions()->willReturn(array('constraints' => new Assert\Date()));
        $dateField->getOption('constraints')->willReturn(array(new Assert\Date()));
        $dateField->hasOption('constraints')->willReturn(true);
        $dateField->getOption('validation_groups')->willReturn(array('Default'));
        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $typeField->getOptions()->willReturn(array());
        $typeField->hasOption('constraints')->willReturn(false);
        $fieldSet->get('type')->willReturn($typeField);
        $fieldSet->has('type')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addSingleValue(new SingleValue(10));
        $valuesBag->addSingleValue(new SingleValue(3));
        $valuesGroup->addField('id', $valuesBag);

        $valuesBag = new ValuesBag();
        $valuesBag->addSingleValue(new SingleValue('bar'));
        $valuesGroup->addField('date', $valuesBag);

        $valuesBag = new ValuesBag();
        $valuesBag->addSingleValue(new SingleValue('foo')); // This value is actually invalid, but the field has no constraints so it should have no violations
        $valuesGroup->addField('type', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->assignValidatorExpectation(10, 'fields[id].singleValues[0].value', array(new Assert\Range(array('min' => 5))));
        $this->assignValidatorExpectation(3, 'fields[id].singleValues[1].value', array(new Assert\Range(array('min' => 5))), array('This value should be {{ limit }} or more.', array('{{ value }}' => 3, '{{ limit }}' => 5)));
        $this->assignValidatorExpectation('bar', 'fields[date].singleValues[0].value', array(new Assert\Date()), array('This value is not a valid date.', array('{{ value }}' => 'bar')));

        // No constraints so this should not be called
        $this->_executionContext->validateValue(Argument::exact('foo'), Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->validate($condition, new ValuesGroupConstraint());
    }

    public function it_validates_ranges(ExecutionContextInterface $executionContext, SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, FieldConfigInterface $dateField, ValueComparisonInterface $comparison)
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

        $this->_executionContext = $executionContext;
        $this->initialize($executionContext);

        $fieldSet->has(Argument::any())->willReturn(false);

        $idField->getOptions()->willReturn(array('constraints' => new Assert\Range(array('min' => 5))));
        $idField->getOption('constraints')->willReturn(array(new Assert\Range(array('min' => 5))));
        $idField->hasOption('constraints')->willReturn(true);
        $idField->getOption('validation_groups')->willReturn(array('Default'));
        $idField->getValueComparison()->willReturn($comparison);
        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $dateField->getOptions()->willReturn(array('constraints' => new Assert\NotNull()));
        $dateField->getOption('constraints')->willReturn(array(new Assert\NotNull()));
        $dateField->hasOption('constraints')->willReturn(true);
        $dateField->getOption('validation_groups')->willReturn(array('Default'));
        $dateField->getValueComparison()->willReturn($comparison);
        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addRange(new Range(10, 20));
        $valuesBag->addRange($invalidRange = new Range(30, 20));
        $valuesGroup->addField('id', $valuesBag);

        $startTime = new \DateTime();
        $endTime = clone $startTime;
        $endTime->modify('+1 day');

        $startTimeView = $startTime->format('m/d/Y');
        $endTimeView = $endTime->format('m/d/Y');

        $valuesBag = new ValuesBag();
        $valuesBag->addRange(new Range($startTime, $endTime, true, true, $startTimeView, $endTimeView));
        $valuesBag->addRange($range2 = new Range($endTime, $startTime, true, true, $endTimeView, $startTimeView));
        $valuesGroup->addField('date', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        // Each side of the bound is validated
        $this->assignValidatorExpectation(10, 'fields[id].ranges[0].lower', array(new Assert\Range(array('min' => 5))));
        $this->assignValidatorExpectation(20, 'fields[id].ranges[0].upper', array(new Assert\Range(array('min' => 5))));

        $this->_executionContext->addViolationAt('fields[id].ranges[1]', Argument::exact('Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.'), Argument::exact(array('{{ lower }}' => 30, '{{ upper }}' => 20)), Argument::exact($invalidRange))->shouldBeCalled();

        $this->assignValidatorExpectation($startTime, 'fields[date].ranges[0].lower', array(new Assert\NotNull()));
        $this->assignValidatorExpectation($endTime, 'fields[date].ranges[0].upper', array(new Assert\NotNull()));

        $this->assignValidatorExpectation(30, 'fields[id].ranges[1].lower', array(new Assert\Range(array('min' => 5))));
        $this->assignValidatorExpectation(20, 'fields[id].ranges[1].upper', array(new Assert\Range(array('min' => 5))));

        $this->_executionContext->addViolationAt('fields[date].ranges[1]', Argument::exact('Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.'), Argument::exact(array('{{ lower }}' => $endTimeView, '{{ upper }}' => $startTimeView)), Argument::exact($range2))->shouldBeCalled();

        $this->assignValidatorExpectation($startTime, 'fields[date].ranges[1].lower', array(new Assert\NotNull()));
        $this->assignValidatorExpectation($endTime, 'fields[date].ranges[1].upper', array(new Assert\NotNull()));

        $this->validate($condition, new ValuesGroupConstraint());
    }

    public function it_validates_excluded_ranges(ExecutionContextInterface $executionContext, SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField, FieldConfigInterface $dateField, ValueComparisonInterface $comparison)
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

        $this->_executionContext = $executionContext;
        $this->initialize($executionContext);

        $fieldSet->has(Argument::any())->willReturn(false);

        $idField->getOptions()->willReturn(array('constraints' => new Assert\Range(array('min' => 5))));
        $idField->getOption('constraints')->willReturn(array(new Assert\Range(array('min' => 5))));
        $idField->hasOption('constraints')->willReturn(true);
        $idField->getOption('validation_groups')->willReturn(array('Default'));
        $idField->getValueComparison()->willReturn($comparison);
        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $dateField->getOptions()->willReturn(array('constraints' => new Assert\NotNull()));
        $dateField->getOption('constraints')->willReturn(array(new Assert\NotNull()));
        $dateField->hasOption('constraints')->willReturn(true);
        $dateField->getOption('validation_groups')->willReturn(array('Default'));
        $dateField->getValueComparison()->willReturn($comparison);
        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedRange(new Range(10, 20));
        $valuesBag->addExcludedRange($invalidRange = new Range(30, 20));
        $valuesGroup->addField('id', $valuesBag);

        $startTime = new \DateTime();
        $endTime = clone $startTime;
        $endTime->modify('+1 day');

        $startTimeView = $startTime->format('m/d/Y');
        $endTimeView = $endTime->format('m/d/Y');

        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedRange(new Range($startTime, $endTime, true, true, $startTimeView, $endTimeView));
        $valuesBag->addExcludedRange($range2 = new Range($endTime, $startTime, true, true, $endTimeView, $startTimeView));
        $valuesGroup->addField('date', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        // Each side of the bound is validated
        $this->assignValidatorExpectation(10, 'fields[id].excludedRanges[0].lower', array(new Assert\Range(array('min' => 5))));
        $this->assignValidatorExpectation(20, 'fields[id].excludedRanges[0].upper', array(new Assert\Range(array('min' => 5))));

        $this->_executionContext->addViolationAt('fields[id].excludedRanges[1]', Argument::exact('Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.'), Argument::exact(array('{{ lower }}' => 30, '{{ upper }}' => 20)), Argument::exact($invalidRange))->shouldBeCalled();

        $this->assignValidatorExpectation($startTime, 'fields[date].excludedRanges[0].lower', array(new Assert\NotNull()));
        $this->assignValidatorExpectation($endTime, 'fields[date].excludedRanges[0].upper', array(new Assert\NotNull()));

        $this->assignValidatorExpectation(30, 'fields[id].excludedRanges[1].lower', array(new Assert\Range(array('min' => 5))));
        $this->assignValidatorExpectation(20, 'fields[id].excludedRanges[1].upper', array(new Assert\Range(array('min' => 5))));

        $this->_executionContext->addViolationAt('fields[date].excludedRanges[1]', Argument::exact('Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.'), Argument::exact(array('{{ lower }}' => $endTimeView, '{{ upper }}' => $startTimeView)), Argument::exact($range2))->shouldBeCalled();

        $this->assignValidatorExpectation($startTime, 'fields[date].excludedRanges[1].lower', array(new Assert\NotNull()));
        $this->assignValidatorExpectation($endTime, 'fields[date].excludedRanges[1].upper', array(new Assert\NotNull()));

        $this->validate($condition, new ValuesGroupConstraint());
    }

    public function it_validates_comparisons(ExecutionContextInterface $executionContext, SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $idField)
    {
        $this->_executionContext = $executionContext;
        $this->initialize($executionContext);

        $fieldSet->has(Argument::any())->willReturn(false);

        $idField->getOptions()->willReturn(array('constraints' => new Assert\Range(array('min' => 5))));
        $idField->getOption('constraints')->willReturn(array(new Assert\Range(array('min' => 5))));
        $idField->hasOption('constraints')->willReturn(true);
        $idField->getOption('validation_groups')->willReturn(array('Default'));
        $fieldSet->get('id')->willReturn($idField);
        $fieldSet->has('id')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addComparison(new Compare(10, '>'));
        $valuesBag->addComparison(new Compare(3, '>'));
        $valuesGroup->addField('id', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->assignValidatorExpectation(10, 'fields[id].comparisons[0]', array(new Assert\Range(array('min' => 5))), array('This value should be {{ limit }} or more.', array('{{ value }}' => 3, '{{ limit }}' => 5)));
        $this->assignValidatorExpectation(3, 'fields[id].comparisons[1]', array(new Assert\Range(array('min' => 5))));

        $this->validate($condition, new ValuesGroupConstraint());
    }

    // Normally you would not validate matchers
    public function it_validates_matchers(ExecutionContextInterface $executionContext, SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $username)
    {
        $this->_executionContext = $executionContext;
        $this->initialize($executionContext);

        $fieldSet->has(Argument::any())->willReturn(false);

        $username->getOptions()->willReturn(array('constraints' => new Assert\NotBlank()));
        $username->getOption('constraints')->willReturn(array(new Assert\NotBlank()));
        $username->hasOption('constraints')->willReturn(true);
        $username->getOption('validation_groups')->willReturn(array('Default'));
        $fieldSet->get('username')->willReturn($username);
        $fieldSet->has('username')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH));
        $valuesBag->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_ENDS_WITH));
        $valuesBag->addPatternMatch(new PatternMatch('', PatternMatch::PATTERN_ENDS_WITH));
        $valuesGroup->addField('username', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $this->assignValidatorExpectation('foo', 'fields[id].matchers[0]', array(new Assert\NotBlank()));
        $this->assignValidatorExpectation('bar', 'fields[id].matchers[1]', array(new Assert\NotBlank()));
        $this->assignValidatorExpectation('', 'fields[id].matchers[2]', array(new Assert\NotBlank()), array('This value should not be blank.'));

        $this->validate($condition, new ValuesGroupConstraint());
    }

    protected function assignValidatorExpectation($value, $path, $constraints, array $violations = null)
    {
        $this->_executionContext->validateValue(Argument::exact($value), $constraints, Argument::any(), Argument::any())->shouldBeCalled();
        $validator = get_class(is_array($constraints) ? $constraints[0] : $constraints) . 'Validator';

        // Use a real validator instead of mocking absolutely everything
        // We give the validator our ExecutionContext so we can use expectations
        $validator = new $validator();
        $validator->initialize($this->_executionContext->getWrappedObject());

        if ($violations) {
            // Multiple violations
            if (is_array($violations[0])) {
                foreach ($violations as $violation) {
                    if (array_key_exists(1, $violation)) {
                        $this->_executionContext->addViolation($violation[0], $violation[1])->shouldBeCalled();
                    } else {
                        $this->_executionContext->addViolation($violation[0])->shouldBeCalled();
                    }
                }
            } else {
                if (array_key_exists(1, $violations)) {
                    $this->_executionContext->addViolation($violations[0], $violations[1])->shouldBeCalled();
                } else {
                    $this->_executionContext->addViolation($violations[0])->shouldBeCalled();
                }
            }
        }

        if (is_array($constraints)) {
            foreach ($constraints as $constraint) {
                call_user_func(array($validator, 'validate'), $value, $constraint);
            }
        } else {
            call_user_func(array($validator, 'validate'), $value, $constraints);
        }

        if ($path !== $path) {
            throw new \RuntimeException(sprintf('Property-path "%s" is not the same as "%s"', $path, $path));
        }
    }
}
