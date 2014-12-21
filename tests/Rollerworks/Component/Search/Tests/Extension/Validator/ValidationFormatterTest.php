<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Validator;

use Rollerworks\Component\Search\Extension\Validator\ValidationFormatter;
use Rollerworks\Component\Search\Extension\Validator\ValidatorExtension;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\FormatterTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class ValidationFormatterTest extends FormatterTestCase
{
    private $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->validator = Validation::createValidator();
        $this->formatter = new ValidationFormatter($this->validator);
    }

    protected function getFieldSet($build = true)
    {
        $fieldSet = new FieldSetBuilder('test', $this->factory);
        $fieldSet->add('id', 'integer', array('constraints' => new Assert\Range(array('min' => 5))));
        $fieldSet->add('date', 'date', array('constraints' => new Assert\Date()));
        $fieldSet->add('type', 'text');

        return $fieldSet->getFieldSet();
    }

    protected function getExtensions()
    {
        return array(new ValidatorExtension());
    }

    /**
     * @test
     */
    public function it_validates_all_fields_with_constraints()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addSingleValue(new SingleValue(10))
                ->addSingleValue(new SingleValue(3))
                ->addSingleValue(new SingleValue(4))
            ->end()
            ->field('date')
                ->addSingleValue(new SingleValue(new \DateTime('2014-12-13 14:06:02', new \DateTimeZone('UTC'))))
                ->addSingleValue(new SingleValue('bar'))
            ->end()
            ->field('type')
                ->addSingleValue(new SingleValue('foo'))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $this->assertTrue($valuesGroup->hasErrors());
        $this->assertSearchError($valuesGroup->getField('id'), 'singleValues[1].value', 'This value should be {{ limit }} or more.', array('{{ value }}' => 3, '{{ limit }}' => 5));
        $this->assertSearchError($valuesGroup->getField('id'), 'singleValues[2].value', 'This value should be {{ limit }} or more.', array('{{ value }}' => 4, '{{ limit }}' => 5));
        $this->assertSearchError($valuesGroup->getField('date'), 'singleValues[1].value', 'This value is not a valid date.', array('{{ value }}' => '"bar"'));
    }

    /**
     * @test
     */
    public function it_validates_ranges()
    {
        $startTime = new \DateTime('2014-12-13 14:35:05', new \DateTimeZone('UTC'));
        $endTime = clone $startTime;
        $endTime->modify('+1 day');

        $startTimeView = $startTime->format('m/d/Y');
        $endTimeView = $endTime->format('m/d/Y');

        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addRange(new Range(10, 20))
                ->addRange($invalidRange = new Range(30, 20))
                ->addRange($invalidRange = new Range(30, 20, false))
            ->end()
            ->field('date')
                ->addRange(new Range($startTime, $endTime, true, true, $startTimeView, $endTimeView))
                ->addRange($range2 = new Range($endTime, $startTime, true, true, $endTimeView, $startTimeView))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $this->assertTrue($valuesGroup->hasErrors());

        $this->assertSearchError($valuesGroup->getField('id'), 'ranges[1]', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 30, '{{ upper }}' => 20));
        $this->assertSearchError($valuesGroup->getField('date'), 'ranges[1]', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => $endTimeView, '{{ upper }}' => $startTimeView));
        $this->assertNotSearchError($valuesGroup->getField('id'), 'ranges[2]', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 30, '{{ upper }}' => 20));
    }

    /**
     * @test
     */
    public function it_validates_excluded_ranges()
    {
        $startTime = new \DateTime('2014-12-13 14:35:05', new \DateTimeZone('UTC'));
        $endTime = clone $startTime;
        $endTime->modify('+1 day');

        $startTimeView = $startTime->format('m/d/Y');
        $endTimeView = $endTime->format('m/d/Y');

        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addExcludedRange(new Range(10, 20))
                ->addExcludedRange($invalidRange = new Range(30, 20))
                ->addExcludedRange($invalidRange = new Range(30, 20, false))
            ->end()
            ->field('date')
                ->addExcludedRange(new Range($startTime, $endTime, true, true, $startTimeView, $endTimeView))
                ->addExcludedRange($range2 = new Range($endTime, $startTime, true, true, $endTimeView, $startTimeView))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $this->assertTrue($valuesGroup->hasErrors());

        $this->assertSearchError($valuesGroup->getField('id'), 'excludedRanges[1]', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 30, '{{ upper }}' => 20));
        $this->assertSearchError($valuesGroup->getField('date'), 'excludedRanges[1]', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => $endTimeView, '{{ upper }}' => $startTimeView));
        $this->assertNotSearchError($valuesGroup->getField('id'), 'excludedRanges[2]', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 30, '{{ upper }}' => 20));
    }

    /**
     * @test
     */
    public function it_validates_comparisons()
    {
        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('id')
                ->addComparison(new Compare(10, '>'))
                ->addComparison(new Compare(3, '>'))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $this->assertTrue($valuesGroup->hasErrors());
        $this->assertSearchError($valuesGroup->getField('id'), 'comparisons[1].value', 'This value should be {{ limit }} or more.', array('{{ value }}' => 3, '{{ limit }}' => 5));
    }

    /**
     * @test
     */
    public function it_validates_matchers()
    {
        $fieldSet = new FieldSetBuilder('test', $this->factory);
        $fieldSet->add('username', 'text', array('constraints' => new Assert\NotBlank()));

        $this->fieldSet = $fieldSet->getFieldSet();

        $condition = SearchConditionBuilder::create($this->fieldSet)
            ->field('username')
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_ENDS_WITH))
                ->addPatternMatch(new PatternMatch('', PatternMatch::PATTERN_ENDS_WITH))
            ->end()
            ->getSearchCondition()
        ;

        $this->formatter->format($condition);
        $valuesGroup = $condition->getValuesGroup();

        $this->assertTrue($valuesGroup->hasErrors());
        $this->assertSearchError($valuesGroup->getField('username'), 'patternMatchers[2].value', 'This value should not be blank.', array('{{ value }}' => '""'));
    }

    private function assertSearchError(
        ValuesBag $valuesBag,
        $subPath,
        $messageTemplate,
        array $messageParameters = array(),
        $messagePluralization = null
    ) {
        $this->assertTrue($valuesBag->hasErrors());

        $expectedError = new ValuesError(
            $subPath,
            strtr($messageTemplate, $messageParameters),
            $messageTemplate,
            $messageParameters,
            $messagePluralization
        );

        $this->assertContains(
            $expectedError,
            $valuesBag->getErrors(),
            'ValuesBag should contain has error',
            false,
            false
        );
    }

    private function assertNotSearchError(
        ValuesBag $valuesBag,
        $subPath,
        $messageTemplate,
        array $messageParameters = array(),
        $messagePluralization = null
    ) {
        $expectedError = new ValuesError(
            $subPath,
            strtr($messageTemplate, $messageParameters),
            $messageTemplate,
            $messageParameters,
            $messagePluralization
        );

        $this->assertNotContains(
            $expectedError,
            $valuesBag->getErrors(),
            '',
            false,
            false
        );
    }
}
