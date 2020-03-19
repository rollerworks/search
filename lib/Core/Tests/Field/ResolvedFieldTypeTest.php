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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\FieldType;
use Rollerworks\Component\Search\Field\FieldTypeExtension;
use Rollerworks\Component\Search\Field\GenericResolvedFieldType;
use Rollerworks\Component\Search\Field\SearchFieldView;
use Rollerworks\Component\Search\FieldSetView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
final class ResolvedFieldTypeTest extends TestCase
{
    /**
     * @var MockObject|FieldType
     */
    private $parentType;

    /**
     * @var MockObject|FieldType
     */
    private $type;

    /**
     * @var MockObject|FieldTypeExtension
     */
    private $extension1;

    /**
     * @var MockObject|FieldTypeExtension
     */
    private $extension2;

    /**
     * @var GenericResolvedFieldType
     */
    private $parentResolvedType;

    /**
     * @var GenericResolvedFieldType
     */
    private $resolvedType;

    protected function setUp()
    {
        $this->parentType = $this->getMockFieldType();
        $this->type = $this->getMockFieldType();
        $this->extension1 = $this->getMockFieldTypeExtension();
        $this->extension2 = $this->getMockFieldTypeExtension();
        $this->parentResolvedType = new GenericResolvedFieldType($this->parentType);
        $this->resolvedType = new GenericResolvedFieldType(
            $this->type,
            [$this->extension1, $this->extension2],
            $this->parentResolvedType
        );
    }

    /**
     * @test
     */
    public function its_resolved_options_in_correct_order()
    {
        $i = 0;

        $assertIndexAndAddOption = function ($index, $option, $default) use (&$i) {
            return function (OptionsResolver $resolver) use (&$i, $index, $option, $default) {
                self::assertEquals($index, $i, 'Executed at index '.$index);

                ++$i;

                $resolver->setDefaults([$option => $default]);
            };
        };

        // First the default options are generated for the super type
        $this->parentType->expects($this->once())
            ->method('configureOptions')
            ->willReturnCallback($assertIndexAndAddOption(0, 'a', 'a_default'));

        // The field type itself
        $this->type->expects($this->once())
            ->method('configureOptions')
            ->willReturnCallback($assertIndexAndAddOption(1, 'b', 'b_default'));

        // And its extensions
        $this->extension1->expects($this->once())
            ->method('configureOptions')
            ->willReturnCallback($assertIndexAndAddOption(2, 'c', 'c_default'));

        $this->extension2->expects($this->once())
            ->method('configureOptions')
            ->willReturnCallback($assertIndexAndAddOption(3, 'd', 'd_default'));

        $givenOptions = ['a' => 'a_custom', 'c' => 'c_custom'];
        $resolvedOptions = ['a' => 'a_custom', 'b' => 'b_default', 'c' => 'c_custom', 'd' => 'd_default'];

        $resolver = $this->resolvedType->getOptionsResolver();

        self::assertEquals($resolvedOptions, $resolver->resolve($givenOptions));
    }

    /**
     * @test
     */
    public function it_creates_a_field()
    {
        $givenOptions = ['a' => 'a_custom', 'c' => 'c_custom'];
        $resolvedOptions = ['a' => 'a_custom', 'b' => 'b_default', 'c' => 'c_custom', 'd' => 'd_default'];
        $optionsResolver = $this->createOptionsResolverMock();

        $this->resolvedType = $this->getMockBuilder(GenericResolvedFieldType::class)
            ->setConstructorArgs([$this->type, [$this->extension1, $this->extension2], $this->parentResolvedType])
            ->setMethods(['getOptionsResolver'])
            ->getMock();

        $this->resolvedType->expects($this->once())
            ->method('getOptionsResolver')
            ->willReturn($optionsResolver);

        $optionsResolver->expects($this->once())
            ->method('resolve')
            ->with($givenOptions)
            ->willReturn($resolvedOptions);

        $field = $this->resolvedType->createField('name', $givenOptions);

        self::assertSame($this->resolvedType, $field->getType());
        self::assertSame($resolvedOptions, $field->getOptions());
    }

