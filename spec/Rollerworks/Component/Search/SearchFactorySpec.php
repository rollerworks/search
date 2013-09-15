<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    public function let(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory)
    {
        $this->beConstructedWith($registry->getWrappedObject(), $resolvedTypeFactory->getWrappedObject());
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\SearchFactory');
    }

    public function it_creates_field_with_type_as_string(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory, ResolvedFieldTypeInterface $type)
    {
        $this->beConstructedWith($registry->getWrappedObject(), $resolvedTypeFactory->getWrappedObject());

        $type->getName()->willReturn('number');
        $registry->getType('number')->willReturn($type->getWrappedObject());

        $expectedField = new SearchField('id', $type->getWrappedObject());
        $this->createField('id', 'number')->shouldBeLike($expectedField);
    }

    public function it_creates_field_with_type_as_object(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory, FieldTypeInterface $type, ResolvedFieldTypeInterface $resolvedType)
    {
        $this->beConstructedWith($registry->getWrappedObject(), $resolvedTypeFactory->getWrappedObject());

        $resolvedType->getName()->willReturn('number');
        $resolvedTypeFactory->createResolvedType(Argument::exact($type->getWrappedObject()), array(), null)->willReturn($resolvedType);

        $expectedField = new SearchField('id', $resolvedType->getWrappedObject());
        $this->createField('id', $type)->shouldBeLike($expectedField);
    }

    public function it_creates_field_with_model_ref(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory, ResolvedFieldTypeInterface $type)
    {
        $this->beConstructedWith($registry->getWrappedObject(), $resolvedTypeFactory->getWrappedObject());

        $type->getName()->willReturn('number');
        $registry->getType('number')->willReturn($type->getWrappedObject());

        $expectedField = new SearchField('uid', $type->getWrappedObject());
        $expectedField->setModelRef('Entity\User', 'id');

        $this->createFieldForProperty('Entity\User', 'id', 'uid', 'number')->shouldBeLike($expectedField);
    }
}
