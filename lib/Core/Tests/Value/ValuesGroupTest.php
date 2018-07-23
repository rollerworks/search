<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Value;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @internal
 */
final class ValuesGroupTest extends TestCase
{
    /**
     * @test
     */
    public function it_contains_no_values_when_initialized()
    {
        $valuesGroup = new ValuesGroup();

        self::assertEquals([], $valuesGroup->getFields());
        self::assertFalse($valuesGroup->hasField('user'));
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

        self::assertEquals(['user' => $field, 'date' => $field2], $valuesGroup->getFields());
        self::assertTrue($valuesGroup->hasField('user'));
        self::assertFalse($valuesGroup->hasField('foo'));
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

        self::assertEquals(['date' => $field2], $valuesGroup->getFields());
    }

    /**
     * @test
     */
    public function it_should_have_subgroups()
    {
        $valuesGroup = new ValuesGroup();

        self::assertFalse($valuesGroup->hasGroups());
        self::assertEquals([], $valuesGroup->getGroups());
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

        self::assertTrue($valuesGroup->hasGroups());
        self::assertEquals([$group, $group2], $valuesGroup->getGroups());
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

        self::assertTrue($valuesGroup->hasGroups());
        self::assertEquals([1 => $group2], $valuesGroup->getGroups());
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

        self::assertEquals($group, $valuesGroup->getGroup(0));
        self::assertEquals($group2, $valuesGroup->getGroup(1));
    }
}
