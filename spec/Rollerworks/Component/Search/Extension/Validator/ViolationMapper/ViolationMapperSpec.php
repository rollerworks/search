<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Extension\Validator\ViolationMapper;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;
use Symfony\Component\Validator\ConstraintViolation;

class ViolationMapperSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Validator\ViolationMapper\ViolationMapper');
        $this->shouldImplement('Rollerworks\Component\Search\Extension\Validator\ViolationMapper\ViolationMapperInterface');
    }

    public function it_maps_violations_on_group_to_an_field_error(ValuesGroup $group, ValuesBag $values)
    {
        $group->getField('id')->willReturn($values->getWrappedObject());
        $group->setHasErrors(true)->shouldBeCalled();

        $values->addError(Argument::exact(new ValuesError('ranges[1].lower', 'This value should be 5 or more.', 'This value should be {{ limit }} or more.', array('{{ limit }}' => 10, '{{ value }}' => 5), null)))->shouldBeCalled();
        $this->mapViolation(new ConstraintViolation('This value should be 5 or more.', 'This value should be {{ limit }} or more.', array('{{ limit }}' => 10, '{{ value }}' => 5), 'ValuesBag', 'fields[id].ranges[1].lower', 5), $group);

        $values->addError(Argument::exact(new ValuesError('ranges[1].lower', 'This value should be 5 or more.', 'This value should be {{ limit }} or more.', array('{{ limit }}' => 10, '{{ value }}' => 5), null)))->shouldBeCalled();
        $this->mapViolation(new ConstraintViolation('This value should be 5 or more.', 'This value should be {{ limit }} or more.', array('{{ limit }}' => 10, '{{ value }}' => 5), 'ValuesBag', 'fields[id].ranges[1].lower', 5), $group);

        $values->addError(Argument::exact(new ValuesError('ranges[1]', 'Lower range-value 5 should be lower then upper range-value 20.', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 5, '{{ upper }}' => 20), null)))->shouldBeCalled();
        $this->mapViolation(new ConstraintViolation('Lower range-value 5 should be lower then upper range-value 20.', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 5, '{{ upper }}' => 20), 'ValuesBag', 'fields[id].ranges[1]', new Range(5, 20)), $group);
    }

    public function it_maps_violations_on_nested_group_to_an_field_error(ValuesGroup $group, ValuesGroup $group2, ValuesGroup $group3, ValuesBag $values, ValuesBag $values2)
    {
        $group->getGroups()->willReturn(array($group2->getWrappedObject()));
        $group->getGroup(0)->willReturn($group2->getWrappedObject());
        $group->setHasErrors(true)->shouldBeCalled();

        $group2->getGroups()->willReturn(array(1 => $group3->getWrappedObject()));
        $group2->getGroup(1)->willReturn($group3->getWrappedObject());
        $group2->setHasErrors(true)->shouldBeCalled();
        $group2->getField('id')->willReturn($values->getWrappedObject());

        $group3->setHasErrors(true)->shouldBeCalled();
        $group3->getField('id')->willReturn($values2->getWrappedObject());

        $this->mapViolation(new ConstraintViolation('This value should be 5 or more.', 'This value should be {{ limit }} or more.', array('{{ limit }}' => 10, '{{ value }}' => 5), 'ValuesBag', 'groups[0].fields[id].singleValue[1].value', 5), $group);
        $values->addError(Argument::exact(new ValuesError('singleValue[1].value', 'This value should be 5 or more.', 'This value should be {{ limit }} or more.', array('{{ limit }}' => 10, '{{ value }}' => 5), null)))->shouldBeCalled();

        $values2->addError(Argument::exact(new ValuesError('singleValue[1].value', 'This value should be 5 or more.', 'This value should be {{ limit }} or more.', array('{{ limit }}' => 10, '{{ value }}' => 5), null)))->shouldBeCalled();
        $this->mapViolation(new ConstraintViolation('This value should be 5 or more.', 'This value should be {{ limit }} or more.', array('{{ limit }}' => 10, '{{ value }}' => 5), 'ValuesBag', 'groups[0].groups[1].fields[id].singleValue[1].value', 5), $group);

        $values->addError(Argument::exact(new ValuesError('ranges[1]', 'Lower range-value 5 should be lower then upper range-value 20.', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 5, '{{ upper }}' => 20), null)))->shouldBeCalled();
        $this->mapViolation(new ConstraintViolation('Lower range-value 5 should be lower then upper range-value 20.', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 5, '{{ upper }}' => 20), 'ValuesBag', 'groups[0].fields[id].ranges[1]', new Range(5, 20)), $group);

        $values2->addError(Argument::exact(new ValuesError('ranges[1]', 'Lower range-value 5 should be lower then upper range-value 20.', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 5, '{{ upper }}' => 20), null)))->shouldBeCalled();
        $this->mapViolation(new ConstraintViolation('Lower range-value 5 should be lower then upper range-value 20.', 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.', array('{{ lower }}' => 5, '{{ upper }}' => 20), 'ValuesBag', 'groups[0].groups[1].fields[id].ranges[1]', new Range(5, 20)), $group);
    }
}
