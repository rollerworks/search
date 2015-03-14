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

class RangeSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(10, 20);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Value\Range');
    }

    function it_has_a_lower_value()
    {
        $this->getLower()->shouldReturn(10);
    }

    function it_has_an_upper_value()
    {
        $this->getUpper()->shouldReturn(20);
    }

    function it_has_a_lower_viewValue()
    {
        $this->getViewLower()->shouldReturn('10');
    }

    function it_has_an_upper_viewValue()
    {
        $this->getViewUpper()->shouldReturn('20');
    }

    function its_lower_value_is_inclusive_by_default()
    {
        $this->isLowerInclusive()->shouldReturn(true);
    }

    function its_upper_value_should_be_inclusive_by_default()
    {
        $this->isUpperInclusive()->shouldReturn(true);
    }

    function it_allows_exclusive_lower_value()
    {
        $this->beConstructedWith(10, 20, false);
        $this->isLowerInclusive()->shouldReturn(false);
    }

    function it_allows_exclusive_upper_value()
    {
        $this->beConstructedWith(10, 20, true, false);
        $this->isUpperInclusive()->shouldReturn(false);
    }
}
