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

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FieldSetRegistry;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionSerializer;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;

final class SearchConditionSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldSetRegistry
     */
    private $fieldSetRegistry;

    /**
     * @var SearchConditionSerializer
     */
    private $serializer;

    protected function setUp()
    {
        $fieldSet = new FieldSet('foobar');
        $fieldSet->lockConfig();

        $this->fieldSetRegistry = new FieldSetRegistry();
        $this->fieldSetRegistry->add($fieldSet);

        $this->serializer = new SearchConditionSerializer($this->fieldSetRegistry);
    }

    public function testSerializeUnSerialize()
    {
        $date = new \DateTime();

        $fieldId = new ValuesBag();
        $fieldId->addSingleValue(new SingleValue(10));
        $fieldId->addSingleValue(new SingleValue($date, $date->format('m/d/Y')));

        $valuesGroup0 = new ValuesGroup();
        $valuesGroup0->addField('id', $fieldId);

        $fieldName = new ValuesBag();
        $fieldName->addError(new ValuesError('singleValue[0]', 'system error'));
        $fieldName->addSingleValue(new SingleValue(10));
        $fieldName->addSingleValue(new SingleValue($date, $date->format('m/d/Y')));

        $valuesGroup1 = new ValuesGroup();

        $valuesGroup1->addField('name', $fieldName);
        $valuesGroup0->addGroup($valuesGroup1);

        $searchCondition = new SearchCondition($this->fieldSetRegistry->get('foobar'), $valuesGroup0);

        $serialized = serialize($this->serializer->serialize($searchCondition));
        $unSerialized = $this->serializer->unserialize(unserialize($serialized));

        $this->assertEquals($searchCondition, $unSerialized);
    }

    public function testUnSerializeMissingFields()
    {
        $this->setExpectedException(
             'Rollerworks\Component\Search\Exception\InvalidArgumentException',
             'Serialized search condition must be exactly two values [FieldSet-name, serialized ValuesGroup].'
        );

        $this->serializer->unserialize(['foobar']);
    }

    public function testUnSerializeWrongField()
    {
        $this->setExpectedException(
             'Rollerworks\Component\Search\Exception\InvalidArgumentException',
             'Serialized search condition must be exactly two values [FieldSet-name, serialized ValuesGroup].'
        );

        $this->serializer->unserialize(['foobar', 'foo' => 'bar']);
    }

    public function testUnSerializeInvalidData()
    {
        // Disable errors to get the exception
        error_reporting(0);

        $this->setExpectedException(
             'Rollerworks\Component\Search\Exception\InvalidArgumentException',
             'Unable to unserialize invalid value.'
        );

        $this->serializer->unserialize(['foobar', '{i-am-invalid}']);
    }
}
