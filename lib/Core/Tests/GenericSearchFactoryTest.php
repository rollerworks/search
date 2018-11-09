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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\ResolvedFieldType;
use Rollerworks\Component\Search\Field\TypeRegistry;
use Rollerworks\Component\Search\FieldSetRegistry;
use Rollerworks\Component\Search\GenericSearchFactory;

/**
 * @internal
 */
final class GenericSearchFactoryTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $registry;

    /**
     * @var MockObject
     */
    private $fieldSetRegistry;

    /**
     * @var GenericSearchFactory
     */
    private $factory;

    /**
     * @var MockObject
     */
    private $fieldConfig;

    protected function setUp()
    {
        $this->fieldSetRegistry = $this->createMock(FieldSetRegistry::class);
        $this->registry = $this->createMock(TypeRegistry::class);
        $this->fieldConfig = $this->createMock(FieldConfig::class);

        $this->factory = new GenericSearchFactory($this->registry, $this->fieldSetRegistry);
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

        self::assertSame($this->fieldConfig, $this->factory->createField('name', TextType::class, $options));
    }

    private function getMockResolvedType()
    {
        return $this->getMockBuilder(ResolvedFieldType::class)->getMock();
    }
}
