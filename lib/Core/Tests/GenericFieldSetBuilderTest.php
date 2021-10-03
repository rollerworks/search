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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\ResolvedFieldType;
use Rollerworks\Component\Search\Field\SearchField;
use Rollerworks\Component\Search\GenericFieldSetBuilder;
use Rollerworks\Component\Search\SearchFactory;
use Rollerworks\Component\Search\Tests\Fixtures\BarType;
use Rollerworks\Component\Search\Tests\Fixtures\FooType;

/**
 * @internal
 */
final class GenericFieldSetBuilderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var GenericFieldSetBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        // Prophecy binds the callback to the ObjectProphecy.
        $test = $this;

        $factory = $this->prophesize(SearchFactory::class);
        $factory->createField(Argument::cetera())->will(
            function ($args) use ($test) {
                \assert(isset($this));

                $type = $test->prophesize(ResolvedFieldType::class);
                $type->getInnerType()->willReturn(new $args[1]());

                return new SearchField($args[0], $type->reveal(), $args[2]);
            }
        );

        $this->builder = new GenericFieldSetBuilder($factory->reveal());
    }

    /** @test */
    public function add_fields(): void
    {
        $this->builder->add('id', FooType::class);
        $this->builder->add('name', BarType::class);

        self::assertTrue($this->builder->has('id'));
        self::assertTrue($this->builder->has('name'));
    }

    /** @test */
    public function always_gives_a_resolved_field(): void
    {
        $this->builder->add('id', FooType::class, ['foo' => 'bar']);

        $this->assertBuilderFieldConfigurationEquals('id', FooType::class, ['foo' => 'bar']);
    }

    /** @test */
    public function set_pre_configured_field(): void
    {
        $field = $this->prophesize(FieldConfig::class);
        $field->getName()->willReturn('id');

        $field = $field->reveal();

        $this->builder->set($field);

        self::assertTrue($this->builder->has('id'));
        self::assertSame($field, $this->builder->get('id'));
    }

    /** @test */
    public function remove_field(): void
    {
        $this->builder->add('id', FooType::class);
        $this->builder->add('name', 'text');

        $this->builder->remove('id');

        self::assertTrue($this->builder->has('name'));
        self::assertFalse($this->builder->has('id'));
    }

    /** @test */
    public function get_build_field_set(): void
    {
        $this->builder->add('id', FooType::class, ['max' => 5000]);
        $this->builder->add('gid', FooType::class);

        $fieldSet = $this->builder->getFieldSet('test');

        self::assertEquals('test', $fieldSet->getSetName());
        self::assertFieldConfigurationEquals($fieldSet->get('id'), 'id', FooType::class, ['max' => 5000]);
        self::assertFieldConfigurationEquals($fieldSet->get('gid'), 'gid', FooType::class);
    }

    private function assertBuilderFieldConfigurationEquals(string $name, string $type, array $options = []): void
    {
        self::assertInstanceOf(FieldConfig::class, $field = $this->builder->get($name));
        self::assertEquals($name, $field->getName());
        self::assertInstanceOf($type, $field->getType()->getInnerType());
        self::assertEquals($options, $field->getOptions());
    }

    private static function assertFieldConfigurationEquals(FieldConfig $field, string $name, string $type, array $options = []): void
    {
        self::assertEquals($name, $field->getName());
        self::assertInstanceOf($type, $field->getType()->getInnerType());
        self::assertEquals($options, $field->getOptions());
    }
}
