<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Input;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\FieldSet;

class ProcessorConfigSpec extends ObjectBehavior
{
    public function let()
    {
        $fieldSet = new FieldSet('test');
        $this->beConstructedWith($fieldSet);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Input\ProcessorConfig');
    }

    public function it_has_a_fieldSet()
    {
        $this->getFieldSet()->shouldReturnAnInstanceOf('Rollerworks\Component\Search\FieldSet');
    }

    public function it_allows_setting_maximum_nesting_level()
    {
        $this->setMaxNestingLevel(5);
        $this->getMaxNestingLevel()->shouldReturn(5);

        $this->setMaxNestingLevel(10);
        $this->getMaxNestingLevel()->shouldReturn(10);
    }

    public function it_allows_setting_maximum_values_count()
    {
        $this->setMaxValues(5);
        $this->getMaxValues()->shouldReturn(5);

        $this->setMaxValues(10);
        $this->getMaxValues()->shouldReturn(10);
    }

    public function it_allows_setting_maximum_groups_count()
    {
        $this->setMaxGroups(5);
        $this->getMaxGroups()->shouldReturn(5);

        $this->setMaxGroups(10);
        $this->getMaxGroups()->shouldReturn(10);
    }
}
