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

class RangeSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(10, 20);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Value\Range');
    }

    public function it_should_have_a_lower_value()
    {
        $this->getLower()->shouldReturn(10);
    }

    public function it_should_have_an_upper_value()
    {
        $this->getUpper()->shouldReturn(20);
    }

    public function it_should_allow_changing_the_lower_value()
    {
        $this->setLower(5);

        $this->getLower()->shouldReturn(5);
        $this->getUpper()->shouldReturn(20);
    }

    public function it_should_allow_changing_the_upper_value()
    {
        $this->setUpper(30);

        $this->getLower()->shouldReturn(10);
        $this->getUpper()->shouldReturn(30);
    }

    public function its_lower_value_should_be_inclusive_by_default()
    {
        $this->isLowerInclusive()->shouldReturn(true);
    }

    public function its_upper_value_should_be_inclusive_by_default()
    {
        $this->isUpperInclusive()->shouldReturn(true);
    }

    public function it_should_allow_exclusive_lower_value()
    {
        $this->beConstructedWith(10, 20, false);
        $this->isLowerInclusive()->shouldReturn(false);
    }

    public function it_should_allow_exclusive_upper_value()
    {
        $this->beConstructedWith(10, 20, true, false);
        $this->isUpperInclusive()->shouldReturn(false);
    }
}
