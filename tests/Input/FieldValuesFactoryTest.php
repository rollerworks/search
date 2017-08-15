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

use Prophecy\Argument;
use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Input\FieldValuesFactory;
use Rollerworks\Component\Search\Input\NullValidator;
use Rollerworks\Component\Search\Input\Validator;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\ValueComparator;

/**
 * @internal
 */
final class FieldValuesFactoryTest extends SearchIntegrationTestCase
{
    /** @var ErrorList */
    private $errorList;

    /** @test */
    public function it_adds_simple_values_when_valid()
    {
        $factory = $this->createValuesFactory();
        $valuesBag = $this->initContext($factory);

        $factory->addSimpleValue('foobar', 'values[%d]');
        $factory->addSimpleValue('bar', 'values[%d]');

        $this->assertNoErrors();

        self::assertEquals(['foobar', 'bar'], $valuesBag->getSimpleValues());
    }

    /** @test */
    public function it_adds_simple_excluded_values_when_valid()
    {
        $factory = $this->createValuesFactory();
        $valuesBag = $this->initContext($factory);

        $factory->addExcludedSimpleValue('foobar', 'values[%d]');
        $factory->addExcludedSimpleValue('bar', 'values[%d]');

        $this->assertNoErrors();

        self::assertEquals(['foobar', 'bar'], $valuesBag->getExcludedSimpleValues());
    }

    /** @test */
    public function it_adds_ranges_when_valid()
    {
        $factory = $this->createValuesFactory();
        $valuesBag = $this->initContext($factory, $this->createField(IntegerType::class));

        $factory->addRange('10', '100', true, true, ['values[%d]', 'lower', 'upper']);
        $factory->addRange('20', '60', false, true, ['values[%d]', 'lower', 'upper']);
        $factory->addRange('200', '600', false, false, ['values[%d]', 'lower', 'upper']);

        $this->assertNoErrors();

        self::assertEmpty($valuesBag->get(ExcludedRange::class));
        self::assertEquals(
            [
                new Range(10, 100, true, true),
                new Range(20, 60, false, true),
                new Range(200, 600, false, false),
            ],
            $valuesBag->get(Range::class)
        );
    }

    /** @test */
    public function it_adds_excluded_ranges_when_valid()
    {
        $factory = $this->createValuesFactory();
        $valuesBag = $this->initContext($factory, $this->createField(IntegerType::class));

        $factory->addExcludedRange('10', '100', true, true, ['values[%d]', 'lower', 'upper']);
        $factory->addExcludedRange('20', '60', false, true, ['values[%d]', 'lower', 'upper']);
        $factory->addExcludedRange('200', '600', false, false, ['values[%d]', 'lower', 'upper']);

        $this->assertNoErrors();

        self::assertEmpty($valuesBag->get(Range::class));
        self::assertEquals(
            [
                new ExcludedRange(10, 100, true, true),
                new ExcludedRange(20, 60, false, true),
                new ExcludedRange(200, 600, false, false),
            ],
            $valuesBag->get(ExcludedRange::class)
        );
    }

    /** @test */
    public function it_adds_comparisons_value_when_valid()
    {
        $factory = $this->createValuesFactory();
        $valuesBag = $this->initContext($factory, $this->createField(IntegerType::class));

        $factory->addComparisonValue('>', '100', ['values[%d]', 'operator', 'value']);
        $factory->addComparisonValue('>=', '10', ['values[%d]', 'operator', 'value']);

        $this->assertNoErrors();

        self::assertEquals(
            [
                new Compare(100, '>'),
                new Compare(10, '>='),
            ],
            $valuesBag->get(Compare::class)
        );
    }

    /** @test */
    public function it_produces_errors_when_compare_operator_input_is_invalid()
    {
        $factory = $this->createValuesFactory();
        $valuesBag = $this->initContext($factory, $this->createField(IntegerType::class));

        $factory->addComparisonValue('>', '100', ['values[%d].', 'operator', 'value']);
        $factory->addComparisonValue('??', '100', ['values[%d].', 'operator', 'value']);
        $factory->addComparisonValue(['>'], '100', ['values[%d].', 'operator', 'value']);

        $this->assertProducedErrors(
            [
                ConditionErrorMessage::withMessageTemplate(
                    'root/values[1].operator',
                    'Unknown Comparison operator "{{ operator }}".',
                    ['{{ operator }}' => '??']
                ),
                ConditionErrorMessage::withMessageTemplate(
                    'root/values[2].operator',
                    'Unknown Comparison operator "{{ operator }}".',
                    ['{{ operator }}' => gettype([])]
                ),
            ]
        );

        self::assertEquals([new Compare(100, '>')], $valuesBag->get(Compare::class));
    }

