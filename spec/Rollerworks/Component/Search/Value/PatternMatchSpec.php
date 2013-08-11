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
use Prophecy\Argument;
use Rollerworks\Component\Search\Value\PatternMatch;

class PatternMatchSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('foo', PatternMatch::PATTERN_CONTAINS);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Value\PatternMatch');
    }

    function it_should_return_the_value()
    {
        $this->getValue()->shouldReturn('foo');
    }

    function it_should_return_the_patternType()
    {
        $this->getType()->shouldReturn(PatternMatch::PATTERN_CONTAINS);
    }

    function it_should_complain_when_setting_an_object_as_value()
    {
        $this->shouldThrow(new \InvalidArgumentException('Value of PatternMatch must be a scalar value.'));
        $this->beConstructedWith(new \stdClass, PatternMatch::PATTERN_CONTAINS);
    }

    function it_should_convert_a_patternType_as_text_to_an_integer()
    {
        $this->beConstructedWith('foo', 'CONTAINS');
        $this->getType()->shouldReturn(PatternMatch::PATTERN_CONTAINS);
    }
}
