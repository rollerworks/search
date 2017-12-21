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

use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\GenericFieldSetBuilder;
use Rollerworks\Component\Search\Input\NormStringQueryInput;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Testing for the NormStringQueryInput.
 *
 * Note that NormStringQueryInput derives from StringInput
 * and most of this logic is already tested by StringQueryInputTest.
 *
 * @internal
 */
final class NormStringQueryInputTest extends SearchIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = new GenericFieldSetBuilder($this->getFactory());
        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('name', TextType::class);
        $fieldSet->add('lastname', TextType::class);
        $fieldSet->add('date', DateType::class, ['pattern' => 'MM-dd-yyyy']);
        $fieldSet->set(
            $this->getFactory()->createField('no-range-field', IntegerType::class)
                ->setValueTypeSupport(Range::class, false)
        );

        $fieldSet->set(
            $this->getFactory()->createField('no-compares-field', IntegerType::class)->setValueTypeSupport(
                Compare::class,
                false
            )
        );

        $fieldSet->set(
            $this->getFactory()->createField('no-matchers-field', IntegerType::class)->setValueTypeSupport(
                PatternMatch::class,
                false
            )
        );

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    /**
     * @param string $input
     *
     * @test
     * @dataProvider provideMultipleValues
     */
    public function it_processes_multiple_fields(string $input)
    {
        $processor = new NormStringQueryInput();
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
        $this->assertConditionEquals($input, $condition, $processor, $config);
    }

    public function provideMultipleValues()
    {
        return [
            ['name: value, value2; date: "2014-12-16 00:00:00 UTC";'],
            ['name: value, value2; date: "2014-12-16 00:00:00"'],
        ];
    }

    /**
     * @test
     */
    public function it_errors_when_the_field_does_not_exist_in_fieldset()
    {
        $config = new ProcessorConfig($this->getFieldSet());

        $e = new UnknownFieldException('field2');
        $error = $e->toErrorMessageObj();

        try {
            $processor = new NormStringQueryInput();
            $processor->process($config, 'field2: value;');

            $this->fail('Condition should be invalid.');
        } catch (\Exception $e) {
            /* @var InvalidSearchConditionException $e */
            self::detectSystemException($e);
            self::assertInstanceOf(InvalidSearchConditionException::class, $e);
            self::assertEquals([$error], $e->getErrors());
        }
    }
}
