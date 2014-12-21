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
use Rollerworks\Component\Search\Value\PatternMatch;

class PatternMatchSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('foo', PatternMatch::PATTERN_CONTAINS);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Value\PatternMatch');
    }

    public function it_should_return_the_value()
    {
        $this->getValue()->shouldReturn('foo');
    }

    public function it_should_return_the_patternType()
    {
        $this->getType()->shouldReturn(PatternMatch::PATTERN_CONTAINS);
    }

    public function its_case_sensitive_by_default()
    {
        $this->isCaseInsensitive()->shouldReturn(false);
    }

    public function it_allows_case_insensitive()
    {
        $this->setCaseInsensitive(true);
        $this->isCaseInsensitive()->shouldReturn(true);
    }

    public function it_should_complain_when_setting_an_object_as_value()
    {
        $this->shouldThrow(new \InvalidArgumentException('Value of PatternMatch must be a scalar value.'));
        $this->beConstructedWith(new \stdClass, PatternMatch::PATTERN_CONTAINS);
    }

    public function it_should_convert_a_patternType_as_text_to_an_integer()
    {
        $this->beConstructedWith('foo', 'CONTAINS');
        $this->getType()->shouldReturn(PatternMatch::PATTERN_CONTAINS);
    }
}
