<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Value;

use PhpSpec\ObjectBehavior;

class SingleValueSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(10);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Value\SingleValue');
    }

    public function it_should_return_a_value()
    {
        $this->getValue()->shouldReturn(10);
    }

    public function it_should_allow_changing_the_value()
    {
        $this->setValue(20);
        $this->getValue()->shouldReturn(20);
    }

    public function it_should_allow_an_object_as_value()
    {
        $value = new \DateTime();

        $this->beConstructedWith($value);
        $this->getValue()->shouldReturn($value);
    }

    public function it_should_allow_setting_an_object_as_value()
    {
        $value = new \DateTime();

        $this->setValue($value);
        $this->getValue()->shouldReturn($value);
    }
}
