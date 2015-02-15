<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Exporter;

use Rollerworks\Component\Search\Exporter\FilterQueryExporter;
use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\Input\FilterQueryInput;
use Rollerworks\Component\Search\InputProcessorInterface;

final class FilterQueryExporterTest extends SearchConditionExporterTestCase
{
    public function provideSingleValuePairTest()
    {
        return 'name: "value ", "-value2", "value2-", "10.00", "10,00", hÌ, ٤٤٤٦٥٤٦٠٠, "doctor""who""""", !value3;';
    }

    public function provideFieldAliasTest()
    {
        return 'firstname: value, value2;';
    }

    public function provideMultipleValuesTest()
    {
        return 'name: value, value2; date: "12-16-2014";';
    }

    public function provideRangeValuesTest()
    {
        return 'id: 1-10, 15-30, ]100-200, 310-400[, !50-70; date: "12-16-2014"-"12-20-2014";';
    }

    public function provideComparisonValuesTest()
    {
        return 'id: >1, <2, <=5, >=8; date: >="12-16-2014";';
    }

    public function provideMatcherValuesTest()
    {
        return 'name: ~*value, ~i>value2, ~<value3, ~?"^foo|bar?", ~!*value4, ~i!*value5;';
    }

    public function provideGroupTest()
    {
        return 'name: value, value2; (name: value3, value4); *(name: value8, value10);';
    }

    public function provideMultipleSubGroupTest()
    {
        return '(name: value, value2); (name: value3, value4);';
    }

    public function provideNestedGroupTest()
    {
        return '((name: value, value2));';
    }

    public function provideEmptyValuesTest()
    {
        return '';
    }

    public function provideEmptyGroupTest()
    {
        return '();';
    }

    /**
     * @return ExporterInterface
     */
    protected function getExporter()
    {
        return new FilterQueryExporter($this->fieldLabelResolver->reveal());
    }

    /**
     * @return InputProcessorInterface
     */
    protected function getInputProcessor()
    {
        return new FilterQueryInput($this->fieldAliasResolver->reveal());
    }
}
