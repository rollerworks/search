<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Input;

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Input\FilterQuery\QueryException;
use Rollerworks\Component\Search\Input\FilterQueryInput;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;

final class FilterQueryInputTest extends InputProcessorTestCase
{
    protected function getProcessor()
    {
        return new FilterQueryInput($this->fieldAliasResolver->reveal());
    }

    /**
     * This is a special case as the dash is also used for ranges
     *
     * @test
     */
    public function it_parses_fieldNames_with_dash()
    {
        $fieldSet = $this->getFieldSet(false)->add('field-1', 'text')->getFieldSet();

        $processor = $this->getProcessor();
        $config = new ProcessorConfig($fieldSet);

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));
        $expectedGroup->addField('field-1', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);

        $this->assertEquals($condition, $processor->process($config, 'field-1: value, value2;'));
        $this->assertEquals($condition, $processor->process($config, 'field-1: value, value2'));
    }

    /**
     * @test
     */
    public function it_parses_a_quoted_value()
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value"2'));
        $values->addSingleValue(new SingleValue('!foo'));
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertEquals($condition, $processor->process($config, 'name: "value", "value""2", "!foo";'));
    }

    /**
     * @param string         $message
     * @param string         $input
     * @param int            $col
     * @param int            $line
     * @param array|string[] $expected
     * @param string         $got
     *
     * @test
     * @dataProvider provideQueryExceptionTests
     */
    public function it_errors_when_the_syntax_is_invalid($input, $message, $col, $line, $expected, $got)
    {
        $fieldSet = $this->getFieldSet(false)->add('field1', 'text')->getFieldSet();

        $processor = $this->getProcessor();
        $config = new ProcessorConfig($fieldSet);

        try {
            $processor->process($config, $input);
        } catch (\Exception $e) {
            if (!$e instanceof QueryException) {
                throw $e;
            }

            $this->assertEquals($message, $e->getMessage());
            $this->assertEquals($col, $e->getCol());
            $this->assertEquals($line, $e->getSyntaxLine());
            $this->assertEquals($expected, $e->getExpected());
            $this->assertEquals($got, $e->getInstead());
        }
    }

    public function provideQueryExceptionTests()
    {
        return array(
            array(
                'field1: value, value2, value3, value4, value5;)',
                "[Syntax Error] line 0, col 46: Error: Expected '(' | FieldIdentification, got ')'",
                46,
                0,
                array('(', 'FieldIdentification'),
                ')',
            ),
            array(
                'field1: value value2)',
                "[Syntax Error] line 0, col 14: Error: Expected ';' | '|' | ',' | '|' | ')', got 'value2'",
                14,
                0,
                array(';', '|', ',', '|', ')'),
                'value2',
            ),
        );
    }

    public function provideEmptyInputTests()
    {
        return array(
            array(''),
            array(' '),
        );
    }

    public function provideSingleValuePairTests()
    {
        return array(
            array('name: value, value2, ٤٤٤٦٥٤٦٠٠, 30, 30L, !value3;'),
            array('name: value, value2, ٤٤٤٦٥٤٦٠٠, 30, 30L, !value3'),
        );
    }

    public function provideMultipleValues()
    {
        return array(
            array('name: value, value2; date:"12-16-2014";'),
            array('name: value, value2; date:"12-16-2014"'),
        );
    }

    public function provideRangeValues()
    {
        return array(
            array('id: 1-10, 15 - 30, ]100-200], 310-400[, !50-70; date:["12-16-2014"-"12-20-2014"];'),
        );
    }

    public function provideComparisonValues()
    {
        return array(
            array('id: >1, <2, <=5, >=8, <>20; date:>="12-16-2014";'),
        );
    }

    public function provideMatcherValues()
    {
        return array(
            array('name: ~*value, ~i>value2, ~<value3, ~?"^foo|bar?", ~!*value4, ~i!*value5;'),
        );
    }

    public function provideGroupTests()
    {
        return array(
            array('name: value, value2; (name: value3, value4;); *(name: value8, value10;);'),
            array('name: value, value2; (name: value3, value4); *(name: value8, value10)'),
            array('name: value, value2; (name: value3, value4); *(name: value8, value10;)'),
        );
    }

    public function provideRootLogicalTests()
    {
        return array(
            array('name: value, value2;'),
            array('*name: value, value2;', ValuesGroup::GROUP_LOGICAL_OR),
        );
    }

    public function provideMultipleSubGroupTests()
    {
        return array(
            array('(name: value, value2); (name: value3, "value4");'),
        );
    }

    public function provideNestedGroupTests()
    {
        return array(
            array('((name: value, value2;););'),
            array('((name: value, value2;);)'),
            array('((name: value, value2;))'),
            array('((name: value, value2))'),
        );
    }

    public function provideAliasedFieldsTests()
    {
        return array(
            array('name: value; name: value2;'),
            array('name: value; firstname: value2;'),
            array('firstname: value; name: value2;'),
            array('firstname: value, value2;'),
        );
    }

    public function provideValueOverflowTests()
    {
        return array(
            array('name: value, value2, value3, value4, value5;', 'name', 3, 4, 0, 0),
            array('((name: value, value2, value3, value4, value5));', 'name', 3, 4, 0, 2),
            array('((name: value); (name: value, value2, value3, value4, value5));', 'name', 3, 4, 1, 2),
            array('name: value, value2; name: value3, value4, value5;', 'name', 3, 4, 0, 0), // merging
            array('id: 1, 2; user-id: 3, 4, 5;', 'id', 3, 4, 0, 0), // aliased
        );
    }

    public function provideGroupsOverflowTests()
    {
        return array(
            array('(name: value, value2;); (name: value, value2;); (name: value, value2;); (name: value, value2;)', 3, 4, 0, 0),
            array('( ((name: value, value2)); ((name: value, value2;); (name: value, value2;); (name: value, value2;); (name: value, value2;)) )', 3, 4, 1, 2),
        );
    }

    public function provideNestingLevelExceededTests()
    {
        return array(
            array('((field2: value;))'),
        );
    }

    public function provideUnknownFieldTests()
    {
        return array(
            array('field2: value;'),
        );
    }

    public function provideUnsupportedValueTypeExceptionTests()
    {
        return array(
            array('no-range-field: 1-12;', 'no-range-field', 'range'),
            array('no-compares-field: >12;', 'no-compares-field', 'comparison'),
            array('no-matchers-field: ~>12;', 'no-matchers-field', 'pattern-match'),
        );
    }

    public function provideFieldRequiredTests()
    {
        return array(
            array('field1: value, value2, value3, value4, value5;', 'field2', 0, 0),
            array('((field1: value, value2, value3, value4, value5));', 'field2', 0, 2),
            array('((field2: value); (field1: value, value2, value3, value4, value5));', 'field2', 1, 2),
        );
    }

    public function provideInvalidRangeTests()
    {
        return array(
            array('id: 30-10, 50-60, 40-20;'),
            array('id: !30-10, !50-60, !40-20;', true),
        );
    }

    public function provideInvalidValueTests()
    {
        return array(
            array(
                'id: foo, 30, bar, >life;',
                'id',
                array(
                    new ValuesError('singleValues[0]', 'This value is not valid.'),
                    new ValuesError('singleValues[2]', 'This value is not valid.'),
                    new ValuesError('comparisons[0].value', 'This value is not valid.'),
                ),
            ),
            array(
                'id: foo-10, 50-60, 50-bar;',
                'id',
                array(
                    new ValuesError('ranges[0].lower', 'This value is not valid.'),
                    new ValuesError('ranges[2].upper', 'This value is not valid.'),
                ),
            ),
        );
    }
}