    /** @test */
    public function it_adds_pattern_matchers_when_valid()
    {
        $factory = $this->createValuesFactory();
        $valuesBag = $this->initContext($factory, $this->createField());

        $factory->addPatterMatch(PatternMatch::PATTERN_CONTAINS, 'foo-bar', false, ['values[%d]', 'type', 'value']);
        $factory->addPatterMatch(PatternMatch::PATTERN_CONTAINS, 'foobar', true, ['values[%d]', 'type', 'value']);

        $this->assertNoErrors();

        self::assertEquals(
            [
                new PatternMatch('foo-bar', PatternMatch::PATTERN_CONTAINS, false),
                new PatternMatch('foobar', PatternMatch::PATTERN_CONTAINS, true),
            ],
            $valuesBag->get(PatternMatch::class)
        );
    }

    /** @test */
    public function it_produces_errors_when_pattern_match_input_is_invalid()
    {
        $factory = $this->createValuesFactory();
        $valuesBag = $this->initContext($factory, $this->createField());

        $factory->addPatterMatch(PatternMatch::PATTERN_CONTAINS, ['foo-bar'], false, ['values[%d].', 'value', 'type']);
        $factory->addPatterMatch([PatternMatch::PATTERN_CONTAINS], 'foo-bar', false, ['values[%d].', 'value', 'type']);
        $factory->addPatterMatch('boo', 'foo-bar', false, ['values[%d].', 'value', 'type']);
        $factory->addPatterMatch(PatternMatch::PATTERN_CONTAINS, 'foobar', true, ['values[%d].', 'value', 'type']);

        $this->assertProducedErrors(
            [
                new ConditionErrorMessage('root/values[0].value', 'PatternMatch value must a string.'),
                new ConditionErrorMessage('root/values[1].type', 'PatternMatch type must a string.'),
                ConditionErrorMessage::withMessageTemplate(
                    'root/values[2].type',
                    'Unknown PatternMatch type "{{ type }}".',
                    ['{{ type }}' => 'boo']
                ),
            ]
        );

        self::assertEquals(
            [
                new PatternMatch('foobar', PatternMatch::PATTERN_CONTAINS, true),
            ],
            $valuesBag->get(PatternMatch::class)
        );
    }

