<?php

namespace spec\Rollerworks\Component\Search;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

class ValuesGroupSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\\Component\\Search\\ValuesGroup');
    }

    function it_should_have_values()
    {
        $this->getFields()->shouldReturn(array());
        $this->hasField('user')->shouldReturn(false);
    }

    function it_should_allow_adding_values()
    {
        $field = new ValuesBag();
        $field2 = new ValuesBag();

        $this->addField('user', $field);
        $this->addField('date', $field2);

        $this->getFields()->shouldReturn(array('user' => $field, 'date' => $field2));
        $this->hasField('user')->shouldReturn(true);
        $this->hasField('foo')->shouldReturn(false);
    }

    function it_should_allow_removing_values()
    {
        $field = new ValuesBag();
        $field2 = new ValuesBag();

        $this->addField('user', $field);
        $this->addField('date', $field2);

        $this->removeField('user');

        $this->getFields()->shouldReturn(array('date' => $field2));
    }

    function it_should_have_subgroups()
    {
        $this->hasGroups()->shouldReturn(false);
        $this->getGroups()->shouldReturn(array());
    }

    function it_should_allow_adding_subgroups()
    {
        $group = new ValuesGroup();
        $group2 = new ValuesGroup();

        $this->addGroup($group);
        $this->addGroup($group2);

        $this->hasGroups()->shouldReturn(true);
        $this->getGroups()->shouldReturn(array($group, $group2));
    }

    function it_should_allow_removing_subgroups()
    {
        $group = new ValuesGroup();
        $group2 = new ValuesGroup();

        $this->addGroup($group);
        $this->addGroup($group2);

        $this->removeGroup(0);

        $this->hasGroups()->shouldReturn(true);
        $this->getGroups()->shouldReturn(array(1 => $group2));
    }

    function it_should_have_violations()
    {
        $this->hasViolations()->shouldReturn(false);
    }

    function it_should_allow_setting_violations()
    {
        $this->setViolations(true);
        $this->hasViolations()->shouldReturn(true);
    }
}
