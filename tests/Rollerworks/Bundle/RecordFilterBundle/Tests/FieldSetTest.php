<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests;

use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class FieldSetTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $this->assertInstanceOf('Rollerworks\Bundle\RecordFilterBundle\FieldSet', FieldSet::create());
    }

    public function testCreateWithName()
    {
        $fieldSet = FieldSet::create('users');

        $this->assertInstanceOf('Rollerworks\Bundle\RecordFilterBundle\FieldSet', $fieldSet);
        $this->assertEquals('users', $fieldSet->getSetName());
    }

    public function testCreateWithInvalidName()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid fieldSet name "foo-bar" (must be a legal class-name without a namespace).');

        new FieldSet('foo-bar');
    }

    public function testCreateWithInvalidName2()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Invalid fieldSet name "\foo\bar" (must be a legal class-name without a namespace).');

        new FieldSet('\foo\bar');
    }

    public function testSet()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', new FilterField('id'));
    }

    public function testSetWithInvalidName()
    {
        $fieldSet = new FieldSet();

        $this->setExpectedException('\InvalidArgumentException', 'FieldName "i\'d" can not contain quotes.');
        $fieldSet->set('i\'d', new FilterField('id'));
    }

    public function testSetEmptyName()
    {
        $fieldSet = new FieldSet();

        $this->setExpectedException('\InvalidArgumentException', 'FieldName can not be empty.');
        $fieldSet->set('', new FilterField('id'));
    }

    public function testGetWithoutField()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', new FilterField('id'));

        $this->setExpectedException('\RuntimeException', 'Unable to find filter field: foo');
        $fieldSet->get('foo');
    }

    public function testReplace()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', new FilterField('id'));

        $config = new FilterField('number');

        $fieldSet->replace('id', $config);
        $this->assertSame($config, $fieldSet->get('id'));
    }

    public function testReplaceNoField()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', new FilterField('id'));

        $this->setExpectedException('\RuntimeException', 'Unable to replace none existent field: number');
        $fieldSet->replace('number', new FilterField('number'));
    }

    public function testRemove()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', new FilterField('id'));

        $this->assertTrue($fieldSet->has('id'));
        $fieldSet->remove('id');
        $this->assertFalse($fieldSet->has('id'));
    }

    public function testCount()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', new FilterField('id'));
        $fieldSet->set('email', new FilterField('email'));

        $this->assertCount(2, $fieldSet);
    }

    public function testIterator()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', new FilterField('id'));
        $fieldSet->set('email', new FilterField('email'));

        $this->assertInstanceOf('\ArrayIterator', $fieldSet->getIterator());
        $this->assertEquals($fieldSet->all(), iterator_to_array($fieldSet->getIterator()));
    }
}
