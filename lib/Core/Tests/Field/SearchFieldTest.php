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
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Field\ResolvedFieldType;
use Rollerworks\Component\Search\Field\SearchField;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValueComparator;

/**
 * @internal
 */
final class SearchFieldTest extends TestCase
{
    /**
     * @var ResolvedFieldType
     */
    private $resolvedType;

    /**
     * @var SearchField
     */
    private $field;

    protected function setUp()
    {
        $this->resolvedType = $this->getMockBuilder(ResolvedFieldType::class)->getMock();
        $this->field = new SearchField('foobar', $this->resolvedType, ['name' => 'value']);
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
        self::assertFalse($this->field->supportValueType(Range::class));
    }

    /**
     * @test
     */
    public function it_allows_configuring_value_support()
    {
        $this->field->setValueTypeSupport(Range::class, true);

        self::assertTrue($this->field->supportValueType(Range::class));
        self::assertFalse($this->field->supportValueType(Compare::class));

        // And now disable it
        $this->field->setValueTypeSupport(Range::class, false);

        self::assertFalse($this->field->supportValueType(Range::class));
        self::assertFalse($this->field->supportValueType(Compare::class));
    }

    /**
     * @test
     */
    public function it_has_no_comparison_class_by_default()
    {
        self::assertEquals(null, $this->field->getValueComparator());
    }

    /**
     * @test
     */
    public function it_allows_setting_a_comparison_class()
    {
        $comparisonObj = $this->getMockBuilder(ValueComparator::class)->getMock();

        $this->field->setValueComparator($comparisonObj);
        self::assertEquals($comparisonObj, $this->field->getValueComparator());
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
    public function its_data_is_unlocked_by_default()
    {
        self::assertFalse($this->field->isConfigLocked());
    }

    /**
     * @test
     */
    public function its_data_is_lockable()
    {
        $this->field->finalizeConfig();
        self::assertTrue($this->field->isConfigLocked());
    }

    /**
     * @test
     */
    public function it_ignores_comparator_requirement_for_non_implemented_or_disabled_types()
    {
        $this->field->setValueTypeSupport(PatternMatch::class, true);
        $this->field->setValueTypeSupport(Range::class, false);
        $this->field->finalizeConfig();

        self::assertTrue($this->field->isConfigLocked());
    }

    /**
     * @test
     */
    public function it_checks_comparator_requirements_and_throws_when_invalid()
    {
        $this->field->setValueTypeSupport(Range::class, true);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Supported value-type "'.Range::class.'" requires a value comparator but none is set for field "foobar"'
        );

        $this->field->finalizeConfig();
    }

    /**
     * @test
     */
    public function its_data_is_not_changeable_when_locked()
    {
        $this->field->finalizeConfig();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('SearchField setter methods cannot be accessed anymore once the data is locked.');

        $this->field->setViewTransformer(null);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createTransformerMock()
    {
        return $this->getMockBuilder(DataTransformer::class)->getMock();
    }
}
