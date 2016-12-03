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
use Rollerworks\Component\Search\SearchField;
use Rollerworks\Component\Search\Tests\Fixtures\BarType;
use Rollerworks\Component\Search\Tests\Fixtures\FooType;

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
                $type->getInnerType()->willReturn(new $args[1]());

                return new SearchField($args[0], $type->reveal(), $args[2]);
            }
        );

        $this->builder = new FieldSetBuilder($factory->reveal());
    }

    public function testAddFields()
    {
        $this->builder->add('id', FooType::class);
        $this->builder->add('name', BarType::class);

        $this->assertTrue($this->builder->has('id'));
        $this->assertTrue($this->builder->has('name'));
    }

    public function testAlwaysGivesAResolvedField()
    {
        $this->builder->add('id', FooType::class, ['foo' => 'bar']);

        $this->assertBuilderFieldConfigurationEquals('id', FooType::class, ['foo' => 'bar']);
    }

    public function testSetPreConfiguredField()
    {
        $field = $this->prophesize(FieldConfigInterface::class);
        $field->getName()->willReturn('id');

        $field = $field->reveal();

        $this->builder->set($field);

        $this->assertTrue($this->builder->has('id'));
        $this->assertSame($field, $this->builder->get('id'));
    }

    public function testRemoveField()
    {
        $this->builder->add('id', FooType::class);
        $this->builder->add('name', 'text');

        $this->builder->remove('id');

        $this->assertTrue($this->builder->has('name'));
        $this->assertFalse($this->builder->has('id'));
    }

    public function testGetBuildFieldSet()
    {
        $this->builder->add('id', FooType::class, ['max' => 5000]);
        $this->builder->add('gid', FooType::class);

        $fieldSet = $this->builder->getFieldSet('test');

        $this->assertEquals('test', $fieldSet->getSetName());
        $this->assertFieldConfigurationEquals($fieldSet->get('id'), 'id', FooType::class, ['max' => 5000]);
        $this->assertFieldConfigurationEquals($fieldSet->get('gid'), 'gid', FooType::class);
    }

    private function assertBuilderFieldConfigurationEquals(string $name, string $type, array $options = [])
    {
        $this->assertInstanceOf('Rollerworks\Component\Search\FieldConfigInterface', $field = $this->builder->get($name));
        $this->assertEquals($name, $field->getName());
        $this->assertInstanceOf($type, $field->getType()->getInnerType());
        $this->assertEquals($options, $field->getOptions());
    }

    private function assertFieldConfigurationEquals(FieldConfigInterface $field, string $name, string $type, array $options = [])
    {
        $this->assertEquals($name, $field->getName());
        $this->assertInstanceOf($type, $field->getType()->getInnerType());
        $this->assertEquals($options, $field->getOptions());
    }
}
