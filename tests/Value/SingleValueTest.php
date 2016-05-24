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

use Rollerworks\Component\Search\Value\SingleValue;

class SingleValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SingleValue
     */
    private $value;

    protected function setUp()
    {
        $this->value = new SingleValue(10, '10');
    }

    /**
     * @test
     */
    public function it_has_a_value()
    {
        $this->assertEquals(10, $this->value->getValue());
    }

    /**
     * @test
     */
    public function it_has_a_viewValue()
    {
        $this->assertEquals('10', $this->value->getViewValue());
    }

    /**
     * @test
     */
    public function it_allows_an_object_as_value()
    {
        $value = new \DateTime();
        $this->value = new SingleValue($value, '2014-12-24');

        $this->assertEquals($value, $this->value->getValue());
        $this->assertEquals('2014-12-24', $this->value->getViewValue());
    }
}
