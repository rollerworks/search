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

namespace Rollerworks\Component\Search\Tests;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionSerializer;
use Rollerworks\Component\Search\SearchFactory;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @internal
 */
final class SearchConditionSerializerTest extends TestCase
{
    /**
     * @var SearchConditionSerializer
     */
    private $serializer;

    /** @var GenericFieldSet */
    private $fieldSet;

    protected function setUp(): void
    {
        $field = $this->createMock(FieldConfig::class);
        $field->expects(self::any())->method('getName')->willReturn('id');

        $this->fieldSet = new GenericFieldSet(['id' => $field], 'foobar');

        $factory = $this->prophesize(SearchFactory::class);
        $factory->createFieldSet('foobar')->willReturn($this->fieldSet);

        $this->serializer = new SearchConditionSerializer($factory->reveal());
    }

    public function testSerializeUnSerialize()
    {
        $date = new \DateTimeImmutable();

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

        self::assertEquals($searchCondition, $unSerialized);
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
