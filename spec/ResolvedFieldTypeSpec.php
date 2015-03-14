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
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\FieldTypeExtensionInterface;
use Rollerworks\Component\Search\FieldTypeInterface;
use Rollerworks\Component\Search\ResolvedFieldTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResolvedFieldTypeSpec extends ObjectBehavior
{
    function let(FieldTypeInterface $innerType)
    {
        $innerType->getName()->willReturn('date');
        $innerType->configureOptions(Argument::type('Symfony\Component\OptionsResolver\OptionsResolver'))->willReturn(null);

        $this->beConstructedWith($innerType);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\ResolvedFieldType');
        $this->shouldImplement('Rollerworks\Component\Search\ResolvedFieldTypeInterface');
    }

    function it_should_have_a_name()
    {
        $this->getName()->shouldReturn('date');
    }

    function it_should_have_a_inner_type()
    {
        $this->getInnerType()->shouldImplement('Rollerworks\Component\Search\FieldTypeInterface');
    }

    function it_should_have_no_parent_by_default()
    {
        $this->getParent()->shouldReturn(null);
    }

    function it_should_have_no_typeExtensions_by_default()
    {
        $this->getTypeExtensions()->shouldHaveCount(0);
    }

    function it_should_allow_setting_typeExtensions(FieldTypeInterface $innerType, FieldTypeExtensionInterface $extension1, FieldTypeExtensionInterface $extension2)
    {
        $innerType->getName()->willReturn('date');
        $innerType->configureOptions(Argument::type('Symfony\Component\OptionsResolver\OptionsResolver'))->willReturn(null);

        $extensions = array($extension1, $extension2);
        $this->beConstructedWith($innerType, $extensions);

        $this->getTypeExtensions()->shouldReturn($extensions);
    }

    function it_should_complain_when_given_an_invalid_typeExtension(FieldTypeInterface $innerType)
    {
        $innerType->getName()->willReturn('date');
        $innerType->configureOptions(Argument::type('Symfony\Component\OptionsResolver\OptionsResolver'))->willReturn(null);

        $extensions = array(new \stdClass());

        $this->shouldThrow(new UnexpectedTypeException($extensions[0], 'Rollerworks\Component\Search\FieldTypeExtensionInterface'));
        $this->beConstructedWith($innerType, $extensions);
    }

    function it_should_provide_an_optionsResolver()
    {
        $this->getOptionsResolver()->shouldReturnAnInstanceOf('Symfony\Component\OptionsResolver\OptionsResolver');
    }

    function it_should_include_options_of_the_parent_type(FieldTypeInterface $innerType, ResolvedFieldTypeInterface $parentType)
    {
        $optionsResolver = new OptionsResolver();

        $innerType->getName()->willReturn('event');
        $innerType->getParent()->willReturn('date');

        $parentType->getName()->willReturn('date');
        $parentType->getParent()->willReturn(null);
        $parentType->getOptionsResolver()->willReturn($optionsResolver);

        $innerType->configureOptions(Argument::type('Symfony\Component\OptionsResolver\OptionsResolver'))->willReturn(null);

        $this->beConstructedWith($innerType, array(), $parentType);
        $this->getOptionsResolver()->shouldReturnAnInstanceOf('Symfony\Component\OptionsResolver\OptionsResolver');
    }
}
