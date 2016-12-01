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

use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;

final class ValuesBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_allows_adding_simple_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addSimpleValue('value');
        $valuesBag->addSimpleValue('value2');

        $this->assertTrue($valuesBag->hasSimpleValues());
        $this->assertEquals(['value', 'value2'], $valuesBag->getSimpleValues());
    }

    /**
     * @test
     */
    public function it_allows_adding_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->add($val1 = new Range(10, 20));
        $valuesBag->add($val2 = new Compare(10, '>'));

        $this->assertEquals(2, $valuesBag->count());

        $this->assertTrue($valuesBag->has(Range::class));
        $this->assertTrue($valuesBag->has(Compare::class));
        $this->assertFalse($valuesBag->has(PatternMatch::class));

        $this->assertEquals([$val1], $valuesBag->get(Range::class));
        $this->assertEquals([$val2], $valuesBag->get(Compare::class));
        $this->assertEquals([], $valuesBag->get(PatternMatch::class));
    }

    /**
     * @test
     */
    public function it_allows_removing_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->add($val1 = new Range(10, 20));
        $valuesBag->add($val2 = new Compare(10, '>'));

        $this->assertEquals(2, $valuesBag->count());

        $valuesBag->remove(Range::class, 0);
        $valuesBag->remove(Range::class, 1); // should not decrease the counter

        $this->assertEquals(1, $valuesBag->count());

        $this->assertFalse($valuesBag->has(Range::class));
        $this->assertEquals([], $valuesBag->get(Range::class));
        $this->assertEquals([$val2], $valuesBag->get(Compare::class));
    }

    /**
     * @test
     */
    public function it_allows_removing_simple_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addSimpleValue('value');
        $valuesBag->addSimpleValue('value2');

        $valuesBag->removeSimpleValue(0);

        $this->assertTrue($valuesBag->hasSimpleValues());
        $this->assertEquals([1 => 'value2'], $valuesBag->getSimpleValues());
    }

    /**
     * @test
     */
    public function it_allows_adding_excluded_simple_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedSimpleValue('value');
        $valuesBag->addExcludedSimpleValue('value2');

        $this->assertTrue($valuesBag->hasExcludedSimpleValues());
        $this->assertEquals(['value', 'value2'], $valuesBag->getExcludedSimpleValues());
    }

    /**
     * @test
     */
    public function it_allows_removing_excluded_simple_values()
    {
        $valuesBag = new ValuesBag();
        $valuesBag->addExcludedSimpleValue('value');
        $valuesBag->addExcludedSimpleValue('value2');

        $valuesBag->removeExcludedSimpleValue(0);

        $this->assertTrue($valuesBag->hasExcludedSimpleValues());
        $this->assertEquals([1 => 'value2'], $valuesBag->getExcludedSimpleValues());
    }
}
