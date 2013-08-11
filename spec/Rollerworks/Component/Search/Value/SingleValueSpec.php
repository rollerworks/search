<?php

namespace spec\Rollerworks\Component\Search\Value;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Bundle\RecordFilterBundle\Type\DateTime;

class SingleValueSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(10);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Value\SingleValue');
    }

    function it_should_return_a_value()
    {
        $this->getValue()->shouldReturn(10);
    }

    function it_should_allow_changing_the_value()
    {
        $this->setValue(20);
        $this->getValue()->shouldReturn(20);
    }

    function it_should_allow_an_object_as_value()
    {
        $value = new \DateTime();

        $this->beConstructedWith($value);
        $this->getValue()->shouldReturn($value);
    }

    function it_should_allow_setting_an_object_as_value()
    {
        $value = new \DateTime();

        $this->setValue($value);
        $this->getValue()->shouldReturn($value);
    }
}
