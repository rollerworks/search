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

final class FieldSetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function its_name_is_optional()
    {
        $fieldSet = new FieldSet();

        $this->assertNull($fieldSet->getSetName());
    }

    /**
     * @test
     */
    public function it_accepts_a_name()
    {
        $fieldSet = new FieldSet('users');

        $this->assertEquals('users', $fieldSet->getSetName());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_name_is_invalid()
    {
        $this->setExpectedException('InvalidArgumentException');

        new FieldSet(
            '(users)',
            'The name "(users)" contains illegal characters. '.
            'Names should start with a letter, digit or underscore and only contain letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").'
        );
    }

    /**
     * @test
     */
    public function it_gives_an_empty_array_when_no_fields_are_registered()
    {
        $fieldSet = new FieldSet();

        $this->assertInternalType('array', $fieldSet->all());
        $this->assertCount(0, $fieldSet);
    }

    /**
     * @test
     */
    public function it_allows_adding_fields()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', $idField = $this->createFieldMock());
        $fieldSet->set('name', $nameField = $this->createFieldMock());

        $this->assertSame(['id' => $idField, 'name' => $nameField], $fieldSet->all());
        $this->assertCount(2, $fieldSet);
    }

    /**
     * @test
     */
    public function it_allows_replacing_existing_fields()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', $idField = $this->createFieldMock());
        $fieldSet->set('name', $nameField = $this->createFieldMock());

        $fieldSet->replace('id', $idNewField = $this->createFieldMock());

        $this->assertSame(['id' => $idNewField, 'name' => $nameField], $fieldSet->all());
        $this->assertCount(2, $fieldSet);
    }

    /**
     * @test
     */
    public function it_gets_a_field()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', $idField = $this->createFieldMock());
        $fieldSet->set('name', $nameField = $this->createFieldMock());

        $this->assertSame($idField, $fieldSet->get('id'));
        $this->assertSame($nameField, $fieldSet->get('name'));
    }

    /**
     * @test
     */
    public function it_returns_whether_field_is_registered()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', $idField = $this->createFieldMock());
        $fieldSet->set('name', $nameField = $this->createFieldMock());

        $this->assertSame($idField, $fieldSet->get('id'));
        $this->assertSame($nameField, $fieldSet->get('name'));

        $this->assertTrue($fieldSet->has('id'));
        $this->assertTrue($fieldSet->has('name'));
        $this->assertFalse($fieldSet->has('foo'));
    }

    /**
     * @test
     */
    public function it_allows_removing_registered_fields()
    {
        $fieldSet = new FieldSet();
        $fieldSet->set('id', $idField = $this->createFieldMock());
        $fieldSet->set('name', $nameField = $this->createFieldMock());

        $fieldSet->remove('id');

        $this->assertSame(['name' => $nameField], $fieldSet->all());
        $this->assertCount(1, $fieldSet);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createFieldMock()
    {
        return $this->getMockBuilder('Rollerworks\Component\Search\FieldConfigInterface')->getMock();
    }
}
