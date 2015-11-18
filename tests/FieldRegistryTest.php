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

use Rollerworks\Component\Search\FieldRegistry;
use Rollerworks\Component\Search\FieldTypeInterface;
use Rollerworks\Component\Search\PreloadedExtension;

final class FieldRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_loads_types_from_extensions()
    {
        $extension = new PreloadedExtension(['integer' => $integerType = $this->createTypeMock('integer')]);
        $extension2 = new PreloadedExtension(['text' => $textType = $this->createTypeMock('text')]);

        $resolvedFieldTypeFactory = $this->prophesize('Rollerworks\Component\Search\ResolvedFieldTypeFactory');
        $resolvedFieldTypeFactory->createResolvedType($integerType, [], null)->willReturn($this->createResolvedTypeMock($integerType));
        $resolvedFieldTypeFactory->createResolvedType($textType, [], null)->willReturn($this->createResolvedTypeMock($textType));

        $registry = new FieldRegistry([$extension, $extension2], $resolvedFieldTypeFactory->reveal());

        $this->assertTrue($registry->hasType('integer'));
        $this->assertTrue($registry->hasType('text'));
        $this->assertFalse($registry->hasType('money'));

        $this->assertInstanceOf('Rollerworks\Component\Search\ResolvedFieldTypeInterface', $registry->getType('integer'));
        $this->assertInstanceOf('Rollerworks\Component\Search\ResolvedFieldTypeInterface', $registry->getType('text'));
    }

    /**
     * @test
     */
    public function it_loads_type_extensions()
    {
        $extension = new PreloadedExtension(['text' => $textType = $this->createTypeMock('text')]);
        $extension2 = new PreloadedExtension(
            [
                'field' => $fieldType = $this->createTypeMock('field'),
                'integer' => $integerType = $this->createTypeMock('integer', 'field'),
            ],
            ['text' => [$textTypeExtension = $this->createTypeExtensionMock('text')]]
        );

        $resolvedFieldTypeFactory = $this->prophesize('Rollerworks\Component\Search\ResolvedFieldTypeFactory');
        $resolvedFieldTypeFactory->createResolvedType($fieldType, [], null)->willReturn($resolvedField = $this->createResolvedTypeMock($fieldType));
        $resolvedFieldTypeFactory->createResolvedType($integerType, [], $resolvedField)->willReturn($this->createResolvedTypeMock($integerType));
        $resolvedFieldTypeFactory->createResolvedType($textType, [$textTypeExtension], null)->willReturn($this->createResolvedTypeMock($textType));

        $registry = new FieldRegistry([$extension, $extension2], $resolvedFieldTypeFactory->reveal());

        $this->assertTrue($registry->hasType('integer'));
        $this->assertTrue($registry->hasType('text'));
        $this->assertFalse($registry->hasType('money'));

        $this->assertInstanceOf('Rollerworks\Component\Search\ResolvedFieldTypeInterface', $registry->getType('integer'));
        $this->assertInstanceOf('Rollerworks\Component\Search\ResolvedFieldTypeInterface', $registry->getType('text'));
    }

    private function createTypeMock($name, $parent = null)
    {
        $type = $this->prophesize('Rollerworks\Component\Search\FieldTypeInterface');
        $type->getName()->willReturn($name);
        $type->getParent()->willReturn($parent);

        return $type->reveal();
    }

    private function createResolvedTypeMock(FieldTypeInterface $type)
    {
        $resolvedType = $this->prophesize('Rollerworks\Component\Search\ResolvedFieldTypeInterface');
        $resolvedType->getName()->willReturn($type->getName());
        $resolvedType->getInnerType()->willReturn($type);

        return $resolvedType->reveal();
    }

    private function createTypeExtensionMock($name)
    {
        $fieldExtension = $this->prophesize('Rollerworks\Component\Search\FieldTypeExtensionInterface');
        $fieldExtension->getExtendedType()->willReturn($name);

        return $fieldExtension->reveal();
    }
}
