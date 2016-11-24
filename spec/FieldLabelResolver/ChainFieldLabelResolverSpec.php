<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\FieldLabelResolver;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\FieldLabelResolverInterface;
use Rollerworks\Component\Search\FieldSet;

class ChainFieldLabelResolverSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith([]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\FieldLabelResolver\ChainFieldLabelResolver');
    }

    public function it_returns_the_input_when_there_are_no_resolvers(FieldSet $fieldSet, FieldLabelResolverInterface $resolver)
    {
        $resolver->resolveFieldLabel($fieldSet, 'user')->willReturn('user');
        $resolver->resolveFieldLabel($fieldSet, 'user')->willReturn('user');
    }

    public function it_resolves_a_label(FieldSet $fieldSet, FieldLabelResolverInterface $resolver)
    {
        $this->beConstructedWith([$resolver]);

        $resolver->resolveFieldLabel($fieldSet, 'user_id')->willReturn('user');
        $resolver->resolveFieldLabel($fieldSet, 'id')->willReturn('id');
        $resolver->resolveFieldLabel($fieldSet, 'name')->willReturn(null);

        $this->resolveFieldLabel($fieldSet, 'user_id')->shouldReturn('user');
        $this->resolveFieldLabel($fieldSet, 'id')->shouldReturn('id');
        $this->resolveFieldLabel($fieldSet, 'name')->shouldReturn('name');
    }

    public function it_resolves_a_label_with_multiple_resolvers(
        FieldSet $fieldSet,
        FieldLabelResolverInterface $resolver,
        FieldLabelResolverInterface $resolver2
    ) {
        $this->beConstructedWith([$resolver, $resolver2]);

        $resolver->resolveFieldLabel($fieldSet, 'user_id')->willReturn('user');
        $resolver->resolveFieldLabel($fieldSet, 'id')->willReturn('id');
        $resolver->resolveFieldLabel($fieldSet, 'my_id')->willReturn(null);
        $resolver->resolveFieldLabel($fieldSet, 'name')->willReturn(null);

        $resolver2->resolveFieldLabel($fieldSet, 'my_id')->willReturn('id');
        $resolver2->resolveFieldLabel($fieldSet, 'name')->willReturn(null);

        $this->resolveFieldLabel($fieldSet, 'user_id')->shouldReturn('user');
        $this->resolveFieldLabel($fieldSet, 'my_id')->shouldReturn('id');
        $this->resolveFieldLabel($fieldSet, 'name')->shouldReturn('name');
    }
}
