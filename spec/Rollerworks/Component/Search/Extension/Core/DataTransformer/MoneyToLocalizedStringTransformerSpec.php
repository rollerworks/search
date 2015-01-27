<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Extension\Core\DataTransformer;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

class MoneyToLocalizedStringTransformerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer');
    }

    public function let()
    {
        $this->beConstructedWith(2, false, null, 1, 'EUR');
    }

    public function it_should_reverse_transform_to_a_MoneyObject()
    {
        \Locale::setDefault('de_AT');

        $value = new MoneyValue('EUR', '1.23');

        $this->transform($value)->shouldReturn('€ 1,23');
        $this->reverseTransform('€ 1,23')->shouldBeLike(new MoneyValue('EUR', '1.23'));
    }

    public function it_should_reverse_transform_without_currency_to_a_MoneyObject()
    {
        \Locale::setDefault('de_AT');

        $this->reverseTransform('1,23')->shouldBeLike(new MoneyValue('EUR', '1.23'));
    }
}
