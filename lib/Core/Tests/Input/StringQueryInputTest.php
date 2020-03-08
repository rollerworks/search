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
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\StringLexerException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\Input\StringLexer;
use Rollerworks\Component\Search\Input\StringQueryInput;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Rollerworks\Component\Search\ValueComparator;

/**
 * @internal
 */
final class StringQueryInputTest extends InputProcessorTestCase
{
    protected function getProcessor(callable $labelResolver = null): InputProcessor
    {
        return new StringQueryInput(null, $labelResolver);
    }

    protected function getFieldSet(bool $build = true, bool $order = false)
    {
        $fieldSet = parent::getFieldSet(false, $order);
        $field = $this->getFactory()->createField(
            'geo',
            TextType::class,
            [
                StringQueryInput::FIELD_LEXER_OPTION_NAME => function (StringLexer $lexer): string {
                    $result = $lexer->expects('(');
                    $result .= $lexer->expects('/-?\d+,\h*-?\d+/A', 'Geographic points 12,24');
                    $result .= $lexer->expects(')');

                    return $result;
                },
            ]
        );

        $field->setValueTypeSupport(Compare::class, true);
        $field->setValueTypeSupport(Range::class, true);
        $field->setValueTypeSupport(PatternMatch::class, false);
        $field->setValueComparator(new class() implements ValueComparator {
            public function isHigher($higher, $lower, array $options): bool
            {
                return false;
            }

            public function isLower($lower, $higher, array $options): bool
            {
                return true;
            }

            public function isEqual($value, $nextValue, array $options): bool
            {
                return false;
            }
        });

        $fieldSet->set($field);

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    /**
     * @param mixed $input
     *
     * @test
     * @dataProvider provideAliasedFieldsTests
     */
    public function it_processes_aliased_fields($input)
    {
        $labelResolver = function (FieldConfig $field) {
            $name = $field->getName();

            if ($name === 'name') {
                return 'first-name';
            }

            return $name;
        };

        $processor = $this->getProcessor($labelResolver);
        $config = new ProcessorConfig($this->getFieldSet());
        $config->setDefaultField('name');

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);

        $this->assertConditionEquals($input, $condition, $processor, $config);
    }

    /**
     * @test
     */
    public function it_expects_a_string_input()
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage((new UnexpectedTypeException([], 'string'))->getMessage());

        $processor->process($config, []);
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

        $e = StringLexerException::formatError(7, 1, 'Unknown operator flag, expected "i" and/or "!"');
        $error = $e->toErrorMessageObj();

