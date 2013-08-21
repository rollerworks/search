<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search\Extension\Validator;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Rollerworks\Component\Search\Extension\Validator\Constraints\ValuesGroup as ValuesGroupConstraint;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ValidatorInterface;

class ValidationFormatterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Validator\ValidationFormatter');
        $this->shouldImplement('Rollerworks\Component\Search\FormatterInterface');
    }

    function let(ValidatorInterface $validator, ViolationMapperInterface $violationMapper)
    {
        $this->beConstructedWith($validator, $violationMapper);
    }

    function it_should_not_map_violations_when_there_empty(ValidatorInterface $validator, ViolationMapperInterface $violationMapper, SearchConditionInterface $condition)
    {
        $condition->getValuesGroup()->willReturn(new ValuesGroup());
        $validator->validateValue(Argument::type('Rollerworks\Component\Search\SearchConditionInterface'), Argument::type('Rollerworks\Component\Search\Extension\Validator\Constraints\ValuesGroup'))->shouldBeCalled();
        $violationMapper->mapViolation(null, null)->shouldNotBeCalled();

        $this->beConstructedWith($validator, $violationMapper);
        $this->format($condition);
    }

    function it_should_do_nothing_when_ValuesGroup_has_errors(SearchConditionInterface $condition, ValuesGroup $valuesGroup)
    {
        $valuesGroup->hasErrors()->willReturn(true);
        $condition->getValuesGroup()->willReturn($valuesGroup);

        $this->format($condition);
    }
}
