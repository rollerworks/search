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
use Rollerworks\Component\Search\FieldConfigInterface;

class FieldSetSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\FieldSet');
    }

    public function it_should_have_no_name_by_default()
    {
        $this->getSetName()->shouldReturn(null);
    }

    public function it_should_allow_setting_a_name()
    {
        $this->beConstructedWith('users');

        $this->getSetName()->shouldReturn('users');
    }

    public function it_should_complain_when_name_is_invalid()
    {
        $this->shouldThrow(new \InvalidArgumentException('The name "(users)" contains illegal characters. Names should start with a letter, digit or underscore and only contain letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").'));
        $this->beConstructedWith('(users)');
    }

    public function it_should_have_no_fields_by_default()
    {
        $this->all()->shouldHaveCount(0);
        $this->count()->shouldReturn(0);
    }

    public function it_should_allow_adding_fields(FieldConfigInterface $fieldConfig, FieldConfigInterface $fieldConfig2)
    {
        $this->set('id', $fieldConfig);
        $this->set('name', $fieldConfig2);

        $this->all()->shouldReturn(array('id' => $fieldConfig, 'name' => $fieldConfig2));
        $this->count()->shouldReturn(2);
    }

    public function it_should_allow_replacing_existing_fields(FieldConfigInterface $fieldConfig, FieldConfigInterface $fieldConfig2, FieldConfigInterface $fieldConfig3)
    {
        $this->set('id', $fieldConfig);
        $this->set('name', $fieldConfig3);
        $this->replace('id', $fieldConfig2);

        $this->all()->shouldReturn(array('id' => $fieldConfig2, 'name' => $fieldConfig3));
        $this->count()->shouldReturn(2);
    }

    public function it_should_allow_returning_a_field(FieldConfigInterface $fieldConfig, FieldConfigInterface $fieldConfig2)
    {
        $this->set('id', $fieldConfig);
        $this->set('name', $fieldConfig2);

        $this->get('id')->shouldReturn($fieldConfig);
    }

    public function it_should_allow_returning_if_a_field_exists(FieldConfigInterface $fieldConfig, FieldConfigInterface $fieldConfig2)
    {
        $this->set('id', $fieldConfig);
        $this->set('name', $fieldConfig2);

        $this->has('id')->shouldReturn(true);
        $this->has('foo')->shouldReturn(false);
    }

    public function it_should_allow_removing_fields(FieldConfigInterface $fieldConfig, FieldConfigInterface $fieldConfig2)
    {
        $this->set('id', $fieldConfig);
        $this->set('name', $fieldConfig2);

        $this->remove('id');

        $this->all()->shouldReturn(array('name' => $fieldConfig2));
        $this->count()->shouldReturn(1);
    }
}
