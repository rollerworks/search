<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests;

use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Rollerworks\Component\Search\ValuesError;

final class ValuesGroupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_contains_no_values_when_initialized()
    {
        $valuesGroup = new ValuesGroup();

        $this->assertEquals([], $valuesGroup->getFields());
        $this->assertEquals(false, $valuesGroup->hasField('user'));
    }

    /**
     * @test
     */
    public function it_should_allow_adding_values()
    {
        $valuesGroup = new ValuesGroup();

        $field = new ValuesBag();
        $field2 = new ValuesBag();

        $valuesGroup->addField('user', $field);
        $valuesGroup->addField('date', $field2);

        $this->assertEquals(['user' => $field, 'date' => $field2], $valuesGroup->getFields());
        $this->assertEquals(true, $valuesGroup->hasField('user'));
        $this->assertEquals(false, $valuesGroup->hasField('foo'));
    }

    /**
     * @test
     */
    public function it_should_allow_removing_values()
    {
        $valuesGroup = new ValuesGroup();

        $field = new ValuesBag();
        $field2 = new ValuesBag();

        $valuesGroup->addField('user', $field);
        $valuesGroup->addField('date', $field2);

        $valuesGroup->removeField('user');

        $this->assertEquals(['date' => $field2], $valuesGroup->getFields());
    }

    /**
     * @test
     */
    public function it_should_have_subgroups()
    {
        $valuesGroup = new ValuesGroup();

        $this->assertEquals(false, $valuesGroup->hasGroups());
        $this->assertEquals([], $valuesGroup->getGroups());
    }

    /**
     * @test
     */
    public function it_should_allow_adding_subgroups()
    {
        $valuesGroup = new ValuesGroup();

        $group = new ValuesGroup();
        $group2 = new ValuesGroup();

        $valuesGroup->addGroup($group);
        $valuesGroup->addGroup($group2);

        $this->assertEquals(true, $valuesGroup->hasGroups());
        $this->assertEquals([$group, $group2], $valuesGroup->getGroups());
    }

    /**
     * @test
     */
    public function it_should_allow_removing_subgroups()
    {
        $valuesGroup = new ValuesGroup();

        $group = new ValuesGroup();
        $group2 = new ValuesGroup();

        $valuesGroup->addGroup($group);
        $valuesGroup->addGroup($group2);

        $valuesGroup->removeGroup(0);

        $this->assertEquals(true, $valuesGroup->hasGroups());
        $this->assertEquals([1 => $group2], $valuesGroup->getGroups());
    }

    /**
     * @test
     */
    public function it_allows_getting_subgroups()
    {
        $valuesGroup = new ValuesGroup();

        $group = new ValuesGroup();
        $group2 = new ValuesGroup();

        $valuesGroup->addGroup($group);
        $valuesGroup->addGroup($group2);

        $this->assertEquals($group, $valuesGroup->getGroup(0));
        $this->assertEquals($group2, $valuesGroup->getGroup(1));
    }

    /**
     * @test
     */
    public function it_should_have_no_errors_by_default()
    {
        $valuesGroup = new ValuesGroup();

        $this->assertEquals(false, $valuesGroup->hasErrors());
        $this->assertEquals(false, $valuesGroup->hasErrors(true));
    }

    /**
     * @test
     */
    public function it_has_only_errors_when_field_has_errors()
    {
        $valuesGroup = new ValuesGroup();

        $field = new ValuesBag();
        $field->addError(new ValuesError('value', 'whoops'));
        $valuesGroup->addField('user', $field);

        $this->assertEquals(true, $valuesGroup->hasErrors());
        $this->assertEquals(true, $valuesGroup->hasErrors(true));
    }

    /**
     * @test
     */
    public function it_supports_finding_errors_in_nested_groups()
    {
        $valuesGroup = new ValuesGroup();

        $field = new ValuesBag();
        $field->addError(new ValuesError('value', 'whoops'));

        $group = new ValuesGroup();
        $group->addField('user', $field);
        $valuesGroup->addGroup($group);

        $this->assertEquals(false, $valuesGroup->hasErrors()); // current level has no errors
        $this->assertEquals(true, $valuesGroup->hasErrors(true)); // deeper level with errors
    }
}
