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

    function it_has_a_value()
    {
        $this->getValue()->shouldReturn('foo');
    }

    function it_has_a_patternType()
    {
        $this->getType()->shouldReturn(PatternMatch::PATTERN_CONTAINS);
    }

    function it_is_case_sensitive_by_default()
    {
        $this->isCaseInsensitive()->shouldReturn(false);
    }

    function it_allows_case_insensitive()
    {
        $this->beConstructedWith('foo', PatternMatch::PATTERN_CONTAINS, true);
        $this->isCaseInsensitive()->shouldReturn(true);
    }

    function it_throws_when_setting_an_invalid_value()
    {
        $this->shouldThrow(new \InvalidArgumentException('Value of PatternMatch must be a scalar value.'));
        $this->beConstructedWith(new \stdClass(), PatternMatch::PATTERN_CONTAINS);
    }

    function it_accepts_a_patternType_as_string()
    {
        $this->beConstructedWith('foo', 'CONTAINS');
        $this->getType()->shouldReturn(PatternMatch::PATTERN_CONTAINS);
    }
}
