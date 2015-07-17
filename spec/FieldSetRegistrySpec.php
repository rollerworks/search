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
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\FieldSet;

class FieldSetRegistrySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\FieldSetRegistry');
    }

    function it_is_a_FieldSetRegistry()
    {
        $this->shouldbeAnInstanceOf('Rollerworks\Component\Search\FieldSetRegistryInterface');
    }

    function it_has_no_FieldSets_when_initialized()
    {
        $this->all()->shouldReturn([]);
    }

    function it_supports_adding_FieldSets()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $fieldSet2 = new FieldSet('test2');
        $fieldSet2->lockConfig();

        $this->add($fieldSet);
        $this->add($fieldSet2);

        $this->all()->shouldReturn([$fieldSet->getSetName() => $fieldSet, $fieldSet2->getSetName() => $fieldSet2]);
    }

    function it_can_tell_if_a_FieldSet_is_registered()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $this->add($fieldSet);

        $this->has('test')->shouldReturn(true);
        $this->has('test2')->shouldReturn(false);
    }

    function it_supports_getting_a_FieldSet()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $this->add($fieldSet);

        $this->get('test')->shouldReturn($fieldSet);
    }

    function it_throws_when_getting_an_unregistered_FieldSet()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $this->add($fieldSet);

        $this->shouldThrow(
            new InvalidArgumentException('Unable to get none registered FieldSet "test2".')
        )->during('get', ['test2']);
    }

    function it_disallows_overwriting_a_FieldSet()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $this->add($fieldSet);

        $this->shouldThrow(
            new InvalidArgumentException('Unable to overwrite already registered FieldSet "test".')
        )->during('add', [$fieldSet]);
    }

    // open meaning that the configuration is not locked
    function it_disallows_registering_an_open_FieldSet()
    {
        $fieldSet = new FieldSet('test');

        $this->shouldThrow(
            new InvalidArgumentException('Unable to register unlocked FieldSet "test".')
        )->during('add', [$fieldSet]);
    }
}
