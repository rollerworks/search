<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Test;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Formatter\TransformFormatter;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;

abstract class FieldTypeTestCase extends SearchIntegrationTestCase
{
    /**
     * @var TransformFormatter
     */
    protected $transformer;

    protected function setUp()
    {
        parent::setUp();

        $this->transformer = new TransformFormatter();
    }

    public static function assertDateTimeEquals(\DateTime $expected, \DateTime $actual)
    {
        self::assertEquals($expected->format('c'), $actual->format('c'));
    }

    protected function assertTransformedEquals(FieldConfigInterface $column, ValuesBag $expectedValue, $input)
    {
        $this->assertEquals($expectedValue, $this->formatInput($column, $input));
    }

    protected function assertTransformedNotEquals(FieldConfigInterface $column, ValuesBag $expectedValue, $input)
    {
        $this->assertNotEquals($expectedValue, $this->formatInput($column, $input));
    }

    protected function formatInput(FieldConfigInterface $column, $input)
    {
        $fieldSet = new FieldSet('testSet');
        $fieldSet->set($this->getTestedType(), $column);

        $condition = new SearchConditionBuilder();
        $condition->field($column->getName())->addSingleValue(new SingleValue($input));
        $searchCondition = new SearchCondition($fieldSet, $condition->getGroup());

        $this->transformer->format($searchCondition);
        $values = $searchCondition->getValuesGroup()->getField($column->getName())->getSingleValues();

        return $values[0];
    }

    abstract protected function getTestedType();
}
