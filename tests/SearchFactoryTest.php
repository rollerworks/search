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

use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\SearchFactory;

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
            ->with(TextType::class)
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

        $this->assertSame($this->fieldConfig, $this->factory->createField('name', TextType::class, $options));
    }

    private function getMockResolvedType()
    {
        return $this->getMockBuilder('Rollerworks\Component\Search\ResolvedFieldTypeInterface')->getMock();
    }
}