    /** @test */
    public function it_ignores_value_when_validation_fails()
    {
        $validator = $this->prophesize(Validator::class);
        $validator->initializeContext(Argument::any(), Argument::any())->shouldBeCalledTimes(1);

        // $value, string $type, $originalValue, string $path

        // Simple
        $validator->validate('foobar', 'simple', 'foobar', 'root/values[0]')->willReturn(true);
        $validator->validate(10, 'simple', '10', 'root/values[1]')->willReturn(false);

        $validator->validate('foobar', 'excluded-simple', 'foobar', 'root/values[2]')->willReturn(true);
        $validator->validate(10, 'excluded-simple', '10', 'root/values[3]')->willReturn(false);

        // Ranges (both bound validated, even when first is invalid)
        $validator->validate(10, Range::class, '10', 'root/values[4].lower')->willReturn(true);
        $validator->validate(100, Range::class, '100', 'root/values[4].upper')->willReturn(true);

        $validator->validate(100, Range::class, '100', 'root/values[5].lower')->willReturn(false);
        $validator->validate(200, Range::class, '200', 'root/values[5].upper')->willReturn(true)->shouldBeCalled();

        $validator->validate(100, Range::class, '100', 'root/values[6].lower')->shouldNotBeCalled();
        $validator->validate(10, Range::class, '10', 'root/values[6].upper')->shouldNotBeCalled();

        // ExcludedRanges (both bound validated, even when first is invalid)
        $validator->validate(10, ExcludedRange::class, '10', 'root/values[7].lower')->willReturn(true);
        $validator->validate(100, ExcludedRange::class, '100', 'root/values[7].upper')->willReturn(true);

        $validator->validate(100, ExcludedRange::class, '100', 'root/values[8].lower')->willReturn(true);
        $validator->validate(200, ExcludedRange::class, '200', 'root/values[8].upper')->willReturn(false)->shouldBeCalled();

        // Compare
        $validator->validate(100, Compare::class, '100', 'root/values[9].value')->willReturn(true);
        $validator->validate(10, Compare::class, '10', 'root/values[10].value')->willReturn(false);

        // PatternMatch
        $validator->validate('foo-bar', PatternMatch::class, 'foo-bar', 'root/values[11].value')->willReturn(true);
        $validator->validate('boor', PatternMatch::class, 'boor', 'root/values[12].value')->willReturn(false);

        $field = $this->createFullySupportedField();
        $field->finalizeConfig();

        $factory = $this->createValuesFactory($validator->reveal());
        $valuesBag = $this->initContext($factory, $field);

        $factory->addSimpleValue('foobar', 'values[%d]');
        $factory->addSimpleValue('10', 'values[%d]'); // invalid
        $factory->addExcludedSimpleValue('foobar', 'values[%d]');
        $factory->addExcludedSimpleValue('10', 'values[%d]'); // invalid

        $factory->addRange('10', '100', true, true, ['values[%d]', '.lower', '.upper']);
        $factory->addRange('100', '200', true, true, ['values[%d]', '.lower', '.upper']); // invalid
        $factory->addRange('100', '10', true, true, ['values[%d]', '.lower', '.upper']); // invalid, but not trough validator
        $factory->addExcludedRange('10', '100', true, true, ['values[%d]', '.lower', '.upper']);
        $factory->addExcludedRange('100', '200', true, true, ['values[%d]', '.lower', '.upper']); // invalid

        $factory->addComparisonValue('>', '100', ['values[%d]', '.operator', '.value']);
        $factory->addComparisonValue('>=', '10', ['values[%d]', '.operator', '.value']); // invalid

        $factory->addPatterMatch(PatternMatch::PATTERN_CONTAINS, 'foo-bar', false, ['values[%d]', '.value', '.type']);
        $factory->addPatterMatch(PatternMatch::PATTERN_CONTAINS, 'boor', false, ['values[%d]', '.value', '.type']); // invalid

        $this->assertProducedErrors(
            [
                ConditionErrorMessage::withMessageTemplate(
                    'root/values[6]',
                    'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.',
                    ['{{ lower }}' => '100', '{{ upper }}' => '10']
                ),
            ]
        );

        self::assertEquals(['foobar'], $valuesBag->getSimpleValues());
        self::assertEquals(['foobar'], $valuesBag->getExcludedSimpleValues());
        self::assertEquals([new Range(10, 100)], $valuesBag->get(Range::class));
        self::assertEquals([new ExcludedRange(10, 100)], $valuesBag->get(ExcludedRange::class));
        self::assertEquals([new Compare(100, '>')], $valuesBag->get(Compare::class));
        self::assertEquals([new PatternMatch('foo-bar', PatternMatch::PATTERN_CONTAINS, false)], $valuesBag->get(PatternMatch::class));
    }

