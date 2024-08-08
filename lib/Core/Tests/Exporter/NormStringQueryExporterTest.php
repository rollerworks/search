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

namespace Rollerworks\Component\Search\Tests\Exporter;

use Rollerworks\Component\Search\ConditionExporter;
use Rollerworks\Component\Search\Exporter\NormStringQueryExporter;
use Rollerworks\Component\Search\Input\NormStringQueryInput;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Test\SearchConditionExporterTestCase;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @internal
 */
final class NormStringQueryExporterTest extends SearchConditionExporterTestCase
{
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

        $values = new ValuesBag();
        $values->addSimpleValue('EUR 12.00');
        $values->addSimpleValue('12');
        $expectedGroup->addField('price', $values);

        $condition = new SearchCondition($config->getFieldSet(), $expectedGroup);
        $this->assertExportEquals($this->provideSingleValuePairTest(), $exporter->exportCondition($condition));

        $processor = $this->getInputProcessor();
        $this->assertConditionEquals($this->provideSingleValuePairTest(), $condition, $processor, $config);
    }

    public function provideSingleValuePairTest()
    {
        return 'name: "value ", -value2, value2-, 10.00, "10,00", hÌ, ٤٤٤٦٥٤٦٠٠, "doctor""who""""", !value3; price: EUR 12.00, 12;';
    }

    public function provideMultipleValuesTest()
    {
        return 'name: value, value2; date: 2014-12-16;';
    }

    public function providePrivateFieldsTest()
    {
        return 'name: value, value2; date: 2014-12-16;';
    }

    public function provideRangeValuesTest()
    {
        return 'id: 1 ~ 10, 15 ~ 30, ]100 ~ 200, 310 ~ 400[, !50 ~ 70; date: 2014-12-16 ~ 2014-12-20;';
    }

    public function provideComparisonValuesTest()
    {
        return 'id: > 1, < 2, <= 5, >= 8; date: >= 2014-12-16;';
    }

    public function provideMatcherValuesTest()
    {
        return 'name: ~* value, ~i> value2, ~< value3, ~!* value4, ~i!* value5, ~= value9, ~!= value10, ~i= value11, ~i!= value12;';
    }

    public function provideGroupTest()
    {
        return 'name: value, value2; ( name: value3, value4 ); *( name: value8, value10 );';
    }

    public function provideMultipleSubGroupTest()
    {
        return '( name: value, value2 ); ( name: value3, value4 );';
    }

    public function provideNestedGroupTest()
    {
        return '( ( name: value, value2 ) );';
    }

    public function provideEmptyValuesTest()
    {
        return '';
    }

    public function provideEmptyGroupTest()
    {
        return '(  );';
    }

    public function provideOrderTest()
    {
        return '@id: desc; @status: asc;';
    }

    protected function getExporter(): ConditionExporter
    {
        return new NormStringQueryExporter();
    }

    protected function getInputProcessor(): InputProcessor
    {
        return new NormStringQueryInput();
    }
}
