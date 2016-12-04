<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionSerializer;
use Rollerworks\Component\Search\SearchFactoryInterface;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

final class SearchConditionSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchConditionSerializer
     */
    private $serializer;

    /** @var FieldSet */
    private $fieldSet;

    protected function setUp()
    {
        $field = $this->createMock(FieldConfigInterface::class);
        $field->expects(self::any())->method('getName')->willReturn('id');

        $this->fieldSet = new FieldSet(['id' => $field], 'foobar');

        $factory = $this->prophesize(SearchFactoryInterface::class);
        $factory->createFieldSet('foobar')->willReturn($this->fieldSet);

        $this->serializer = new SearchConditionSerializer($factory->reveal());
    }

    public function testSerializeUnSerialize()
    {
        $date = new \DateTime();

        $fieldId = new ValuesBag();
        $fieldId->addSimpleValue(10);
        $fieldId->addSimpleValue($date);

        $valuesGroup0 = new ValuesGroup();
        $valuesGroup0->addField('id', $fieldId);

        $fieldName = new ValuesBag();
        $fieldName->addSimpleValue(10);
        $fieldName->addSimpleValue($date);

        $valuesGroup1 = new ValuesGroup();

        $valuesGroup1->addField('name', $fieldName);
        $valuesGroup0->addGroup($valuesGroup1);

        $searchCondition = new SearchCondition($this->fieldSet, $valuesGroup0);

        $serialized = serialize($this->serializer->serialize($searchCondition));
        $unSerialized = $this->serializer->unserialize(unserialize($serialized));

        $this->assertEquals($searchCondition, $unSerialized);
    }

    public function testUnSerializeMissingFields()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Serialized search condition must be exactly two values [FieldSet-name, serialized ValuesGroup].'
        );

        $this->serializer->unserialize(['foobar']);
    }

    public function testUnSerializeWrongField()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Serialized search condition must be exactly two values [FieldSet-name, serialized ValuesGroup].'
        );

        $this->serializer->unserialize(['foobar', 'foo' => 'bar']);
    }

    public function testUnSerializeInvalidData()
    {
        try {
            // Disable errors to get the exception
            error_reporting(0);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Unable to unserialize invalid value.');

            $this->serializer->unserialize(['foobar', '{i-am-invalid}']);
        } finally {
            error_reporting(1);
        }
    }
}
