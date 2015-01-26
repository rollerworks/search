<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Exporter;

use Prophecy;
use Prophecy\Prophecy\ObjectProphecy;
use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\InputProcessorInterface;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

abstract class SearchConditionExporterTestCase extends SearchIntegrationTestCase
{
    /**
     * @var ObjectProphecy
     */
    protected $fieldAliasResolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->fieldAliasResolver = $this->prophet->prophesize('Rollerworks\Component\Search\FieldAliasResolverInterface');
        $this->fieldAliasResolver->resolveFieldName(Prophecy\Argument::any(), Prophecy\Argument::any())->will(
            function ($args) {
                return $args[1];
            }
        );
    }

    /**
     * @return ExporterInterface
     */
    abstract protected function getExporter();

    /**
     * @return InputProcessorInterface
     */
    abstract protected function getInputProcessor();

    /**
     * {@inheritdoc}
     */
    protected function getFieldSet($build = true)
    {
        $fieldSet = new FieldSetBuilder('test', $this->getFactory());
        $fieldSet->add($this->getFactory()->createField('id', 'integer')->setAcceptRange(true)->setAcceptCompares(true));
        $fieldSet->add($this->getFactory()->createField('name', 'text')->setAcceptPatternMatch(true));
        $fieldSet->add($this->getFactory()->createField('lastname', 'text'));
        $fieldSet->add(
            $this->getFactory()->createField('date', 'date', array('format' => 'MM-dd-yyyy'))
              ->setAcceptRange(true)
              ->setAcceptCompares(true)
        );

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    /**
     * @test
     */
    public function it_exporters_values()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value '));
        $values->addSingleValue(new SingleValue('-value2'));
        $values->addSingleValue(new SingleValue('value2-'));
        $values->addSingleValue(new SingleValue('10.00'));
        $values->addSingleValue(new SingleValue('10,00'));
        $values->addSingleValue(new SingleValue('hÌ'));
        $values->addSingleValue(new SingleValue('٤٤٤٦٥٤٦٠٠'));
        $values->addSingleValue(new SingleValue('doctor"who""'));
        $values->addExcludedValue(new SingleValue('value3'));

        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideSingleValuePairTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideSingleValuePairTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideSingleValuePairTest();

    /**
     * @test
     */
    public function it_exporters_multiple_fields()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));
        $expectedGroup->addField('name', $values);

        $date = new \DateTime('2014-12-16 00:00:00 UTC');

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue($date, $date->format('m-d-Y')));
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideMultipleValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideSingleValuePairTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideMultipleValuesTest();

    /**
     * @test
     */
    public function it_exporters_range_values()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addRange(new Range(1, 10));
        $values->addRange(new Range(15, 30));
        $values->addRange(new Range(100, 200, false));
        $values->addRange(new Range(310, 400, true, false));
        $values->addExcludedRange(new Range(50, 70));
        $expectedGroup->addField('id', $values);

        $date = new \DateTime('2014-12-16 00:00:00 UTC');
        $date2 = new \DateTime('2014-12-20 00:00:00 UTC');

        $values = new ValuesBag();
        $values->addRange(new Range($date, $date2, true, true, $date->format('m-d-Y'), $date2->format('m-d-Y')));
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideRangeValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideSingleValuePairTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideRangeValuesTest();

    /**
     * @test
     */
    public function it_exporters_comparisons()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addComparison(new Compare(1, '>'));
        $values->addComparison(new Compare(2, '<'));
        $values->addComparison(new Compare(5, '<='));
        $values->addComparison(new Compare(8, '>='));
        $expectedGroup->addField('id', $values);

        $date = new \DateTime('2014-12-16 00:00:00 UTC');

        $values = new ValuesBag();
        $values->addComparison(new Compare($date, '>=', $date->format('m-d-Y')));
        $expectedGroup->addField('date', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideComparisonValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideComparisonValuesTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideComparisonValuesTest();

    /**
     * @test
     */
    public function it_exporters_matchers()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addPatternMatch(new PatternMatch('value', PatternMatch::PATTERN_CONTAINS));
        $values->addPatternMatch(new PatternMatch('value2', PatternMatch::PATTERN_STARTS_WITH, true));
        $values->addPatternMatch(new PatternMatch('value3', PatternMatch::PATTERN_ENDS_WITH));
        $values->addPatternMatch(new PatternMatch('^foo|bar?', PatternMatch::PATTERN_REGEX));
        $values->addPatternMatch(new PatternMatch('value4', PatternMatch::PATTERN_NOT_CONTAINS));
        $values->addPatternMatch(new PatternMatch('value5', PatternMatch::PATTERN_NOT_CONTAINS, true));
        $expectedGroup->addField('name', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideMatcherValuesTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideMatcherValuesTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideMatcherValuesTest();

    /**
     * @test
     */
    public function it_exporters_groups()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));
        $expectedGroup->addField('name', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value3'));
        $values->addSingleValue(new SingleValue('value4'));

        $subGroup = new ValuesGroup();
        $subGroup->addField('name', $values);
        $expectedGroup->addGroup($subGroup);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value8'));
        $values->addSingleValue(new SingleValue('value10'));

        $subGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        $subGroup->addField('name', $values);
        $expectedGroup->addGroup($subGroup);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideGroupTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideGroupTest();

    /**
     * @test
     */
    public function it_exporters_multiple_subgroups()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $subGroup = new ValuesGroup();
        $subGroup->addField('name', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value3'));
        $values->addSingleValue(new SingleValue('value4'));
        $expectedGroup->addGroup($subGroup);

        $subGroup2 = new ValuesGroup();
        $subGroup2->addField('name', $values);
        $expectedGroup->addGroup($subGroup2);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideMultipleSubGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideMultipleSubGroupTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideMultipleSubGroupTest();

    /**
     * @test
     */
    public function it_exporters_nested_subgroups()
    {
        $exporter = $this->getExporter();
        $config = new ProcessorConfig($this->getFieldSet());

        $expectedGroup = new ValuesGroup();
        $nestedGroup = new ValuesGroup();

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));
        $nestedGroup->addField('name', $values);

        $subGroup = new ValuesGroup();
        $subGroup->addGroup($nestedGroup);
        $expectedGroup->addGroup($subGroup);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideNestedGroupTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $processor->process($config, $this->provideNestedGroupTest());
    }

    /**
     * @return mixed
     */
    abstract public function provideNestedGroupTest();

    protected function assertExportEquals($expected, $actual)
    {
        $this->assertEquals($expected, $actual);
    }
}
