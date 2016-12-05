<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Input;

use Rollerworks\Component\Search\Exception\ExceptionInterface;
use Rollerworks\Component\Search\Exception\GroupsNestingException;
use Rollerworks\Component\Search\Exception\GroupsOverflowException;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\InputProcessorInterface;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Rollerworks\Component\Search\ValuesError;

abstract class InputProcessorTestCase extends SearchIntegrationTestCase
{
    /**
     * @return InputProcessorInterface
     */
    abstract protected function getProcessor();

    /**
     * {@inheritdoc}
     */
    protected function getFieldSet($build = true)
    {
        $fieldSet = new FieldSetBuilder($this->getFactory());
        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('name', TextType::class);
        $fieldSet->add('lastname', TextType::class);
        $fieldSet->add('date', DateType::class, ['format' => 'MM-dd-yyyy']);
        $fieldSet->set(
            $this->getFactory()->createField('no-range-field', IntegerType::class)
                ->setValueTypeSupport(ValuesBag::VALUE_TYPE_RANGE, false)
        );

        $fieldSet->set(
            $this->getFactory()->createField('no-compares-field', IntegerType::class)->setValueTypeSupport(
                ValuesBag::VALUE_TYPE_COMPARISON,
                false
            )
        );

        $fieldSet->set(
            $this->getFactory()->createField('no-matchers-field', IntegerType::class)->setValueTypeSupport(
                ValuesBag::VALUE_TYPE_PATTERN_MATCH,
                false
            )
        );

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideEmptyInputTests
     */
    public function it_returns_null_on_empty_input($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        self::assertNull($processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideEmptyInputTests();

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideSingleValuePairTests
     */
    public function it_processes_values($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $values->addSimpleValue('٤٤٤٦٥٤٦٠٠'); // number in Arab
        $values->addSimpleValue('30');
        $values->addSimpleValue('30L');
        $values->addExcludedSimpleValue('value3');
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideSingleValuePairTests();

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideMultipleValues
     */
    public function it_processes_multiple_fields($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('name', $values);

        $date = new \DateTime('2014-12-16 00:00:00 UTC');

        $values = new ValuesBag();
        $values->addSimpleValue($date);
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideMultipleValues();

    /**
     * @param mixed $input
     *
     * @dataProvider provideRangeValues
     * @test
     */
    public function it_processes_range_values($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->add(new Range(1, 10));
        $values->add(new Range(15, 30));
        $values->add(new Range(100, 200, false));
        $values->add(new Range(310, 400, true, false));
        $values->add(new ExcludedRange(50, 70));
        $expectedGroup->addField('id', $values);

        $date = new \DateTime('2014-12-16 00:00:00 UTC');
        $date2 = new \DateTime('2014-12-20 00:00:00 UTC');

        $values = new ValuesBag();
        $values->add(new Range($date, $date2, true, true));
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideRangeValues();

    /**
     * @param mixed $input
     *
     * @dataProvider provideComparisonValues
     * @test
     */
    public function it_processes_comparisons($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->add(new Compare(1, '>'));
        $values->add(new Compare(2, '<'));
        $values->add(new Compare(5, '<='));
        $values->add(new Compare(8, '>='));
        $values->add(new Compare(20, '<>'));
        $expectedGroup->addField('id', $values);

        $date = new \DateTime('2014-12-16 00:00:00 UTC');

        $values = new ValuesBag();
        $values->add(new Compare($date, '>='));
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideComparisonValues();

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideMatcherValues
     */
    public function it_processes_matchers($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->add(new PatternMatch('value', PatternMatch::PATTERN_CONTAINS));
        $values->add(new PatternMatch('value2', PatternMatch::PATTERN_STARTS_WITH, true));
        $values->add(new PatternMatch('value3', PatternMatch::PATTERN_ENDS_WITH));
        $values->add(new PatternMatch('^foo|bar?', PatternMatch::PATTERN_REGEX));
        $values->add(new PatternMatch('value4', PatternMatch::PATTERN_NOT_CONTAINS));
        $values->add(new PatternMatch('value5', PatternMatch::PATTERN_NOT_CONTAINS, true));
        $values->add(new PatternMatch('value9', PatternMatch::PATTERN_EQUALS));
        $values->add(new PatternMatch('value10', PatternMatch::PATTERN_NOT_EQUALS));
        $values->add(new PatternMatch('value11', PatternMatch::PATTERN_EQUALS, true));
        $values->add(new PatternMatch('value12', PatternMatch::PATTERN_NOT_EQUALS, true));
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideMatcherValues();

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideGroupTests
     */
    public function it_processes_groups($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('name', $values);

        $values = new ValuesBag();
        $values->addSimpleValue('value3');
        $values->addSimpleValue('value4');

        $subGroup = new ValuesGroup();
        $subGroup->addField('name', $values);
        $expectedGroup->addGroup($subGroup);

        $values = new ValuesBag();
        $values->addSimpleValue('value8');
        $values->addSimpleValue('value10');

        $subGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        $subGroup->addField('name', $values);
        $expectedGroup->addGroup($subGroup);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideGroupTests();

    /**
     * @param mixed  $input
     * @param string $logical
     *
     * @test
     * @dataProvider provideRootLogicalTests
     */
    public function it_processes_root_logical($input, $logical = ValuesGroup::GROUP_LOGICAL_AND)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup($logical);

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideRootLogicalTests();

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideMultipleSubGroupTests
     */
    public function it_processes_multiple_subgroups($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');

        $subGroup = new ValuesGroup();
        $subGroup->addField('name', $values);

        $values = new ValuesBag();
        $values->addSimpleValue('value3');
        $values->addSimpleValue('value4');
        $expectedGroup->addGroup($subGroup);

        $subGroup2 = new ValuesGroup();
        $subGroup2->addField('name', $values);
        $expectedGroup->addGroup($subGroup2);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideMultipleSubGroupTests();

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideNestedGroupTests
     */
    public function it_processes_nested_subgroups($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();
        $nestedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $nestedGroup->addField('name', $values);

        $subGroup = new ValuesGroup();
        $subGroup->addGroup($nestedGroup);
        $expectedGroup->addGroup($subGroup);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * @return array[]
     */
    abstract public function provideNestedGroupTests();

    /**
     * @param mixed  $input
     * @param string $fieldName
     * @param int    $max
     * @param int    $count
     * @param int    $groupIdx
     * @param int    $nestingLevel
     *
     * @test
     * @dataProvider provideValueOverflowTests
     */
    public function it_errors_when_maximum_values_count_is_exceeded(
        $input,
        $fieldName,
        $max,
        $groupIdx,
        $nestingLevel
    ) {
        $processor = $this->getProcessor();

        $config = new ProcessorConfig($this->getFieldSet());
        $config->setMaxValues(3);

        try {
            $condition = $processor->process($config, $input);

            $this->fail('Condition should be invalid.');
        } catch (\Exception $e) {
            $this->detectSystemException($e);

            if (!$e instanceof ValuesOverflowException) {
                $this->fail('Expected a ValuesOverflowException but got: '.get_class($e));
            }

            self::assertEquals($fieldName, $e->getFieldName());
            self::assertEquals($max, $e->getMax());
            self::assertEquals($groupIdx, $e->getGroupIdx());
            self::assertEquals($nestingLevel, $e->getNestingLevel());
        }
    }

    /**
     * @return array[]
     */
    abstract public function provideValueOverflowTests();

    /**
     * @param mixed $input
     * @param int   $max
     * @param int   $count
     * @param int   $groupIdx
     * @param int   $nestingLevel
     *
     * @test
     * @dataProvider provideGroupsOverflowTests
     */
    public function it_errors_when_maximum_groups_count_is_exceeded($input, $max, $count, $groupIdx, $nestingLevel)
    {
        $processor = $this->getProcessor();

        $config = new ProcessorConfig($this->getFieldSet());
        $config->setMaxGroups(3);

        try {
            $processor->process($config, $input);

            $this->fail('Condition should be invalid.');
        } catch (\Exception $e) {
            $this->detectSystemException($e);

            if (!$e instanceof GroupsOverflowException) {
                $this->fail('Expected a GroupsOverflowException but got: '.get_class($e));
            }

            self::assertEquals($max, $e->getMax());
            self::assertEquals($count, $e->getCount());
            self::assertEquals($groupIdx, $e->getGroupIdx());
            self::assertEquals($nestingLevel, $e->getNestingLevel());
        }
    }

    /**
     * @return array[]
     */
    abstract public function provideGroupsOverflowTests();

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideNestingLevelExceededTests
     */
    public function it_errors_when_maximum_nesting_level_is_reached($input)
    {
        $processor = $this->getProcessor();

        $config = new ProcessorConfig($this->getFieldSet());
        $config->setMaxNestingLevel(1);

        try {
            $processor->process($config, $input);

            $this->fail('Condition should be invalid.');
        } catch (\Exception $e) {
            $this->detectSystemException($e);

            if (!$e instanceof GroupsNestingException) {
                $this->fail('Expected a GroupsNestingException but got: '.get_class($e));
            }

            self::assertEquals(1, $e->getMaxNesting());
            self::assertEquals(0, $e->getGroupIdx());
            self::assertEquals(2, $e->getNestingLevel());
        }
    }

    /**
     * @return array[]
     */
    abstract public function provideNestingLevelExceededTests();

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideUnknownFieldTests
     */
    public function it_errors_when_the_field_does_not_exist_in_fieldset($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        try {
            $processor->process($config, $input);

            $this->fail('Condition should be invalid.');
        } catch (\Exception $e) {
            $this->detectSystemException($e);

            if (!$e instanceof UnknownFieldException) {
                $this->fail('Expected a UnknownFieldException but got: '.get_class($e));
            }

            self::assertEquals('field2', $e->getFieldName());
        }
    }

    /**
     * @return array[]
     */
    abstract public function provideUnknownFieldTests();

    /**
     * @param mixed  $input
     * @param string $fieldName
     * @param string $valueType
     *
     * @test
     * @dataProvider provideUnsupportedValueTypeExceptionTests
     */
    public function it_errors_when_the_field_does_not_support_the_value_type($input, $fieldName, $valueType)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        try {
            $processor->process($config, $input);

            $this->fail('Condition should be invalid.');
        } catch (\Exception $e) {
            $this->detectSystemException($e);

            if (!$e instanceof UnsupportedValueTypeException) {
                $this->fail(
                    sprintf(
                        'Expected a UnknownFieldException but got: "%s" with message: %s',
                        get_class($e),
                        $e->getMessage()
                    )
                );
            }

            self::assertEquals($fieldName, $e->getFieldName());
            self::assertEquals($valueType, $e->getValueType());
        }
    }

    /**
     * @return array[]
     */
    abstract public function provideUnsupportedValueTypeExceptionTests();

    /**
     * @param mixed $input
     * @param bool  $exclusive
     *
     * @test
     * @dataProvider provideInvalidRangeTests
     */
    public function it_errors_when_a_range_has_invalid_bounds($input, $exclusive = false)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        try {
            $processor->process($config, $input);

            $this->fail('Condition should be invalid.');
        } catch (\Exception $e) {
            $this->detectSystemException($e);

            if (!$e instanceof InvalidSearchConditionException) {
                $this->fail('Expected a InvalidSearchConditionException but got: '.get_class($e));
            }

            self::assertCount(2, $e->getCondition()->getValuesGroup()->getField('id')->getErrors());
            $errors = $e->getCondition()->getValuesGroup()->getField('id')->getErrors();

            $error = current($errors);
            self::assertEquals('Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', $error->getMessageTemplate());
            self::assertEquals(['{{ lower }}' => '30', '{{ upper }}' => '10'], $error->getMessageParameters());
            self::assertEquals($exclusive ? 'excludedRanges[0]' : 'ranges[0]', $error->getSubPath());

            $error = next($errors);
            self::assertEquals('Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', $error->getMessageTemplate());
            self::assertEquals(['{{ lower }}' => '40', '{{ upper }}' => '20'], $error->getMessageParameters());
            self::assertEquals($exclusive ? 'excludedRanges[2]' : 'ranges[2]', $error->getSubPath());
        }
    }

    /**
     * @return array[]
     */
    abstract public function provideInvalidRangeTests();

    /**
     * @param mixed         $input
     * @param string        $fieldName
     * @param ValuesError[] $errors
     *
     * @test
     * @dataProvider provideInvalidValueTests
     */
    public function it_errors_when_transformation_fails($input, $fieldName, array $errors)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        try {
            $processor->process($config, $input);

            $this->fail('Condition should be invalid.');
        } catch (\Exception $e) {
            $this->detectSystemException($e);

            if (!$e instanceof InvalidSearchConditionException) {
                $this->fail('Expected a InvalidSearchConditionException but got: '.get_class($e));
            }

            self::assertCount(count($errors), $e->getCondition()->getValuesGroup()->getField('id')->getErrors());
            $errorsList = $e->getCondition()->getValuesGroup()->getField($fieldName)->getErrors();

            foreach ($errors as $error) {
                self::assertArrayHasKey($error->getHash(), $errorsList);
                self::assertNotNull($errorsList[$error->getHash()]->getCause());
            }
        }
    }

    /**
     * @return array[]
     */
    abstract public function provideInvalidValueTests();

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideNestedErrorsTests
     */
    public function it_checks_nested_fields($input)
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $this->setExpectedException('\Rollerworks\Component\Search\Exception\InvalidSearchConditionException');

        $processor->process($config, $input);
    }

    /**
     * @return array[]
     */
    abstract public function provideNestedErrorsTests();

    protected function detectSystemException(\Exception $exception)
    {
        if (!$exception instanceof ExceptionInterface) {
            throw $exception;
        }
    }
}
