<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\FieldRegistryInterface;
use Rollerworks\Component\Search\FieldTypeInterface;
use Rollerworks\Component\Search\ResolvedFieldTypeFactoryInterface;
use Rollerworks\Component\Search\ResolvedFieldTypeInterface;
use Rollerworks\Component\Search\SearchField;

class SearchFactorySpec extends ObjectBehavior
{
    function let(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory)
    {
        $this->beConstructedWith($registry->getWrappedObject(), $resolvedTypeFactory->getWrappedObject());
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\SearchFactory');
    }

    function it_creates_field_with_type_as_string(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory, ResolvedFieldTypeInterface $type)
    {
        $this->beConstructedWith($registry->getWrappedObject(), $resolvedTypeFactory->getWrappedObject());

        $expectedField = new SearchField('id', $type->getWrappedObject());

        $type->getName()->willReturn('number');
        $type->createField('id', array())->willReturn($expectedField);

        $registry->getType('number')->willReturn($type->getWrappedObject());
        $type->buildType(Argument::exact($expectedField), array())->shouldBeCalled();

        $this->createField('id', 'number')->shouldEqual($expectedField);
    }

    function it_creates_field_with_type_as_object(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory, FieldTypeInterface $type, ResolvedFieldTypeInterface $resolvedType)
    {
        $this->beConstructedWith($registry->getWrappedObject(), $resolvedTypeFactory->getWrappedObject());

        $expectedField = new SearchField('id', $resolvedType->getWrappedObject());
        $resolvedType->buildType(Argument::exact($expectedField), array())->shouldBeCalled();

        $resolvedType->getName()->willReturn('number');
        $resolvedType->createField('id', array())->willReturn($expectedField);

        $resolvedTypeFactory->createResolvedType(Argument::exact($type->getWrappedObject()), array(), null)->willReturn($resolvedType);

        $this->createField('id', $type)->shouldEqual($expectedField);
    }

    function it_creates_field_with_model_ref(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory, ResolvedFieldTypeInterface $type)
    {
        $this->beConstructedWith($registry->getWrappedObject(), $resolvedTypeFactory->getWrappedObject());

        $expectedField = new SearchField(
            'uid', $type->getWrappedObject(), array(
            'model_class' => 'Entity\User',
            'model_property' => 'id',
        )
        );
        $expectedField->setModelRef('Entity\User', 'id');

        $type->getName()->willReturn('number');
        $type->createField(
            'uid',
            array(
                'model_class' => 'Entity\User',
                'model_property' => 'id',
            )
        )->willReturn($expectedField);

        $registry->getType('number')->willReturn($type->getWrappedObject());
        $type->buildType(
            Argument::exact($expectedField),
            array(
                'model_class' => 'Entity\User',
                'model_property' => 'id',
            )
        )->shouldBeCalled();

        $this->createField(
            'uid',
            'number',
            array(
                'model_class' => 'Entity\User',
                'model_property' => 'id',
            )
        )->shouldEqual($expectedField);
    }
}
