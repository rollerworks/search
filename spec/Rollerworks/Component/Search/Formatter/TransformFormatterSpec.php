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
use Rollerworks\Component\Search\DataTransformerInterface;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;

class TransformFormatterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Formatter\TransformFormatter');
        $this->shouldImplement('Rollerworks\Component\Search\FormatterInterface');
    }

    function it_transform_singleValues_using_the_registered_transformers(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, SingleValue $dateValue, DataTransformerInterface $viewTransformer)
    {
        $value = '2013-08-25 00:00:00';

        $dateValue->getValue()->will(function () use (&$value) {
            return $value;
        });

        $dateValue->getViewValue()->will(function () {
            return '2013-08-25 00:00:00';
        });

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getViewTransformers()->willReturn(array($viewTransformer));

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addSingleValue($dateValue->getWrappedObject());
        $valuesGroup->addField('date', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $viewTransformer->reverseTransform(Argument::any())->will(function ($args) use (&$value) {
            $value = new \DateTime($args[0]);

            return $value;
        });

        $viewTransformer->transform(Argument::any())->will(function ($args) {
            return $args[0]->format('m/d/Y');
        });

        $dateValue->setViewValue("08/25/2013")->shouldBeCalled();
        $dateValue->setValue(new \DateTime('2013-08-25 00:00:00'))->shouldBeCalled();

        $this->format($condition);
    }

    function it_transform_excludedValues_using_the_registered_transformers(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, SingleValue $dateValue, DataTransformerInterface $viewTransformer)
    {
        $value = '2013-08-25 00:00:00';
        $dateValue->getValue()->will(function () use (&$value) {
            return $value;
        });

        $dateValue->getViewValue()->will(function () {
            return '2013-08-25 00:00:00';
        });

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getViewTransformers()->willReturn(array($viewTransformer));

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedValue($dateValue->getWrappedObject());
        $valuesGroup->addField('date', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $viewTransformer->reverseTransform(Argument::any())->will(function ($args) use (&$value) {
            $value = new \DateTime($args[0]);

            return $value;
        });

        $viewTransformer->transform(Argument::any())->will(function ($args) {
            return $args[0]->format('m/d/Y');
        });

        $dateValue->setViewValue("08/25/2013")->shouldBeCalled();
        $dateValue->setValue(new \DateTime('2013-08-25 00:00:00'))->shouldBeCalled();

        $this->format($condition);
    }

    function it_transform_ranges_using_the_registered_transformers(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, Range $dateValue, DataTransformerInterface $viewTransformer)
    {
        $lowerValue = '2013-08-25 00:00:00';
        $dateValue->getLower()->will(function () use (&$lowerValue) {
            return $lowerValue;
        });

        $dateValue->getViewLower()->will(function () {
            return '2013-08-25 00:00:00';
        });

        $upperValue = '2013-08-30 00:00:00';
        $dateValue->getUpper()->will(function () use (&$upperValue) {
            return $upperValue;
        });

        $dateValue->getViewUpper()->will(function () {
            return '2013-08-30 00:00:00';
        });

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getViewTransformers()->willReturn(array($viewTransformer));

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addRange($dateValue->getWrappedObject());
        $valuesGroup->addField('date', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $viewTransformer->reverseTransform(Argument::exact('2013-08-25 00:00:00'))->will(function () use (&$lowerValue) {
            $lowerValue = new \DateTime('2013-08-25 00:00:00');

            return $lowerValue;
        });

        $viewTransformer->reverseTransform(Argument::exact('2013-08-30 00:00:00'))->will(function () use (&$upperValue) {
            $upperValue = new \DateTime('2013-08-30 00:00:00');

            return $upperValue;
        });

        $viewTransformer->transform(Argument::any())->will(function ($args) {
            return $args[0]->format('m/d/Y');
        });

        $dateValue->setViewLower("08/25/2013")->shouldBeCalled();
        $dateValue->setViewUpper("08/30/2013")->shouldBeCalled();
        $dateValue->setLower(new \DateTime('2013-08-25 00:00:00'))->shouldBeCalled();
        $dateValue->setUpper(new \DateTime('2013-08-30 00:00:00'))->shouldBeCalled();

        $this->format($condition);
    }

    function it_transform_excludedRanges_using_the_registered_transformers(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, Range $dateValue, DataTransformerInterface $viewTransformer)
    {
        $lowerValue = '2013-08-25 00:00:00';
        $dateValue->getLower()->will(function () use (&$lowerValue) {
            return $lowerValue;
        });

        $dateValue->getViewLower()->will(function () {
            return '2013-08-25 00:00:00';
        });

        $upperValue = '2013-08-30 00:00:00';
        $dateValue->getUpper()->will(function () use (&$upperValue) {
            return $upperValue;
        });

        $dateValue->getViewUpper()->will(function () {
            return '2013-08-30 00:00:00';
        });

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getViewTransformers()->willReturn(array($viewTransformer));

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedRange($dateValue->getWrappedObject());
        $valuesGroup->addField('date', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $viewTransformer->reverseTransform(Argument::exact('2013-08-25 00:00:00'))->will(function () use (&$lowerValue) {
            $lowerValue = new \DateTime('2013-08-25 00:00:00');

            return $lowerValue;
        });

        $viewTransformer->reverseTransform(Argument::exact('2013-08-30 00:00:00'))->will(function () use (&$upperValue) {
            $upperValue = new \DateTime('2013-08-30 00:00:00');

            return $upperValue;
        });

        $viewTransformer->transform(Argument::any())->will(function ($args) {
            return $args[0]->format('m/d/Y');
        });

        $dateValue->setViewLower("08/25/2013")->shouldBeCalled();
        $dateValue->setViewUpper("08/30/2013")->shouldBeCalled();
        $dateValue->setLower(new \DateTime('2013-08-25 00:00:00'))->shouldBeCalled();
        $dateValue->setUpper(new \DateTime('2013-08-30 00:00:00'))->shouldBeCalled();

        $this->format($condition);
    }

    function it_transform_comparisons_using_the_registered_transformers(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, Compare $dateValue, DataTransformerInterface $viewTransformer)
    {
        $value = '2013-08-25 00:00:00';
        $dateValue->getValue()->will(function () use (&$value) {
            return $value;
        });

        $dateValue->getViewValue()->will(function () {
            return '2013-08-25 00:00:00';
        });

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getViewTransformers()->willReturn(array($viewTransformer));

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addComparison($dateValue->getWrappedObject());
        $valuesGroup->addField('date', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $viewTransformer->reverseTransform(Argument::any())->will(function ($args) use (&$value) {
            $value = new \DateTime($args[0]);

            return $value;
        });

        $viewTransformer->transform(Argument::any())->will(function ($args) {
            return $args[0]->format('m/d/Y');
        });

        $dateValue->setViewValue("08/25/2013")->shouldBeCalled();
        $dateValue->setValue(new \DateTime('2013-08-25 00:00:00'))->shouldBeCalled();

        $this->format($condition);
    }

    // Normally you would not use objects as value, this is just for testing
    function it_transform_patternMatchers_using_the_registered_transformers(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, PatternMatch $dateValue, DataTransformerInterface $viewTransformer)
    {
        $value = '2013-08-25 00:00:00';
        $dateValue->getValue()->will(function () use (&$value) {
            return $value;
        });
        $dateValue->getType()->willReturn(PatternMatch::PATTERN_CONTAINS);

        $dateValue->getViewValue()->will(function () {
            return '2013-08-25 00:00:00';
        });

        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getViewTransformers()->willReturn(array($viewTransformer));

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addPatternMatch($dateValue->getWrappedObject());
        $valuesGroup->addField('date', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $viewTransformer->reverseTransform(Argument::any())->will(function ($args) use (&$value) {
            $value = new \DateTime($args[0]);

            return $value;
        });

        $viewTransformer->transform(Argument::any())->will(function ($args) {
            return $args[0]->format('m/d/Y');
        });

        $dateValue->setViewValue("08/25/2013")->shouldBeCalled();
        $dateValue->setValue(new \DateTime('2013-08-25 00:00:00'))->shouldBeCalled();

        $this->format($condition);
    }

    function it_does_not_transform_patternMatchers_with_type_regex(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $field, PatternMatch $searchValue, PatternMatch $searchValue2, DataTransformerInterface $viewTransformer)
    {
        $searchValue->getValue()->willReturn('^foo|[bar]*');
        $searchValue->getType()->willReturn(PatternMatch::PATTERN_NOT_REGEX);

        $searchValue2->getValue()->willReturn('^foo|[bar]*');
        $searchValue2->getType()->willReturn(PatternMatch::PATTERN_REGEX);

        $field->hasOption('constraints')->willReturn(false);
        $field->getViewTransformers()->willReturn(array($viewTransformer));

        $fieldSet->get('date')->willReturn($field);
        $fieldSet->has('date')->willReturn(true);

        $valuesGroup = new ValuesGroup();

        $valuesBag = new ValuesBag();
        $valuesBag->addPatternMatch($searchValue->getWrappedObject());
        $valuesBag->addPatternMatch($searchValue2->getWrappedObject());
        $valuesGroup->addField('date', $valuesBag);

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $viewTransformer->reverseTransform(Argument::any())->shouldNotBeCalled();
        $viewTransformer->transform(Argument::any())->shouldNotBeCalled();

        $searchValue->setViewValue(Argument::any())->shouldNotBeCalled();
        $searchValue->setValue(Argument::any())->shouldNotBeCalled();

        $this->format($condition);
    }

    function it_adds_an_error_on_failed_transformation(SearchConditionInterface $condition, FieldSet $fieldSet, FieldConfigInterface $dateField, ValuesBag $valuesBag, DataTransformerInterface $viewTransformer)
    {
        $dateField->hasOption('constraints')->willReturn(false);
        $dateField->getViewTransformers()->willReturn(array($viewTransformer));

        $fieldSet->get('date')->willReturn($dateField);
        $fieldSet->has('date')->willReturn(true);

        $currentValues = array(
            new SingleValue('2013-08-25 00:00:00'),
            new SingleValue('2013-08-30 00:00:00'),
        );

        $valuesBag->getSingleValues()->willReturn($currentValues);
        $valuesBag->hasSingleValues()->willReturn(true);
        $valuesBag->hasExcludedValues()->willReturn(false);
        $valuesBag->hasRanges()->willReturn(false);
        $valuesBag->hasExcludedRanges()->willReturn(false);
        $valuesBag->hasComparisons()->willReturn(false);
        $valuesBag->hasPatternMatchers()->willReturn(false);

        $valuesGroup = new ValuesGroup();
        $valuesGroup->addField('date', $valuesBag->getWrappedObject());

        $valuesBag->addError(Argument::exact(new ValuesError('singleValues[0]', 'Transformation failed.')))->shouldBeCalled();

        $condition->getValuesGroup()->willReturn($valuesGroup);
        $condition->getFieldSet()->willReturn($fieldSet);

        $viewTransformer->reverseTransform(Argument::any())->willThrow(new TransformationFailedException('Transformation failed.'));

        $this->format($condition);
    }
}
