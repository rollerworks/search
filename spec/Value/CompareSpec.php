<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Value;

use PhpSpec\ObjectBehavior;

class CompareSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(10, '>', '10');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Value\Compare');
    }

    function it_has_a_value()
    {
        $this->getValue()->shouldReturn(10);
    }

    function it_has_an_operator()
    {
        $this->getOperator()->shouldReturn('>');
    }

    function it_allows_an_object_as_value()
    {
        $value = new \DateTime();

        $this->beConstructedWith($value, '<', '2014-12-24');

        $this->getValue()->shouldReturn($value);
        $this->getOperator()->shouldReturn('<');
    }
}
