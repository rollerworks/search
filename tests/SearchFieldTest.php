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
use Rollerworks\Component\Search\SearchField;
use Rollerworks\Component\Search\Value\ValuesBag;

final class SearchFieldTest extends TestCase
{
    /**
     * @var \Rollerworks\Component\Search\ResolvedFieldTypeInterface
     */
    private $resolvedType;

    /**
     * @var SearchField
     */
    private $field;

    protected function setUp()
    {
        $this->resolvedType = $this->getMockBuilder('Rollerworks\Component\Search\ResolvedFieldTypeInterface')->getMock();
        $this->field = new SearchField('foobar', $this->resolvedType, ['name' => 'value']);

        self::assertInstanceOf('Rollerworks\Component\Search\FieldConfigInterface', $this->field);
    }

    /**
     * @test
     */
    public function it_has_a_name()
    {
        self::assertEquals('foobar', $this->field->getName());
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
        self::assertEquals(true, $this->field->hasOption('name'));
        self::assertEquals(false, $this->field->hasOption('foo'));
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
        self::assertEquals(null, $this->field->getOption('foo'));
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
        self::assertEquals(false, $this->field->supportValueType(ValuesBag::VALUE_TYPE_RANGE));
        self::assertEquals(false, $this->field->supportValueType(ValuesBag::VALUE_TYPE_COMPARISON));
        self::assertEquals(false, $this->field->supportValueType(ValuesBag::VALUE_TYPE_PATTERN_MATCH));
    }

    /**
     * @test
     */
    public function it_allows_configuring_value_support()
    {
        $this->field->setValueTypeSupport(ValuesBag::VALUE_TYPE_RANGE, true);
        self::assertEquals(true, $this->field->supportValueType(ValuesBag::VALUE_TYPE_RANGE));
        self::assertEquals(false, $this->field->supportValueType(ValuesBag::VALUE_TYPE_COMPARISON));

        // And now disable it
        $this->field->setValueTypeSupport(ValuesBag::VALUE_TYPE_RANGE, false);
        self::assertEquals(false, $this->field->supportValueType(ValuesBag::VALUE_TYPE_RANGE));
    }

    /**
     * @test
     */
    public function it_has_no_comparison_class_by_default()
    {
        self::assertEquals(null, $this->field->getValueComparison());
    }

    /**
     * @test
     */
    public function it_allows_setting_a_comparison_class()
    {
        $comparisonObj = $this->getMockBuilder('Rollerworks\Component\Search\ValueComparisonInterface')->getMock();

        $this->field->setValueComparison($comparisonObj);
        self::assertEquals($comparisonObj, $this->field->getValueComparison());
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
        $viewTransformer = $this->createTransformerMock();
        $this->field->setViewTransformer($viewTransformer);

        self::assertEquals($viewTransformer, $this->field->getViewTransformer());
    }

    /**
     * @test
     */
    public function its_data_is_locked_by_default()
    {
        self::assertEquals(false, $this->field->getDataLocked());
    }

    /**
     * @test
     */
    public function its_data_is_lockable()
    {
        $this->field->setDataLocked();
        self::assertEquals(true, $this->field->getDataLocked());
    }

    /**
     * @test
     */
    public function its_data_is_not_changeable_when_locked()
    {
        $this->field->setDataLocked();

        $this->setExpectedException(
            'Rollerworks\Component\Search\Exception\BadMethodCallException',
            'SearchField setter methods cannot be accessed anymore once the data is locked.'
        );

        $this->field->setDataLocked();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createTransformerMock()
    {
        return $this->getMockBuilder('Rollerworks\Component\Search\DataTransformerInterface')->getMock();
    }
}
