<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
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

    function it_allows_getting_subgroups()
    {
        $group = new ValuesGroup();
        $group2 = new ValuesGroup();

        $this->addGroup($group);
        $this->addGroup($group2);

        $this->getGroup(0)->shouldReturn($group);
        $this->getGroup(1)->shouldReturn($group2);
    }

    function it_should_have_no_errors_by_default()
    {
        $this->hasErrors()->shouldReturn(false);
        $this->hasErrors(true)->shouldReturn(false);
    }

    function it_has_only_errors_when_field_has_errors()
    {
        $field = new ValuesBag();
        $field->addError(new ValuesError('value', 'whoops'));
        $this->addField('user', $field);

        $this->hasErrors()->shouldReturn(true);
        $this->hasErrors(true)->shouldReturn(true);
    }

    function it_supports_finding_errors_in_nested_groups()
    {
        $field = new ValuesBag();
        $field->addError(new ValuesError('value', 'whoops'));

        $group = new ValuesGroup();
        $group->addField('user', $field);
        $this->addGroup($group);

        $this->hasErrors()->shouldReturn(false); // current level has no errors
        $this->hasErrors(true)->shouldReturn(true); // deeper level with errors
    }
}
