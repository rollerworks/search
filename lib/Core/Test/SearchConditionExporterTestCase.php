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

namespace Rollerworks\Component\Search\Test;

use Rollerworks\Component\Search\ConditionExporter;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\MoneyType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\GenericFieldSetBuilder;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

// TODO Add some tests with empty fields and groups (and they should be able to process)

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class SearchConditionExporterTestCase extends SearchIntegrationTestCase
{
    abstract protected function getExporter(): ConditionExporter;

    abstract protected function getInputProcessor(): InputProcessor;

    protected function getFieldSet(bool $build = true)
    {
        $priceField = $this->getFactory()->createField('price', MoneyType::class);
        $priceField->setNormTransformer(null);
        $priceField->setViewTransformer(null);

        $fieldSet = new GenericFieldSetBuilder($this->getFactory());
        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('name', TextType::class);
        $fieldSet->add('lastname', TextType::class);
        $fieldSet->add('date', DateType::class, ['pattern' => 'MM-dd-yyyy']);
        $fieldSet->set($priceField);

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    /** @test */
    public function it_exporters_values(): void
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value ');
        $values->addSimpleValue('-value2');
        $values->addSimpleValue('value2-');
        $values->addSimpleValue('10.00');
        $values->addSimpleValue('10,00');
        $values->addSimpleValue('hÌ');
        $values->addSimpleValue('٤٤٤٦٥٤٦٠٠');
        $values->addSimpleValue('doctor"who""');
        $values->addExcludedSimpleValue('value3');
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideSingleValuePairTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideSingleValuePairTest(), $condition, $processor, $config);
    }

    abstract public function provideSingleValuePairTest();

    /** @test */
    public function it_exporters_multiple_fields(): void
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('name', $values);

        $date = new \DateTimeImmutable('2014-12-16 00:00:00 UTC');

        $values = new ValuesBag();
        $values->addSimpleValue($date);
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideMultipleValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideMultipleValuesTest(), $condition, $processor, $config);
    }

    abstract public function provideMultipleValuesTest();

    /** @test */
    public function it_ignores_private_fields(): void
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSimpleValue('value');
        $values->addSimpleValue('value2');
        $expectedGroup->addField('name', $values);

        $date = new \DateTimeImmutable('2014-12-16 00:00:00 UTC');

        $values = new ValuesBag();
        $values->addSimpleValue($date);
        $expectedGroup->addField('date', $values);

        $values = new ValuesBag();
        $values->addSimpleValue(1);
        $expectedGroup->addField('_id', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideMultipleValuesTest(), $exporter->exportCondition($condition));
    }

    abstract public function providePrivateFieldsTest();

    /** @test */
    public function it_exporters_range_values(): void
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->add(new Range(1, 10));
        $values->add(new Range(15, 30));
        $values->add(new Range(100, 200, false));
        $values->add(new Range(310, 400, true, false));
        $values->add(new ExcludedRange(50, 70));
        $expectedGroup->addField('id', $values);

        $date = new \DateTimeImmutable('2014-12-16 00:00:00 UTC');
        $date2 = new \DateTimeImmutable('2014-12-20 00:00:00 UTC');

        $values = new ValuesBag();
        $values->add(new Range($date, $date2, true, true));
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideRangeValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideRangeValuesTest(), $condition, $processor, $config);
    }

    abstract public function provideRangeValuesTest();

    /** @test */
    public function it_exporters_comparisons(): void
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->add(new Compare(1, '>'));
        $values->add(new Compare(2, '<'));
        $values->add(new Compare(5, '<='));
        $values->add(new Compare(8, '>='));
        $expectedGroup->addField('id', $values);

        $date = new \DateTimeImmutable('2014-12-16 00:00:00 UTC');

        $values = new ValuesBag();
        $values->add(new Compare($date, '>='));
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideComparisonValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideComparisonValuesTest(), $condition, $processor, $config);
    }

    abstract public function provideComparisonValuesTest();

    /** @test */
    public function it_exporters_matchers(): void
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->add(new PatternMatch('value', PatternMatch::PATTERN_CONTAINS));
        $values->add(new PatternMatch('value2', PatternMatch::PATTERN_STARTS_WITH, true));
        $values->add(new PatternMatch('value3', PatternMatch::PATTERN_ENDS_WITH));
        $values->add(new PatternMatch('value4', PatternMatch::PATTERN_NOT_CONTAINS));
        $values->add(new PatternMatch('value5', PatternMatch::PATTERN_NOT_CONTAINS, true));
        $values->add(new PatternMatch('value9', PatternMatch::PATTERN_EQUALS));
        $values->add(new PatternMatch('value10', PatternMatch::PATTERN_NOT_EQUALS));
        $values->add(new PatternMatch('value11', PatternMatch::PATTERN_EQUALS, true));
        $values->add(new PatternMatch('value12', PatternMatch::PATTERN_NOT_EQUALS, true));
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideMatcherValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideMatcherValuesTest(), $condition, $processor, $config);
    }

    abstract public function provideMatcherValuesTest();

    /** @test */
    public function it_exporters_groups(): void
    {
        $exporter = $this->getExporter();
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
        $this->assertExportEquals($this->provideGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideGroupTest(), $condition, $processor, $config);
    }

    abstract public function provideGroupTest();

    /** @test */
    public function it_exporters_multiple_subgroups(): void
    {
        $exporter = $this->getExporter();
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
        $this->assertExportEquals($this->provideMultipleSubGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideMultipleSubGroupTest(), $condition, $processor, $config);
    }

    abstract public function provideMultipleSubGroupTest();

    /** @test */
    public function it_exporters_nested_subgroups(): void
    {
        $exporter = $this->getExporter();
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
        $this->assertExportEquals($this->provideNestedGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideNestedGroupTest(), $condition, $processor, $config);
    }

    abstract public function provideNestedGroupTest();

    /** @test */
    public function it_exporters_with_empty_fields(): void
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideEmptyValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideEmptyValuesTest(), $condition, $processor, $config);
    }

    abstract public function provideEmptyValuesTest();

    /** @test */
    public function it_exporters_with_empty_group(): void
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addGroup(new ValuesGroup());

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideEmptyGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideEmptyGroupTest(), $condition, $processor, $config);
    }

    abstract public function provideEmptyGroupTest();

    protected function assertExportEquals($expected, $actual): void
    {
        self::assertEquals($expected, $actual);
    }
}
