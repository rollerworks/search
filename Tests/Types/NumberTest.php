<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests;

use Rollerworks\RecordFilterBundle\Type\Number;

class NumberTest extends \PHPUnit_Framework_TestCase
{
    function testSanitize()
    {
        $type = new Number();

        $this->assertEquals('-1', $type->sanitizeString('-1'));
        $this->assertEquals('1', $type->sanitizeString('+1'));
    }

    function testValidation()
    {
        $type = new Number();

        $this->assertTrue($type->validateValue('1'));
        $this->assertTrue($type->validateValue('+1'));
        $this->assertTrue($type->validateValue('-1'));

        $this->assertFalse($type->validateValue('*1'));
        $this->assertFalse($type->validateValue('1/1'));
        $this->assertFalse($type->validateValue('1.0'));
    }

    function testHigher()
    {
        $type = new Number();

        $this->assertTrue($type->isHigher('2', '1'));
    }

    function testNotHigher()
    {
        $type = new Number();

        $this->assertFalse($type->isHigher('1', '2'));
    }

    function testLower()
    {
        $type = new Number();

        $this->assertTrue($type->isLower('1', '2'));
    }

    function testNotLower()
    {
        $type = new Number();

        $this->assertFalse($type->isLower('2', '1'));
    }

    function testEquals()
    {
        $type = new Number();

        $this->assertTrue($type->isEquals('1', '1'));
        $this->assertTrue($type->isEquals('-1', '-1'));
    }

    function testNotEquals()
    {
        $type = new Number();

        $this->assertFalse($type->isEquals('1', '3'));
        $this->assertFalse($type->isEquals('-1', '1'));
        $this->assertFalse($type->isEquals('-1', '-2'));
    }
}
