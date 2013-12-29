<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

class ValuesGroupSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\\Component\\Search\\ValuesGroup');
    }

    public function it_should_have_values()
    {
        $this->getFields()->shouldReturn(array());
        $this->hasField('user')->shouldReturn(false);
    }

    public function it_should_allow_adding_values()
    {
        $field = new ValuesBag();
        $field2 = new ValuesBag();

        $this->addField('user', $field);
        $this->addField('date', $field2);

        $this->getFields()->shouldReturn(array('user' => $field, 'date' => $field2));
        $this->hasField('user')->shouldReturn(true);
        $this->hasField('foo')->shouldReturn(false);
    }

    public function it_should_allow_removing_values()
    {
        $field = new ValuesBag();
        $field2 = new ValuesBag();

        $this->addField('user', $field);
        $this->addField('date', $field2);

        $this->removeField('user');

        $this->getFields()->shouldReturn(array('date' => $field2));
    }

    public function it_should_have_subgroups()
    {
        $this->hasGroups()->shouldReturn(false);
        $this->getGroups()->shouldReturn(array());
    }

    public function it_should_allow_adding_subgroups()
    {
        $group = new ValuesGroup();
        $group2 = new ValuesGroup();

        $this->addGroup($group);
        $this->addGroup($group2);

        $this->hasGroups()->shouldReturn(true);
        $this->getGroups()->shouldReturn(array($group, $group2));
    }

    public function it_should_allow_removing_subgroups()
    {
        $group = new ValuesGroup();
        $group2 = new ValuesGroup();

        $this->addGroup($group);
        $this->addGroup($group2);

        $this->removeGroup(0);

        $this->hasGroups()->shouldReturn(true);
        $this->getGroups()->shouldReturn(array(1 => $group2));
    }

    public function it_should_have_no_errors_by_default()
    {
        $this->hasErrors()->shouldReturn(false);
    }

    public function it_should_allow_setting_violations()
    {
        $this->setHasErrors(true);
        $this->hasErrors()->shouldReturn(true);
    }
}