    /**
     * @test
     */
    public function it_builds_the_type()
    {
        $i = 0;

        $assertIndex = function ($index) use (&$i) {
            return function () use (&$i, $index) {
                self::assertEquals($index, $i, 'Executed at index '.$index);

                ++$i;
            };
        };

        $options = ['a' => 'Foo', 'b' => 'Bar'];
        $field = $this->createFieldMock();

        // First the field is built for the super type
        $this->parentType->expects($this->once())
            ->method('buildType')
            ->with($field, $options)
            ->willReturnCallback($assertIndex(0));

        // Then the type itself
        $this->type->expects($this->once())
            ->method('buildType')
            ->with($field, $options)
            ->willReturnCallback($assertIndex(1));

        // Then its extensions
        $this->extension1->expects($this->once())
            ->method('buildType')
            ->with($field, $options)
            ->willReturnCallback($assertIndex(2));

        $this->extension2->expects($this->once())
            ->method('buildType')
            ->with($field, $options)
            ->willReturnCallback($assertIndex(3));

        $this->resolvedType->buildType($field, $options);
    }

    public function testCreateView()
    {
        $field = $this->createFieldMock();
        $view = $this->resolvedType->createFieldView($field, new FieldSetView());

        self::assertInstanceOf(SearchFieldView::class, $view);
    }

    public function testGetBlockPrefix()
    {
        $this->type->expects(self::once())
            ->method('getBlockPrefix')
            ->willReturn('my_prefix');

        $resolvedType = new GenericResolvedFieldType($this->type);
        $this->assertSame('my_prefix', $resolvedType->getBlockPrefix());
    }

    public function testBuildView()
    {
        $options = ['a' => '1', 'b' => '2'];
        $field = $this->createFieldMock();
        $view = $this->createSearchFieldViewMock();

        $i = 0;

        $assertIndex = function ($index) use (&$i) {
            return function () use (&$i, $index) {
                self::assertEquals($index, $i, 'Executed at index '.$index);

                ++$i;
            };
        };

        // First the super type
        $this->parentType->expects($this->once())
            ->method('buildView')
            ->with($view, $field, $options)
            ->willReturnCallback($assertIndex(0));

        // Then the type itself
        $this->type->expects($this->once())
            ->method('buildView')
            ->with($view, $field, $options)
            ->willReturnCallback($assertIndex(1));

        // Then its extensions
        $this->extension1->expects($this->once())
            ->method('buildView')
            ->with($field, $view)
            ->willReturnCallback($assertIndex(2));

        $this->extension2->expects($this->once())
            ->method('buildView')
            ->with($field, $view)
            ->willReturnCallback($assertIndex(3));

        $this->resolvedType->buildFieldView($view, $field, $options);
    }

    /**
     * @return MockObject|FieldType
     */
    private function getMockFieldType(string $typeClass = AbstractFieldType::class)
    {
        return $this->getMockBuilder($typeClass)
            ->setMethods(['configureOptions', 'buildView', 'buildType', 'getBlockPrefix'])
            ->getMock();
    }

    /**
     * @return MockObject|AbstractFieldTypeExtension
     */
    private function getMockFieldTypeExtension()
    {
        return $this->getMockBuilder(AbstractFieldTypeExtension::class)
            ->setMethods(['getExtendedType', 'configureOptions', 'buildView', 'buildType']
        )->getMock();
    }

    /**
     * @return MockObject
     */
    private function createOptionsResolverMock()
    {
        return $this->getMockBuilder(OptionsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|FieldConfig
     */
    private function createFieldMock()
    {
        return $this->getMockBuilder(FieldConfig::class)->getMock();
    }

    /**
     * @return MockObject|SearchFieldView
     */
    private function createSearchFieldViewMock()
    {
        return $this->createMock(SearchFieldView::class);
    }
}
