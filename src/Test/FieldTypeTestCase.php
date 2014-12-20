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
        self::assertEquals(
            $expected->format('c'),
            $actual->format('c')
        );
    }

    protected function assertTransformedEquals(FieldConfigInterface $field, $expectedValue, $input)
    {
        $values = $this->formatInput($field, $input);

        if ($values->hasErrors()) {
            $this->fail(implode(', ', $values->getErrors()));
        }

        $values = $values->getSingleValues();

        if ($expectedValue instanceof \DateTime) {
            $this->assertDateTimeEquals($expectedValue, $values[0]->getValue());
        } else {
            $this->assertEquals($expectedValue, $values[0]->getValue());
        }
    }

    protected function assertTransformedFails(FieldConfigInterface $field, $input)
    {
        $this->assertTrue($this->formatInput($field, $input)->hasErrors());
    }

    protected function assertTransformedNotEquals(FieldConfigInterface $field, $expectedValue, $input)
    {
        $this->assertNotEquals($expectedValue, $this->formatInput($field, $input));
    }

    protected function formatInput(FieldConfigInterface $field, $input)
    {
        $fieldSet = new FieldSet('testSet');
        $fieldSet->set($field->getName(), $field);

        $condition = new SearchConditionBuilder();
        $condition->field($field->getName())->addSingleValue(new SingleValue($input));

        $searchCondition = new SearchCondition($fieldSet, $condition->getGroup());

        $this->transformer->format($searchCondition);

        return $searchCondition->getValuesGroup()->getField($field->getName());
    }

    /**
     * @return string
     */
    abstract protected function getTestedType();
}
