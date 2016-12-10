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

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\Input\FilterQuery\QueryException;
use Rollerworks\Component\Search\Input\FilterQueryInput;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

final class FilterQueryInputTest extends InputProcessorTestCase
{
    protected function getProcessor(callable $labelResolver = null)
    {
        return new FilterQueryInput($labelResolver);
    }

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideAliasedFieldsTests
     */
    public function it_processes_aliased_fields($input)
    {
        $labelResolver = function (FieldConfigInterface $field) {
            $name = $field->getName();

            if ($name === 'name') {
                return 'first-name';
            }

            return $name;
        };

        $processor = $this->getProcessor($labelResolver);
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, $input));
    }

    /**
     * This is a special case as the dash is also used for ranges.
     *
     * @test
     */
    public function it_parses_fieldNames_with_dash()
    {
        $fieldSet = $this->getFieldSet(false)->add('field-1', TextType::class)->getFieldSet();

        $processor = $this->getProcessor();
        $config = new ProcessorConfig($fieldSet);

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('field-1', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);

        self::assertEquals($condition, $processor->process($config, 'field-1: value, value2;'));
        self::assertEquals($condition, $processor->process($config, 'field-1: value, value2'));
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
        $values->addSimpleValue('value');
        $values->addSimpleValue('value"2');
        $values->addSimpleValue('!foo');
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        self::assertEquals($condition, $processor->process($config, 'name: "value", "value""2", "!foo";'));
    }

    public function testPatternMatchLexerNoEndLessLoop()
    {
        $config = new ProcessorConfig($this->getFieldSet());

        $e = QueryException::syntaxError(8, 0, ['*', '>', '<', '?', '!*', '!>', '!<', '!?', '=', '!='], '!');
        $error = $e->toErrorMessageObj();

        $this->assertConditionContainsErrors('name: ~!!*"value";', $config, [$error]);
    }

    /**
     * @param string   $input
     * @param int      $col
     * @param int      $line
     * @param string[] $expected
     * @param string   $got
     *
     * @throws \Exception When an unmatched exception is thrown
     *
     * @test
     * @dataProvider provideQueryExceptionTests
     */
    public function it_errors_when_the_syntax_is_invalid($input, $col, $line, $expected, $got)
    {
        $fieldSet = $this->getFieldSet(false)->add('field1', TextType::class)->getFieldSet();
        $config = new ProcessorConfig($fieldSet);

        $e = QueryException::syntaxError($col, $line, $expected, $got);
        $error = $e->toErrorMessageObj();

        $this->assertConditionContainsErrors($input, $config, [$error]);
    }

    public function provideQueryExceptionTests()
    {
        return [
            [
                'field1: value, value2, value3, value4, value5;)',
                46,
                0,
                ['(', 'FieldIdentification'],
                ')',
            ],
            [
                'field1: value value2)',
                14,
                0,
                [';', '|', ',', '|', ')'],
                'value2',
            ],
            // Ensure Rollerworks\Component\Search\Input\FilterQuery\Lexer::T_OPEN_PARENTHESIS is converted to '('
            [
                'field1: value, value2; *',
                -1,
                0,
                ['('],
                'end of string',
            ],
        ];
    }

    public function provideEmptyInputTests()
    {
        return [
            [''],
            [' '],
        ];
    }

    public function provideSingleValuePairTests()
    {
        return [
            ['name: value, value2, ٤٤٤٦٥٤٦٠٠, 30, 30L, !value3;'],
            ['name: value, value2, ٤٤٤٦٥٤٦٠٠, 30, 30L, !value3'],
        ];
    }

    public function provideMultipleValues()
    {
        return [
            ['name: value, value2; date:"12-16-2014";'],
            ['name: value, value2; date:"12-16-2014"'],
        ];
    }

    public function provideRangeValues()
    {
        return [
            ['id: 1-10, 15 - 30, ]100-200], 310-400[, !50-70; date:["12-16-2014"-"12-20-2014"];'],
        ];
    }

    public function provideComparisonValues()
    {
        return [
            ['id: >1, <2, <=5, >=8, <>20; date:>="12-16-2014";'],
        ];
    }

    public function provideMatcherValues()
    {
        return [
            ['name: ~*value, ~i>value2, ~<value3, ~?"^foo|bar?", ~!*value4, ~i!*value5, ~=value9, ~!=value10, ~i=value11, ~i!=value12;'],
        ];
    }

    public function provideGroupTests()
    {
        return [
            ['name: value, value2; (name: value3, value4;); *(name: value8, value10;);'],
            ['name: value, value2; (name: value3, value4); *(name: value8, value10)'],
            ['name: value, value2; (name: value3, value4); *(name: value8, value10;)'],
        ];
    }

    public function provideRootLogicalTests()
    {
        return [
            ['name: value, value2;'],
            ['*name: value, value2;', ValuesGroup::GROUP_LOGICAL_OR],
        ];
    }

    public function provideMultipleSubGroupTests()
    {
        return [
            ['(name: value, value2); (name: value3, "value4");'],
        ];
    }

    public function provideNestedGroupTests()
    {
        return [
            ['((name: value, value2;););'],
            ['((name: value, value2;);)'],
            ['((name: value, value2;))'],
            ['((name: value, value2))'],
        ];
    }

    public function provideAliasedFieldsTests()
    {
        return [
            ['first-name: value1; first-name: value, value2;'],
            ['first-name: value, value2;'],
        ];
    }

    public function provideValueOverflowTests()
    {
        return [
            ['first level' => 'name: value, value2, value3, value4, value5;', 'name', '[name][3]'],
            ['nested level' => '((name: value, value2, value3, value4, value5));', 'name', '[1][1][name][3]'],
            ['deeper level' => '((name: value); (name: value, value2, value3, value4, value5));', 'name', '[1][2][name][3]'],
            ['overwriting' => 'name: value1, value22; name: value, value2, value3, value4, value5;', 'name', '[name][3]'],
        ];
    }

    public function provideGroupsOverflowTests()
    {
        return [
            ['(name: value, value2;); (name: value, value2;); (name: value, value2;); (name: value, value2;)', ''],
            ['( ((name: value, value2)); ((name: value, value2;); (name: value, value2;); (name: value, value2;); (name: value, value2;)) )', '[1][2]'],
        ];
    }

    public function provideNestingLevelExceededTests()
    {
        return [
            ['((field2: value;))', '[1][1]'],
        ];
    }

    public function provideUnknownFieldTests()
    {
        return [
            ['field2: value;'],
        ];
    }

    public function provideUnsupportedValueTypeExceptionTests()
    {
        return [
            ['no-range-field: 1-12;', 'no-range-field', Range::class],
            ['no-compares-field: >12;', 'no-compares-field', Compare::class],
            ['no-matchers-field: ~>12;', 'no-matchers-field', PatternMatch::class],
        ];
    }

    public function provideInvalidRangeTests()
    {
        return [
            ['id: 30-10, 50-60, 40-20;', ['[id][0]', '[id][2]']],
            ['id: !30-10, !50-60, !40-20;', ['[id][0]', '[id][2]']],
        ];
    }

    public function provideInvalidValueTests()
    {
        return [
            [
                'id: foo, 30, bar, >life;',
                [
                    new ConditionErrorMessage('[id][0]', 'This value is not valid.'),
                    new ConditionErrorMessage('[id][2]', 'This value is not valid.'),
                    new ConditionErrorMessage('[id][3]', 'This value is not valid.'),
                ],
            ],
            [
                'id: foo-10, 50-60, 50-bar;',
                [
                    new ConditionErrorMessage('[id][0][lower]', 'This value is not valid.'),
                    new ConditionErrorMessage('[id][2][upper]', 'This value is not valid.'),
                ],
            ],
        ];
    }

    public function provideNestedErrorsTests()
    {
        return [
            ['date: 1;', [new ConditionErrorMessage('[date][0]', 'This value is not valid.')]],
            ['(date: 1;)', [new ConditionErrorMessage('[1][date][0]', 'This value is not valid.')]],
            ['((((((date: 1;))))))', [new ConditionErrorMessage('[1][1][1][1][1][1][date][0]', 'This value is not valid.')]],
        ];
    }
}
