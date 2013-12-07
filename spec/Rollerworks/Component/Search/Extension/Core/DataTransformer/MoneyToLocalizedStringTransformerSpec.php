<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search\Extension\Core\DataTransformer;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

class MoneyToLocalizedStringTransformerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer');
    }

    function let()
    {
        $this->beConstructedWith(2, false, null, 1, 'EUR');
    }

    function it_should_reverse_transform_to_a_MoneyObject()
    {
        \Locale::setDefault('de_AT');

        $value = new MoneyValue('EUR', '1.23');

        $this->transform($value)->shouldReturn('€ 1,23');
        $this->reverseTransform('€ 1,23')->shouldBeLike(new MoneyValue('EUR', '1.23'));
    }

    function it_should_reverse_transform_without_currency_to_a_MoneyObject()
    {
        \Locale::setDefault('de_AT');

        $this->reverseTransform('1,23')->shouldBeLike(new MoneyValue('EUR', '1.23'));
    }
}
