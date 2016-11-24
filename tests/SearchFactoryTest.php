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

use Rollerworks\Component\Search\SearchFactory;
use Rollerworks\Component\Search\Tests\Fixtures\FooSubType;
use Rollerworks\Component\Search\Tests\Fixtures\FooType;

final class SearchFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resolvedTypeFactory;

    /**
     * @var SearchFactory
     */
    private $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldConfig;

    protected function setUp()
    {
        $this->resolvedTypeFactory = $this->getMockBuilder('Rollerworks\Component\Search\ResolvedFieldTypeFactoryInterface')->getMock();
        $this->registry = $this->getMockBuilder('Rollerworks\Component\Search\FieldRegistryInterface')->getMock();
        $this->fieldConfig = $this->getMockBuilder('Rollerworks\Component\Search\FieldConfigInterface')->getMock();

        $this->factory = new SearchFactory($this->registry, $this->resolvedTypeFactory);
    }

    /**
     * @test
     */
    public function create_field_with_type_name()
    {
        $options = ['a' => '1', 'b' => '2'];
        $resolvedOptions = ['a' => '2', 'b' => '3'];
        $resolvedType = $this->getMockResolvedType();

        $this->registry->expects($this->once())
            ->method('getType')
            ->with('type')
            ->will($this->returnValue($resolvedType));

        $resolvedType->expects($this->once())
            ->method('createField')
            ->with('name', $options)
            ->will($this->returnValue($this->fieldConfig));

        $this->fieldConfig->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($resolvedOptions));

        $resolvedType->expects($this->once())
            ->method('buildType')
            ->with($this->fieldConfig, $resolvedOptions);

        $this->assertSame($this->fieldConfig, $this->factory->createField('name', 'type', $options));
    }

    /**
     * @test
     */
    public function create_field_with_type_instance()
    {
        $options = ['a' => '1', 'b' => '2'];
        $resolvedOptions = ['a' => '2', 'b' => '3'];
        $type = new FooType();
        $resolvedType = $this->getMockResolvedType();

        $this->resolvedTypeFactory->expects($this->once())
            ->method('createResolvedType')
            ->with($type)
            ->will($this->returnValue($resolvedType));

        $this->registry->expects($this->never())
            ->method('getType')
            ->with('type');

        $resolvedType->expects($this->once())
            ->method('createField')
            ->with('name', $options)
            ->will($this->returnValue($this->fieldConfig));

        $this->fieldConfig->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($resolvedOptions));

        $resolvedType->expects($this->once())
            ->method('buildType')
            ->with($this->fieldConfig, $resolvedOptions);

        $this->assertSame($this->fieldConfig, $this->factory->createField('name', $type, $options));
    }

    /**
     * @test
     */
    public function create_field_with_type_instance_with_parent_type()
    {
        $options = ['a' => '1', 'b' => '2'];
        $resolvedOptions = ['a' => '2', 'b' => '3'];
        $type = new FooSubType();
        $resolvedType = $this->getMockResolvedType();
        $parentResolvedType = $this->getMockResolvedType();

        $this->registry->expects($this->once())
            ->method('getType')
            ->with('foo')
            ->will($this->returnValue($parentResolvedType));

        $this->resolvedTypeFactory->expects($this->once())
            ->method('createResolvedType')
            ->with($type, [], $parentResolvedType)
            ->will($this->returnValue($resolvedType));

        $resolvedType->expects($this->once())
            ->method('createField')
            ->with('name', $options)
            ->will($this->returnValue($this->fieldConfig));

        $this->fieldConfig->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($resolvedOptions));

        $resolvedType->expects($this->once())
            ->method('buildType')
            ->with($this->fieldConfig, $resolvedOptions);

        $this->assertSame($this->fieldConfig, $this->factory->createField('name', $type, $options));
    }

    /**
     * @test
     *
     * @group legacy
     */
    public function create_field_with_model_reference()
    {
        $options = ['a' => '1', 'b' => '2'];
        $resolvedOptions = ['a' => '2', 'b' => '3'];
        $resolvedType = $this->getMockResolvedType();

        $this->fieldConfig = $this->getMockBuilder('Rollerworks\Component\Search\SearchField')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->once())
            ->method('getType')
            ->with('type')
            ->will($this->returnValue($resolvedType));

        $resolvedType->expects($this->once())
            ->method('createField')
            ->with('name', $options)
            ->will($this->returnValue($this->fieldConfig));

        $this->fieldConfig->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($resolvedOptions));

        $this->fieldConfig->expects($this->once())
            ->method('setModelRef')
            ->with('Entity\User', 'id');

        $resolvedType->expects($this->once())
            ->method('buildType')
            ->with($this->fieldConfig, $resolvedOptions);

        $this->assertSame(
            $this->fieldConfig,
            $this->factory->createFieldForProperty('Entity\User', 'id', 'name', 'type', $options)
        );
    }

    private function getMockResolvedType()
    {
        return $this->getMockBuilder('Rollerworks\Component\Search\ResolvedFieldTypeInterface')->getMock();
    }
}
