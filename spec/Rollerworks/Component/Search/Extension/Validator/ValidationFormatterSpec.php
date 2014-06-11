<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Extension\Validator;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\ValuesGroup;
use Symfony\Component\Validator\ValidatorInterface;

class ValidationFormatterSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Validator\ValidationFormatter');
        $this->shouldImplement('Rollerworks\Component\Search\FormatterInterface');
    }

    public function let(ValidatorInterface $validator, ViolationMapperInterface $violationMapper)
    {
        $this->beConstructedWith($validator, $violationMapper);
    }

    public function it_should_not_map_violations_when_there_empty(ValidatorInterface $validator, ViolationMapperInterface $violationMapper, SearchConditionInterface $condition)
    {
        $condition->getValuesGroup()->willReturn(new ValuesGroup());
        $validator->validateValue(Argument::type('Rollerworks\Component\Search\SearchConditionInterface'), Argument::type('Rollerworks\Component\Search\Extension\Validator\Constraints\ValuesGroup'))->shouldBeCalled();
        $violationMapper->mapViolation(null, null)->shouldNotBeCalled();

        $this->beConstructedWith($validator, $violationMapper);
        $this->format($condition);
    }

    public function it_should_do_nothing_when_ValuesGroup_has_errors(SearchConditionInterface $condition, ValuesGroup $valuesGroup)
    {
        $valuesGroup->hasErrors()->willReturn(true);
        $condition->getValuesGroup()->willReturn($valuesGroup);

        $this->format($condition);
    }
}
