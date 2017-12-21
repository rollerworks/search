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
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\Test\SearchConditionExporterTestCase;

/**
 * @internal
 */
final class NormStringQueryExporterTest extends SearchConditionExporterTestCase
{
    public function provideSingleValuePairTest()
    {
        return 'name: "value ", -value2, value2-, 10.00, "10,00", hÌ, ٤٤٤٦٥٤٦٠٠, "doctor""who""""", !value3;';
    }

    public function provideMultipleValuesTest()
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

    protected function getExporter(): ConditionExporter
    {
        return new NormStringQueryExporter();
    }

    protected function getInputProcessor(): InputProcessor
    {
        return new NormStringQueryInput();
    }
}
