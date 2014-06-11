<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Extension\Core\Type;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceListInterface;
use Rollerworks\Component\Search\FieldConfigInterface;

class ChoiceTypeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Core\Type\ChoiceType');
    }

    public function it_sets_the_value_transformer_when_configured(FieldConfigInterface $config, ChoiceListInterface $choices)
    {
        $config->addViewTransformer(Argument::type('Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToValueTransformer'))->shouldBeCalled();

        $this->buildType($config, array('label_as_value' => false, 'choice_list' => $choices));
    }

    public function it_sets_the_label_transformer_when_configured(FieldConfigInterface $config, ChoiceListInterface $choices)
    {
        $config->addViewTransformer(Argument::type('Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToLabelTransformer'))->shouldBeCalled();

        $this->buildType($config, array('label_as_value' => true, 'choice_list' => $choices));
    }
}
