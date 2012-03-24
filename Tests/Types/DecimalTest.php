<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Tests;

use Rollerworks\RecordFilterBundle\Formatter\Type\Decimal;

class DecimalTest extends \PHPUnit_Framework_TestCase
{
    function testSanitize()
    {
        $type = new Decimal();

        $this->assertEquals('1.0', $type->sanitizeString('1,00'));
        $this->assertEquals('1.5', $type->sanitizeString('1,50'));
        $this->assertEquals('1.05', $type->sanitizeString('1,050'));
        $this->assertEquals('1.051', $type->sanitizeString('1,051'));

        $this->assertEquals('1.0', $type->sanitizeString('1.00'));
        $this->assertEquals('1.5', $type->sanitizeString('1.50'));
        $this->assertEquals('1.05', $type->sanitizeString('1.050'));
        $this->assertEquals('1.051', $type->sanitizeString('1.051'));
    }


    function testValidation()
    {
        $type = new Decimal();

        $this->assertTrue($type->validateValue('1,00'));
        $this->assertTrue($type->validateValue('1,0'));
        $this->assertTrue($type->validateValue('1,50'));
        $this->assertTrue($type->validateValue('1,501'));

        $this->assertTrue($type->validateValue('1.00'));
        $this->assertTrue($type->validateValue('1.0'));
        $this->assertTrue($type->validateValue('1.50'));
        $this->assertTrue($type->validateValue('1.501'));

        $this->assertFalse($type->validateValue('1..0'));
        $this->assertFalse($type->validateValue('1,.0'));
        $this->assertFalse($type->validateValue('1,,0'));
        $this->assertFalse($type->validateValue('1.0.0'));
        $this->assertFalse($type->validateValue('1,0,0'));
        $this->assertFalse($type->validateValue('1,0.0'));
        $this->assertFalse($type->validateValue('1.2e3')); // Legal? Yes, accepted? No
    }


    function testHigher()
    {
        $type = new Decimal();

        $this->assertTrue($type->isHigher(1.5, 1.0));
    }


    function testNotHigher()
    {
        $type = new Decimal();

        $this->assertFalse($type->isHigher(1.0, 1.5));
    }


    function testLower()
    {
        $type = new Decimal();

        $this->assertFalse($type->isLower(1.5, 1.0));
    }


    function testNotLower()
    {
        $type = new Decimal();

        $this->assertFalse($type->isLower(1.5, 1.0));
    }


    function testEquals()
    {
        $type = new Decimal();

        $this->assertTrue($type->isEquals(1.5, 1.5));
        $this->assertTrue($type->isEquals(1.5, 1.50));
        $this->assertTrue($type->isEquals(1.566, 1.566));
    }


    function testNotEquals()
    {
        $type = new Decimal();

        $this->assertFalse($type->isEquals(1.5, 1.51));
        $this->assertFalse($type->isEquals(1.5, 1.4));
        $this->assertFalse($type->isEquals(1.566, 1.567));
    }
}