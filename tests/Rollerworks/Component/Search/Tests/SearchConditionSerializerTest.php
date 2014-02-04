<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Tests;

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionSerializer;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;

class SearchConditionSerializerTest extends \PHPUnit_Framework_TestCase
{
    function testSerializeUnSerialize()
    {
        $fieldSet = new FieldSet('foobar');

        $fieldId = new ValuesBag();
        $fieldId->addSingleValue(new SingleValue(10));
        $fieldId->addSingleValue(new SingleValue(new \DateTime()));

        $valuesGroup0 = new ValuesGroup();
        $valuesGroup0->addField('id', $fieldId);

        $fieldName = new ValuesBag();
        $fieldName->addError(new ValuesError('singleValue[0]', 'system error'));
        $fieldName->addSingleValue(new SingleValue(10));
        $fieldName->addSingleValue(new SingleValue(new \DateTime()));

        $valuesGroup1 = new ValuesGroup();
        $valuesGroup1->setHasErrors();

        $valuesGroup1->addField('name', $fieldName);
        $valuesGroup0->addGroup($valuesGroup1);

        $searchCondition = new SearchCondition($fieldSet, $valuesGroup0);

        $serialized = serialize(SearchConditionSerializer::serialize($searchCondition));
        $unSerialized = SearchConditionSerializer::unserialize($fieldSet, unserialize($serialized));

        $this->assertEquals($searchCondition, $unSerialized);
    }

    function testUnSerializeWrongFieldSet()
    {
        $fieldSet = new FieldSet('foobar');

        $valuesGroup0 = new ValuesGroup();
        $valuesGroup0->addField('id', new ValuesBag());

        $searchCondition = new SearchCondition($fieldSet, $valuesGroup0);

        $serialized = SearchConditionSerializer::serialize($searchCondition);

        $fieldSet = new FieldSet('bar_foo');

        $this->setExpectedException(
             'Rollerworks\Component\Search\Exception\InvalidArgumentException',
             'Wrong FieldSet, expected FieldSet "foobar", but got "bar_foo".'
        );

        SearchConditionSerializer::unserialize($fieldSet, $serialized);
    }

    function testUnSerializeMissingFields()
    {
        $fieldSet = new FieldSet('bar_foo');

        $this->setExpectedException(
             'Rollerworks\Component\Search\Exception\InvalidArgumentException',
             'Serialized SearchCondition must be exactly two values [fieldSet-name, ValuesGroup].'
        );

        SearchConditionSerializer::unserialize($fieldSet, array('bar_foo'));
    }

    function testUnSerializeWrongField()
    {
        $fieldSet = new FieldSet('bar_foo');

        $this->setExpectedException(
             'Rollerworks\Component\Search\Exception\InvalidArgumentException',
             'Serialized SearchCondition must be exactly two values [fieldSet-name, ValuesGroup].'
        );

        SearchConditionSerializer::unserialize($fieldSet, array('bar_foo', 'foo' => 'bar'));
    }

    function testUnSerializeInvalidData()
    {
        $fieldSet = new FieldSet('bar_foo');

        $this->setExpectedException(
             'Rollerworks\Component\Search\Exception\InvalidArgumentException',
             'Unable to unserialize invalid value.'
        );

        SearchConditionSerializer::unserialize($fieldSet, array('bar_foo', '{i-am-invalid}'));
    }
}