        $this->assertConditionContainsErrors('name: ~!!*"value";', $config, [$error]);
    }

    /** @test */
    public function it_fails_with_unbound_values_and_no_default_field()
    {
        $config = new ProcessorConfig($this->getFieldSet());

        $e = new InputProcessorException('', 'No default field was configured.');
        $error = $e->toErrorMessageObj();

        $this->assertConditionContainsErrors('value;', $config, [$error]);
    }

    /**
     * @test
     */
    public function it_processes_with_customer_value_lexer()
    {
        $processor = new StringQueryInput();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('(12,24)');
        $values->add(new Compare('(12,24)', '>'));
        $values->add(new Range('(12,24)', '(12,25)'));
        $expectedGroup->addField('geo', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertConditionEquals('geo: (12,24), >(12,24), (12,24)~(12,25);', $condition, $processor, $config);
    }

    /**
     * @test
     * @dataProvider provideQueryExceptionTests
     */
    public function it_errors_when_the_syntax_is_invalid(string $input, StringLexerException $exception)
    {
        $fieldSet = $this->getFieldSet(false)->add('field1', TextType::class)->getFieldSet();
        $config = new ProcessorConfig($fieldSet);

        $error = $exception->toErrorMessageObj();
        $this->assertConditionContainsErrors($input, $config, [$error]);
    }

    public function provideQueryExceptionTests()
    {
        return [
            [
                'field1: value, value2, value3, value4, value5;)',
                StringLexerException::formatError(46, 1, 'Cannot close group as this field is not in a group'),
            ],
            [
                'field1: value; field1: ;',
                StringLexerException::formatError(23, 1, 'A field must have at least one value'),
            ],
            [
                'field1: value , ;',
                StringLexerException::formatError(16, 1, 'Values must be separated by a ",". A values list must end with ";" or ")"'),
            ],
            [
                '(field1: value, value2, value3, value4, value5;))',
                StringLexerException::formatError(48, 1, 'Cannot close group as this field is not in a group'),
            ],
            [
                '(field1: value, value2, value3, value4, value5;);)',
                StringLexerException::formatError(49, 1, 'Cannot close group as this field is not in a group'),
            ],
            [
                'field1: value, value2, value3, value4, value5; &',
                StringLexerException::formatError(47, 1, 'A group logical operator can only be used at the start of the input or before a group opening'),
            ],
            [
                "(field1: value, value2, value3, value4, value5; ); \n&",
                StringLexerException::formatError(52, 2, 'A group logical operator can only be used at the start of the input or before a group opening'),
            ],
            [
                'field1: value value2)',
                StringLexerException::formatError(20, 1, 'A value containing spaces must be surrounded by quotes'),
            ],
            [
                'field1: value, value2; *',
                StringLexerException::formatError(23, 1, 'A group logical operator can only be used at the start of the input or before a group opening'),
            ],
            // Customer value-lexer
            [
                'geo: value, value2;',
                StringLexerException::syntaxError(5, 1, ['('], 'value, val'),
            ],
            [
                'geo: (value, value2);',
                StringLexerException::syntaxError(6, 1, ['Geographic points 12,24'], 'value, val'),
            ],
            [
                'geo: (12, 24) ~ (value, value2);',
                StringLexerException::syntaxError(17, 1, ['Geographic points 12,24'], 'value, val'),
            ],
        ];
    }

    /**
     * @test
     *
     * @see https://github.com/rollerworks/search/issues/246
     */
    public function it_parses_group_logical_when_group_is_provided_first_in()
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder();
        $fieldSet->add('title', TextType::class);
        $fieldSet->add('subtitle', TextType::class);
        $fieldSet->add('teaser', TextType::class);
        $fieldSet = $fieldSet->getFieldSet();

        $processor = $this->getProcessor();
        $config = new ProcessorConfig($fieldSet);

        $condition = SearchConditionBuilder::create($fieldSet)
            ->group(ValuesGroup::GROUP_LOGICAL_OR)
                ->field('title')
                    ->addSimpleValue('paris')
                ->end()
                ->field('subtitle')
                    ->addSimpleValue('paris')
                ->end()
                ->field('teaser')
                    ->addSimpleValue('paris')
                ->end()
            ->end()
            ->getSearchCondition();

        self::assertEquals($condition, $processor->process($config, '*(title:paris;subtitle:paris;teaser:paris)'));
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
            ['name: value, value2, ٤٤٤٦٥٤٦٠٠, 30, 30L, !value3; @date: uP', ['@date' => 'ASC']],
            ['name: value, value2, ٤٤٤٦٥٤٦٠٠, 30, 30L, !value3; @date: Up', ['@date' => 'ASC']],
            ['name: value, value2, ٤٤٤٦٥٤٦٠٠, 30, 30L, !value3;', ['@date' => 'DESC', '@id' => 'ASC']],
            ['', ['@date' => 'DESC', '@id' => 'ASC']],
        ];
    }

    public function provideMultipleValues()
    {
        return [
            ['name: value, value2; date: "12-16-2014";'],
            ['name: value, value2; date: "12-16-2014"'],
            ['value1; date: "12-16-2014"; value, value2'], // Possible, but not recommended
        ];
    }

    public function provideRangeValues()
    {
        return [
            ['id: 1~10, 15 ~ 30, ] 100~200 ], 310~400[, !50~70; date: [12-16-2014 ~ 12-20-2014];'],
            ['1~10, 15 ~ 30, ] 100~200 ], 310~400[, !50~70; date: [12-16-2014 ~ 12-20-2014];'],
        ];
    }

    public function provideComparisonValues()
    {
        return [
            ['id: >1, <2, <=5, >=8, <>20; date: >="12-16-2014";'],
            ['>1, <2, <=5, >=8, <>20; date: >="12-16-2014";'],
        ];
    }

    public function provideMatcherValues()
    {
        return [
            ['name: ~*value, ~i>value2, ~<value3, ~!*value4, ~i!*value5, ~=value9, ~!=value10, ~i=value11, ~i!=value12;'],
        ];
    }

    public function provideGroupTests()
    {
        return [
            ['name: value, value2; (name: value3, value4;); *(name: value8, value10;);'],
            ['name: value, value2; (name: value3, value4); *(name: value8, value10)'],
            ['name: value, value2; (name: value3, value4); *(name: value8, value10;)'],
            ['value, value2; (value3, value4); *(value8, value10;)'],
        ];
    }

    public function provideRootLogicalTests()
    {
        return [
            ['name: value, value2;'],
            ['value, value2;'],
            ['*name: value, value2;', ValuesGroup::GROUP_LOGICAL_OR],
            ['*value, value2;', ValuesGroup::GROUP_LOGICAL_OR],
        ];
    }

    public function provideMultipleSubGroupTests()
    {
        return [
            ['(name: value, value2); (name: value3, "value4");'],
            ['(name: value, value2); (value3, "value4");'],
        ];
    }

    public function provideNestedGroupTests()
    {
        return [
            ['((name: value, value2;););'],
            ['((name: value, value2;);)'],
            ['((name: value, value2;))'],
            ['((name: value, value2))'],
            ['((value, value2))'],
        ];
    }

    public function provideAliasedFieldsTests()
    {
        return [
            ['first-name: value1; first-name: value, value2;'],
            ['first-name: value, value2;'],
            ['value, value2;'],
        ];
    }

    public function provideValueOverflowTests()
    {
        return [
            ['first level' => 'name: value, value2, value3, value4, value5;', 'name', '[name][3]'],
            ['nested level' => '((name: value, value2, value3, value4, value5));', 'name', '[0][0][name][3]'],
            ['deeper level' => '((name: value); (name: value, value2, value3, value4, value5));', 'name', '[0][1][name][3]'],
            ['overwriting' => 'name: value1, value22; name: value, value2, value3, value4, value5;', 'name', '[name][3]'],
        ];
    }

    public function provideGroupsOverflowTests()
    {
        return [
            ['(name: value, value2;); (name: value, value2;); (name: value, value2;); (name: value, value2;)', ''],
            ['( ((name: value, value2)); ((name: value, value2;); (name: value, value2;); (name: value, value2;); (name: value, value2;)) )', '[0][1]'],
        ];
    }

    public function provideNestingLevelExceededTests()
    {
        return [
            ['((field2: value;))', '[0][0]'],
        ];
    }

    public function providePrivateFieldTests()
    {
        return [
            ['_id: 1;', '_id'],
            ['id: 1; _id: 2;', '_id'],
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
            ['no-range-field: 1~12;', 'no-range-field', Range::class],
            ['no-compares-field: >12;', 'no-compares-field', Compare::class],
            ['no-matchers-field: ~>12;', 'no-matchers-field', PatternMatch::class],
        ];
    }

    public function provideInvalidRangeTests()
    {
        return [
            ['id: 30~10, 50~60, 40~20;', ['[id][0]', '[id][2]']],
            ['id: !30~10, !50~60, !40~20;', ['[id][0]', '[id][2]']],
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
                'id: foo~10, 50~60, 50~bar;',
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
            ['(date: 1;)', [new ConditionErrorMessage('[0][date][0]', 'This value is not valid.')]],
            ['((((((date: 1;))))))', [new ConditionErrorMessage('[0][0][0][0][0][0][date][0]', 'This value is not valid.')]],
        ];
    }
}
