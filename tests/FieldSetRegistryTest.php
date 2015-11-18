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

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FieldSetRegistry;

final class FieldSetRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldSetRegistry
     */
    private $fieldSetRegistry;

    protected function setUp()
    {
        $this->fieldSetRegistry = new FieldSetRegistry();
    }

    /**
     * @test
     */
    public function it_returns_an_empty_array_when_none_are_registered()
    {
        $this->assertEquals([], $this->fieldSetRegistry->all());
    }

    /**
     * @test
     */
    public function it_allows_adding_FieldSets()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $fieldSet2 = new FieldSet('test2');
        $fieldSet2->lockConfig();

        $this->fieldSetRegistry->add($fieldSet);
        $this->fieldSetRegistry->add($fieldSet2);

        $this->assertEquals(
            $this->fieldSetRegistry->all(),
            [$fieldSet->getSetName() => $fieldSet, $fieldSet2->getSetName() => $fieldSet2]
        );
    }

    /**
     * @test
     */
    public function it_returns_whether_the_FieldSet_is_registered()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $this->fieldSetRegistry->add($fieldSet);

        $this->assertTrue($this->fieldSetRegistry->has('test'));
        $this->assertFalse($this->fieldSetRegistry->has('test2'));
    }

    /**
     * @test
     */
    public function it_gets_a_registered_FieldSet()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $this->fieldSetRegistry->add($fieldSet);

        $this->assertEquals($fieldSet, $this->fieldSetRegistry->get('test'));
    }

    /**
     * @test
     */
    public function it_throws_when_getting_an_unregistered_FieldSet()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $this->fieldSetRegistry->add($fieldSet);

        $this->setExpectedException(
            'Rollerworks\Component\Search\Exception\InvalidArgumentException',
            'Unable to get none registered FieldSet "test2".'
        );

        $this->fieldSetRegistry->get('test2');
    }

    /**
     * @test
     */
    public function it_disallows_overwriting_a_FieldSet()
    {
        $fieldSet = new FieldSet('test');
        $fieldSet->lockConfig();

        $this->fieldSetRegistry->add($fieldSet);

        $this->setExpectedException(
            'Rollerworks\Component\Search\Exception\InvalidArgumentException',
            'Unable to overwrite already registered FieldSet "test".'
        );

        $this->fieldSetRegistry->add($fieldSet);
    }

    /**
     * @test
     */
    public function it_disallows_registering_unlocked_FieldSet()
    {
        $fieldSet = new FieldSet('test');

        $this->setExpectedException(
            'Rollerworks\Component\Search\Exception\InvalidArgumentException',
            'Unable to register unlocked FieldSet "test".'
        );

        $this->fieldSetRegistry->add($fieldSet);
    }
}
