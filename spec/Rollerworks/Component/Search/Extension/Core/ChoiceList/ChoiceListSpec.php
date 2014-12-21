<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Extension\Core\ChoiceList;

use PhpSpec\ObjectBehavior;

class ChoiceListSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(array('creditcard', 'cash'), array('credit-card-payment', 'cash-payment'));
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList');
    }

    public function it_returns_the_choice_by_value()
    {
        $this->getChoiceForValue(0)->shouldReturn('creditcard');
        $this->getChoiceForValue(1)->shouldReturn('cash');
    }

    public function its_choice_returns_null_when_the_value_is_not_set()
    {
        $this->getChoiceForValue(2)->shouldReturn(null);
    }

    public function it_returns_the_choice_by_label()
    {
        $this->getChoiceForLabel('credit-card-payment')->shouldReturn('creditcard');
        $this->getChoiceForLabel('cash-payment')->shouldReturn('cash');
    }

    public function its_choice_returns_null_when_the_label_is_not_set()
    {
        $this->getChoiceForValue('paypal')->shouldReturn(null);
    }

    public function it_returns_the_label_by_choice()
    {
        $this->getLabelForChoice('creditcard')->shouldReturn('credit-card-payment');
        $this->getLabelForChoice('cash')->shouldReturn('cash-payment');
    }

    public function its_label_returns_null_when_the_choice_is_not_set()
    {
        $this->getLabelForChoice('paypal')->shouldReturn(null);
    }
}
