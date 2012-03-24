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

use Rollerworks\RecordFilterBundle\Formatter\Type\Date;

class DaterTest extends \PHPUnit_Framework_TestCase
{
    function testSanitize()
    {
        $type = new Date();

        $this->assertEquals('2010-10-04', $type->sanitizeString('04.10.2010'));
        $this->assertEquals('2010-10-04', $type->sanitizeString('04.10.2010'));
        $this->assertEquals('2010-10-04', $type->sanitizeString('04-10-2010'));

        $this->assertEquals('2010-10-04', $type->sanitizeString('2010.10.04'));
        $this->assertEquals('2010-10-04', $type->sanitizeString('2010-10-04'));
    }


    function testValidation()
    {
        $type = new Date();

        $this->assertTrue($type->validateValue('04.10.2010'));
        $this->assertFalse($type->validateValue('04.13.2010'));
    }


    function testHigher()
    {
        $type = new Date();

        $this->assertTrue($type->isHigher('05.10.2010', '04.10.2010'));
    }


    function testNotHigher()
    {
        $type = new Date();

        $this->assertFalse($type->isHigher('04.10.2010', '05.10.2010'));
    }


    function testLower()
    {
        $type = new Date();

        $this->assertTrue($type->isLower('03.10.2010', '04.10.2010'));
    }


    function testNotLower()
    {
        $type = new Date();

        $this->assertFalse($type->isLower('05.10.2010', '04.10.2010'));
    }


    function testEquals()
    {
        $type = new Date();

        $this->assertTrue($type->isEquals('05.10.2010', '05.10.2010'));
        $this->assertTrue($type->isEquals('05.10.2010', '05-10-2010'));
        $this->assertTrue($type->isEquals('05-10-2010', '05.10.2010'));
    }


    function testNotEquals()
    {
        $type = new Date();

        $this->assertFalse($type->isEquals('03.10.2010', '04.10.2010'));
        $this->assertFalse($type->isEquals('03.10.2010', '04-10-2010'));
        $this->assertFalse($type->isEquals('03-10-2010', '04.10.2010'));
    }
}