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

use Rollerworks\Component\Search\Value\Compare;

class CompareTest extends \PHPUnit_Framework_TestCase
{
    /** @var Compare */
    private $value;

    public function setUp()
    {
        $this->value = new Compare(10, '>', '10');
    }

    /** @test */
    public function it_has_a_value()
    {
        $this->assertEquals(10, $this->value->getValue());
    }

    /** @test */
    public function it_has_an_operator()
    {
        $this->assertEquals('>', $this->value->getOperator());
    }

    /** @test */
    public function it_allows_an_object_as_value()
    {
        $value = new \DateTime();

        $this->value = new Compare($value, '<', '2014-12-24');

        $this->assertEquals($value, $this->value->getValue());
        $this->assertEquals('<', $this->value->getOperator());
    }
}