    /** @test */
    public function its_values_increase_the_values_count()
    {
        $field = $this->createFullySupportedField();
        $factory = $this->createValuesFactory(null, 2);

        $tester = function (\Closure $adder, string $type) use ($factory, $field) {
            try {
                $this->initContext($factory, $field);

                $adder($factory);

                $this->fail('Type "'.$type.'" did not increase values count.');
            } catch (ValuesOverflowException $e) {
                self::assertEquals(
                    (new ValuesOverflowException('field-name', 2, 'root/values[2]'))->getMessage(),
                    $e->getMessage()
                );
            }
        };

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addSimpleValue(10, 'values[%d]');
                $factory->addSimpleValue(20, 'values[%d]');
                $factory->addSimpleValue(30, 'values[%d]');
            },
            'simple'
        );

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addExcludedSimpleValue(10, 'values[%d]');
                $factory->addExcludedSimpleValue(20, 'values[%d]');
                $factory->addExcludedSimpleValue(30, 'values[%d]');
            },
            'simple-excluded'
        );

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addRange(10, 50, true, true, ['values[%d]', '.lower', '.upper']);
                $factory->addRange(60, 80, true, true, ['values[%d]', '.lower', '.upper']);
                $factory->addRange(100, 200, true, true, ['values[%d]', '.lower', '.upper']);
            },
            'range'
        );

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addExcludedRange(10, 50, true, true, ['values[%d]', '.lower', '.upper']);
                $factory->addExcludedRange(60, 80, true, true, ['values[%d]', '.lower', '.upper']);
                $factory->addExcludedRange(100, 200, true, true, ['values[%d]', '.lower', '.upper']);
            },
            'excluded-range'
        );

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addComparisonValue('>', 10, ['values[%d]', '.operator', '.value']);
                $factory->addComparisonValue('<', 80, ['values[%d]', '.operator', '.value']);
                $factory->addComparisonValue('>', 100, ['values[%d]', '.operator', '.value']);
            },
            'compare'
        );

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addPatterMatch('CONTAINS', 'boo', false, ['values[%d]', '.value', '.type']);
                $factory->addPatterMatch('CONTAINS', 'bar', false, ['values[%d]', '.value', '.type']);
                $factory->addPatterMatch('CONTAINS', 'moo', false, ['values[%d]', '.value', '.type']);
            },
            'pattern'
        );
    }

    /** @test */
    public function its_checks_value_type_is_supported()
    {
        $field = $this->createField();
        $field->setValueTypeSupport(Range::class, false);
        $field->setValueTypeSupport(Compare::class, false);
        $field->setValueTypeSupport(PatternMatch::class, false);

        $factory = $this->createValuesFactory(null, 2);

        $tester = function (\Closure $caller, string $type) use ($factory, $field) {
            $this->initContext($factory, $field);

            try {
                $caller($factory);

                $this->fail('Type "'.$type.'" is not validated.');
            } catch (UnsupportedValueTypeException $e) {
                self::assertEquals(
                    (new UnsupportedValueTypeException('field-name', $type))->getMessage(),
                    $e->getMessage()
                );
            }
        };

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addRange(100, 200, true, true, ['values[%d]', '.lower', '.upper']);
            },
            Range::class
        );

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addExcludedRange(100, 200, true, true, ['values[%d]', '.lower', '.upper']);
            },
            Range::class
        );

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addComparisonValue('>', 100, ['values[%d]', '.operator', '.value']);
            },
            Compare::class
        );

        $tester(
            function (FieldValuesFactory $factory) {
                $factory->addPatterMatch('CONTAINS', 'moo', false, ['values[%d]', '.value', '.type']);
            },
            PatternMatch::class
        );
    }

    /** @test */
    public function it_produces_errors_when_string_transformation_is_not_possible()
    {
        $factory = $this->createValuesFactory();

        // Field with no transformers.
        $field = $this->createFullySupportedField();
        $field->setViewTransformer(null);
        $field->setNormTransformer(null);

        $valuesBag = $this->initContext($factory, $field);

        $factory->addSimpleValue(['foobar'], 'values[%d]');
        $factory->addSimpleValue('10', 'values[%d]');
        $factory->addExcludedSimpleValue(['foobar'], 'values[%d]');
        $factory->addExcludedSimpleValue('10', 'values[%d]');

        $factory->addRange('10', '100', true, true, ['values[%d]', '.lower', '.upper']);
        $factory->addRange(['100'], '200', true, true, ['values[%d]', '.lower', '.upper']);
        $factory->addRange('100', ['200'], true, true, ['values[%d]', '.lower', '.upper']);

        $factory->addExcludedRange('10', '100', true, true, ['values[%d]', '.lower', '.upper']);
        $factory->addExcludedRange(['100'], '200', true, true, ['values[%d]', '.lower', '.upper']);
        $factory->addExcludedRange('100', ['200'], true, true, ['values[%d]', '.lower', '.upper']);
        $factory->addExcludedRange('15', '20', true, true, ['values[%d]', '.lower', '.upper']);

        $factory->addComparisonValue('>=', ['10'], ['values[%d]', '.operator', '.value']);
        $factory->addComparisonValue('>', '100', ['values[%d]', '.operator', '.value']);

        $this->assertProducedErrors(
            [
                ConditionErrorMessage::withMessageTemplate('root/values[0]', 'This value is not valid.'),
                ConditionErrorMessage::withMessageTemplate('root/values[2]', 'This value is not valid.'),
                ConditionErrorMessage::withMessageTemplate('root/values[5].lower', 'This value is not valid.'),
                ConditionErrorMessage::withMessageTemplate('root/values[6].upper', 'This value is not valid.'),
                ConditionErrorMessage::withMessageTemplate('root/values[8].lower', 'This value is not valid.'),
                ConditionErrorMessage::withMessageTemplate('root/values[9].upper', 'This value is not valid.'),
                ConditionErrorMessage::withMessageTemplate('root/values[11].value', 'This value is not valid.'),
            ]
        );

        self::assertEquals([10], $valuesBag->getSimpleValues());
        self::assertEquals([10], $valuesBag->getExcludedSimpleValues());
        self::assertEquals([new Range('10', '100')], $valuesBag->get(Range::class));
        self::assertEquals([new ExcludedRange('10', '100'), new ExcludedRange('15', '20')], $valuesBag->get(ExcludedRange::class));
        self::assertEquals([new Compare('100', '>')], $valuesBag->get(Compare::class));
    }

    /** @test */
    public function it_produces_errors_when_reverse_transformation_is_not_possible()
    {
        $factory = $this->createValuesFactory();
        $valuesBag = $this->initContext(
            $factory,
            $this->createField(
                IntegerType::class,
                ['invalid_message' => 'No, this value is not valid!', 'invalid_message_parameters' => ['he' => 'you']]
            )
        );

        $factory->addSimpleValue(['foobar'], 'values[%d]');
        $factory->addSimpleValue('10', 'values[%d]');

        $this->assertProducedErrors(
            [
                ConditionErrorMessage::withMessageTemplate('root/values[0]', 'No, this value is not valid!', ['he' => 'you']),
            ]
        );

        self::assertEquals([10], $valuesBag->getSimpleValues());
    }

    private function createValuesFactory(Validator $validator = null, int $valuesLimit = 100): FieldValuesFactory
    {
        $this->errorList = new ErrorList();
        $validator = $validator ?? new NullValidator();

        return new FieldValuesFactory($this->errorList, $validator, $valuesLimit);
    }

    private function createField(string $type = null, array $options = []): FieldConfig
    {
        return $this->getFactory()->createField('field-name', $type ?? TextType::class, $options);
    }

    private function initContext(FieldValuesFactory $factory, FieldConfig $field = null): ValuesBag
    {
        $factory->initContext($field ?? $this->createField(), $valuesBag = new ValuesBag(), 'root/');

        return $valuesBag;
    }

    private function assertNoErrors()
    {
        if ($this->errorList->count()) {
            dump($this->errorList->getArrayCopy());

            $this->fail('ErrorList has unexpected messages.');
        }
    }

    /**
     * @param ConditionErrorMessage[] $errors
     */
    private function assertProducedErrors(array $errors)
    {
        if (!$this->errorList->count()) {
            $this->fail('ErrorList should contain messages.');
        }

        $errorsList = $this->errorList->getArrayCopy();

        foreach ($errorsList as $error) {
            // Remove cause to make assertion possible.
            $error->cause = null;
        }

        self::assertEquals($errors, $errorsList);
    }

    private function createFullySupportedField(): FieldConfig
    {
        $field = $this->createField();
        $field->setValueTypeSupport(Range::class, true);
        $field->setValueTypeSupport(Compare::class, true);
        $field->setViewTransformer(
            new class() implements DataTransformer {
                public function transform($value)
                {
                    return null;
                }

                public function reverseTransform($value)
                {
                    return ctype_digit($value) ? (int) $value : $value;
                }
            }
        );
        $field->setValueComparator(
            new class() implements ValueComparator {
                public function isHigher($higher, $lower, array $options): bool
                {
                    return $higher > $lower;
                }

                public function isLower($lower, $higher, array $options): bool
                {
                    return $lower < $higher;
                }

                public function isEqual($value, $nextValue, array $options): bool
                {
                    return $value === $nextValue;
                }
            }
        );

        return $field;
    }
}
