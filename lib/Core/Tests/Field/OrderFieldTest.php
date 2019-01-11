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

namespace Rollerworks\Component\Search\Tests\Field;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Field\OrderField;
use Rollerworks\Component\Search\Field\ResolvedFieldType;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValueComparator;

/**
 * @internal
 */
final class OrderFieldTest extends TestCase
{
    /**
     * @var ResolvedFieldType
     */
    private $resolvedType;

    /**
     * @var OrderField
     */
    private $field;

    protected function setUp()
    {
        $this->resolvedType = $this->getMockBuilder(ResolvedFieldType::class)->getMock();
        $this->field = new OrderField('@foobar', $this->resolvedType, ['name' => 'value']);
    }

    /**
     * @test
     */
    public function it_has_a_name()
    {
        self::assertEquals('@foobar', $this->field->getName());
    }

    /**
     * @test
     */
    public function it_has_a_type()
    {
        self::assertEquals($this->resolvedType, $this->field->getType());
    }

    /**
     * @test
     */
    public function it_has_options()
    {
        self::assertEquals(['name' => 'value'], $this->field->getOptions());
    }

    /**
     * @test
     */
    public function it_should_return_if_an_option_exists()
    {
        self::assertTrue($this->field->hasOption('name'));
        self::assertFalse($this->field->hasOption('foo'));
    }

    /**
     * @test
     */
    public function it_should_return_an_options_value()
    {
        self::assertEquals('value', $this->field->getOption('name'));
    }

    /**
     * @test
     */
    public function it_should_return_null_by_default_if_the_option_does_exist()
    {
        self::assertNull($this->field->getOption('foo'));
    }

    /**
     * @test
     */
    public function it_should_return_default_value_if_the_option_does_exist()
    {
        self::assertEquals('value1', $this->field->getOption('foo', 'value1'));
    }

    /**
     * @test
     */
    public function it_supports_no_special_value_types_by_default()
    {
        self::assertFalse($this->field->supportValueType(Range::class));
    }

    /**
     * @test
     */
    public function it_does_not_allow_configuring_value_support()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('does not support supporting custom value types');

        $this->field->setValueTypeSupport(Range::class, true);

        self::assertFalse($this->field->supportValueType(Range::class));
    }

    /**
     * @test
     */
    public function it_has_no_comparison_class_by_default()
    {
        self::assertNull($this->field->getValueComparator());
    }

    /**
     * @test
     */
    public function it_does_not_allow_setting_a_comparison_class()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('does not support supporting custom value comparator');

        $comparisonObj = $this->getMockBuilder(ValueComparator::class)->getMock();

        $this->field->setValueComparator($comparisonObj);
        self::assertNull($this->field->getValueComparator());
    }

    /**
     * @test
     */
    public function it_has_no_ViewTransformer_by_default()
    {
        self::assertNull($this->field->getViewTransformer());
    }

    /**
     * @test
     */
    public function it_allows_setting_a_ViewTransformer()
    {
        $viewTransformer = $this->getMockBuilder(DataTransformer::class)->getMock();
        $this->field->setViewTransformer($viewTransformer);

        self::assertEquals($viewTransformer, $this->field->getViewTransformer());
    }

    /**
     * @test
     */
    public function it_has_no_NormTransformer_by_default()
    {
        self::assertNull($this->field->getNormTransformer());
    }

    /**
     * @test
     */
    public function it_allows_setting_a_NormTransformer()
    {
        $normTransformer = $this->getMockBuilder(DataTransformer::class)->getMock();
        $this->field->setNormTransformer($normTransformer);

        self::assertEquals($normTransformer, $this->field->getNormTransformer());
    }
}
