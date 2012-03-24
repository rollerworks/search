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

use Rollerworks\RecordFilterBundle\Formatter\Type\Number;

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