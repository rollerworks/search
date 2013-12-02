<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search\Value;

use PhpSpec\ObjectBehavior;

class CompareSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(10, '>');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Value\Compare');
    }

    function it_should_return_a_value()
    {
        $this->getValue()->shouldReturn(10);
    }

    function it_should_return_the_operator()
    {
        $this->getOperator()->shouldReturn('>');
    }

    function it_should_allow_changing_the_value()
    {
        $this->setValue(20);

        $this->getValue()->shouldReturn(20);
        $this->getOperator()->shouldReturn('>');
    }

    function it_should_allow_an_object_as_value()
    {
        $value = new \DateTime();

        $this->beConstructedWith($value, '<');

        $this->getValue()->shouldReturn($value);
        $this->getOperator()->shouldReturn('<');
    }

    function it_should_allow_setting_an_object_as_value()
    {
        $value = new \DateTime();

        $this->setValue($value);
        $this->getValue()->shouldReturn($value);
    }
}
