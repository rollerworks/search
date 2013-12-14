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
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceListInterface;

class ChoiceToValueTransformerSpec extends ObjectBehavior
{
    function let(ChoiceListInterface $choices)
    {
        $this->beConstructedWith($choices);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToValueTransformer');
        $this->shouldImplement('Rollerworks\Component\Search\DataTransformerInterface');
    }

    function it_transformers_label_to_choice(ChoiceListInterface $choices)
    {
        $this->beConstructedWith($choices);

        $choices->getValueForChoice('active')->willReturn('1');
        $choices->getValueForChoice('removed')->willReturn('2');

        $this->transform('active')->shouldReturn('1');
        $this->transform('removed')->shouldReturn('2');
    }

    function it_reverse_transformers_choice_to_label(ChoiceListInterface $choices)
    {
        $this->beConstructedWith($choices);

        $choices->getChoiceForValue('1')->willReturn('active');
        $choices->getChoiceForValue('2')->willReturn('removed');

        $this->reverseTransform('1')->shouldReturn('active');
        $this->reverseTransform('2')->shouldReturn('removed');
    }
}
