<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Value;

use Rollerworks\Component\Search\Value\Range;

class RangeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Range
     */
    private $value;

    public function setUp()
    {
        $this->value = new Range(10, 20);
    }

    /** @test */
    public function it_has_a_lower_value()
    {
        $this->assertEquals(10, $this->value->getLower());
    }

    /** @test */
    public function it_has_an_upper_value()
    {
        $this->assertEquals(20, $this->value->getUpper());
    }

    /** @test */
    public function it_has_a_lower_viewValue()
    {
        $this->assertEquals('10', $this->value->getViewLower());
    }

    /** @test */
    public function it_has_an_upper_viewValue()
    {
        $this->assertEquals('20', $this->value->getViewUpper());
    }

    /** @test */
    public function its_lower_value_is_inclusive_by_default()
    {
        $this->assertEquals(true, $this->value->isLowerInclusive());
    }

    /** @test */
    public function its_upper_value_should_be_inclusive_by_default()
    {
        $this->assertEquals(true, $this->value->isUpperInclusive());
    }

    /** @test */
    public function it_allows_exclusive_lower_value()
    {
        $this->value = new Range(10, 20, false);
        $this->assertEquals(false, $this->value->isLowerInclusive());
    }

    /** @test */
    public function it_allows_exclusive_upper_value()
    {
        $this->value = new Range(10, 20, true, false);
        $this->assertEquals(false, $this->value->isUpperInclusive());
    }
}
