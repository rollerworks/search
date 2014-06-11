<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\FieldTypeExtensionInterface;
use Rollerworks\Component\Search\FieldTypeInterface;
use Rollerworks\Component\Search\ResolvedFieldTypeFactoryInterface;
use Rollerworks\Component\Search\ResolvedFieldTypeInterface;
use Rollerworks\Component\Search\SearchExtensionInterface;

class FieldRegistrySpec extends ObjectBehavior
{
    public function let(ResolvedFieldTypeFactoryInterface $resolvedFieldFactory)
    {
        $this->beConstructedWith(array(), $resolvedFieldFactory);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\FieldRegistry');
    }

    public function it_loads_types_from_extensions(SearchExtensionInterface $extension, FieldTypeInterface $type, ResolvedFieldTypeInterface $resolvedType, ResolvedFieldTypeFactoryInterface $resolvedFieldFactory)
    {
        $type->getName()->willReturn('integer');
        $type->getParent()->willReturn(null);

        $resolvedType->getName()->willReturn('integer');
        $resolvedType->getInnerType()->willReturn($type);

        $extension->hasType('integer')->willReturn(true);
        $extension->getType('integer')->willReturn($type);
        $extension->getTypeExtensions('integer')->willReturn(array());

        $resolvedFieldFactory->createResolvedType($type, array(), null)->willReturn($resolvedType);
        $this->beConstructedWith(array($extension), $resolvedFieldFactory);

        $this->getType('integer')->shouldEqual($resolvedType);
    }

    public function it_loads_type_extensions(SearchExtensionInterface $extension, SearchExtensionInterface $extension2, FieldTypeExtensionInterface $fieldExtension, FieldTypeExtensionInterface $fieldExtension2, FieldTypeInterface $type, ResolvedFieldTypeInterface $resolvedType, ResolvedFieldTypeFactoryInterface $resolvedFieldFactory)
    {
        $type->getName()->willReturn('integer');
        $type->getParent()->willReturn(null);

        $resolvedType->getName()->willReturn('integer');
        $resolvedType->getInnerType()->willReturn($type);

        $extension->hasType('integer')->willReturn(true);
        $extension->getType('integer')->willReturn($type);
        $extension->getTypeExtensions('integer')->willReturn(array($fieldExtension->getWrappedObject()));

        $extension->hasType(Argument::any())->willReturn(false);
        $extension2->getTypeExtensions('integer')->willReturn(array($fieldExtension2->getWrappedObject()));

        $resolvedFieldFactory->createResolvedType($type, array($fieldExtension->getWrappedObject(), $fieldExtension2->getWrappedObject()), null)->willReturn($resolvedType);
        $this->beConstructedWith(array($extension->getWrappedObject(), $extension2->getWrappedObject()), $resolvedFieldFactory);

        $this->getType('integer')->shouldEqual($resolvedType);
    }
}
