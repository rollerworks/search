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

use Prophecy\Argument;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\Metadata\SearchField as MappingSearchField;
use Rollerworks\Component\Search\SearchField;

final class FieldSetBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldSetBuilder
     */
    private $builder;

    protected function setUp()
    {
        // Prophecy binds the callback to the ObjectProphecy.
        $test = $this;

        $factory = $this->prophesize('Rollerworks\Component\Search\SearchFactory');
        $factory->createField(Argument::cetera())->will(
            function ($args) use ($test) {
                $type = $test->prophesize('Rollerworks\Component\Search\ResolvedFieldTypeInterface');
                $type->getName()->willReturn($args[1]);

                return new SearchField($args[0], $type->reveal(), $args[2]);
            }
        );

        $metadataReader = $this->prophesize('Rollerworks\Component\Search\Metadata\MetadataReaderInterface');
        $metadataReader->getSearchFields('User')->willReturn(
            [
                'uid' => new MappingSearchField('uid', 'User', 'id', false, 'integer', ['min' => 1]),
                'username' => new MappingSearchField('username', 'User', 'name', false, 'text'),
            ]
        );

        $metadataReader->getSearchFields('Group')->willReturn(
            [
                'gid' => new MappingSearchField('gid', 'Group', 'id', false, 'integer'),
                'group-name' => new MappingSearchField('group-name', 'Group', 'name', false, 'text'),
            ]
        );

        $this->builder = new FieldSetBuilder('test', $factory->reveal(), $metadataReader->reveal());
    }

    public function testGetName()
    {
        $this->assertEquals('test', $this->builder->getName());
    }

    function testAddFields()
    {
        $this->builder->add('id', 'integer');
        $this->builder->add('name', 'text');

        $this->assertTrue($this->builder->has('id'));
        $this->assertTrue($this->builder->has('name'));
    }

    function testAlwaysGivesAResolvedField()
    {
        $this->builder->add('id', 'integer', ['foo' => 'bar']);

        $this->assertBuilderFieldConfigurationEquals('id', 'integer', ['foo' => 'bar']);
    }

    function testAddedPreConfiguredField()
    {
        $type = $this->prophesize('Rollerworks\Component\Search\ResolvedFieldTypeInterface');
        $type->getName()->willReturn('text');

        $this->builder->add($field = new SearchField('id', $type->reveal()));

        $this->assertTrue($this->builder->has('id'));
        $this->assertSame($field, $this->builder->get('id'));
    }

    function testRemoveField()
    {
        $this->builder->add('id', 'integer');
        $this->builder->add('name', 'text');

        $this->builder->remove('id');

        $this->assertTrue($this->builder->has('name'));
        $this->assertFalse($this->builder->has('id'));
    }

    function testImportFieldsFromMetadata()
    {
        $this->builder->importFromClass('User');
        $this->builder->importFromClass('Group');

        $this->assertTrue($this->builder->has('uid'));
        $this->assertTrue($this->builder->has('username'));
        $this->assertTrue($this->builder->has('gid'));
        $this->assertTrue($this->builder->has('group-name'));

        $this->assertBuilderFieldConfigurationEquals(
            'uid',
            'integer',
            [
                'min' => 1,
                'model_class' => 'User',
                'model_property' => 'id',
            ]
        );

        $this->assertBuilderFieldConfigurationEquals(
            'username',
            'text',
            [
                'model_class' => 'User',
                'model_property' => 'name',
            ]
        );

        $this->assertBuilderFieldConfigurationEquals(
            'gid',
            'integer',
            [
                'model_class' => 'Group',
                'model_property' => 'id',
            ]
        );

        $this->assertBuilderFieldConfigurationEquals(
            'group-name',
            'text',
            [
                'model_class' => 'Group',
                'model_property' => 'name',
            ]
        );
    }

    function testGetBuildFieldSet()
    {
        $this->builder->add('id', 'integer', ['max' => 5000]);
        $this->builder->add(
            'gid',
            'integer',
            [
                'model_class' => 'Rollerworks\Component\Search\Fixtures\Entity\Group',
                'model_property' => 'name',
            ]
        );

        $fieldSet = $this->builder->getFieldSet();

        $this->assertEquals('test', $fieldSet->getSetName());
        $this->assertFieldConfigurationEquals($fieldSet->get('id'), 'id', 'integer', ['max' => 5000]);
        $this->assertFieldConfigurationEquals(
            $fieldSet->get('gid'),
            'gid',
            'integer',
            [
                'model_class' => 'Rollerworks\Component\Search\Fixtures\Entity\Group',
                'model_property' => 'name',
            ]
        );
    }

    /**
     * @dataProvider getBuilderMethods
     */
    function testCannotChangeCompletedBuilder($method, array $parameters)
    {
        $this->builder->add('id', 'integer', ['max' => 5000]);
        $this->builder->getFieldSet();

        $this->setExpectedException(
            'Rollerworks\Component\Search\Exception\BadMethodCallException',
            'FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'
        );

        call_user_func_array([$this->builder, $method], $parameters);
    }

    public function getBuilderMethods()
    {
        return [
            ['add', ['id', 'integer']],
            ['has', ['id']],
            ['get', ['id']],
            ['remove', ['id']],
        ];
    }

    private function assertBuilderFieldConfigurationEquals($name, $type, $options = [])
    {
        $this->assertInstanceOf('Rollerworks\Component\Search\FieldConfigInterface', $field = $this->builder->get($name));
        $this->assertEquals($name, $field->getName());
        $this->assertEquals($type, $field->getType()->getName());
        $this->assertEquals($options, $field->getOptions());
    }

    private function assertFieldConfigurationEquals(FieldConfigInterface $field, $name, $type, $options = [])
    {
        $this->assertEquals($name, $field->getName());
        $this->assertEquals($type, $field->getType()->getName());
        $this->assertEquals($options, $field->getOptions());
    }
}
