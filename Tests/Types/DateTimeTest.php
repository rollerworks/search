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

use Rollerworks\RecordFilterBundle\Formatter\Type\DateTime;

class DateTimeTest extends \PHPUnit_Framework_TestCase
{
    function testSanitize()
    {
        $type = new DateTime();

        $this->assertEquals('2010-10-04 12:00:00', $type->sanitizeString('04.10.2010 12:00:00'));
        $this->assertEquals('2010-10-04 12:00', $type->sanitizeString('04-10-2010 12:00'));
        $this->assertEquals('2010-10-04 12:00', $type->sanitizeString('2010-10-04 12:00'));

        $this->assertEquals('2010-10-04 12:00', $type->sanitizeString('04.10.2010 12.00'));
        $this->assertEquals('2010-10-04 12:00', $type->sanitizeString('04-10-2010 12.00'));
        $this->assertEquals('2010-10-04 12:00', $type->sanitizeString('2010-10-04 12.00'));

        $this->assertEquals('2010-10-04 12:00:00', $type->sanitizeString('04.10.2010 12:00:00'));
        $this->assertEquals('2010-10-04 12:00:00', $type->sanitizeString('04-10-2010 12:00:00'));
        $this->assertEquals('2010-10-04 12:00:00', $type->sanitizeString('2010-10-04 12:00:00'));

        $this->assertEquals('2010-10-04 12:00:00', $type->sanitizeString('04.10.2010 12.00.00'));
        $this->assertEquals('2010-10-04 12:00:00', $type->sanitizeString('04-10-2010 12.00.00'));
        $this->assertEquals('2010-10-04 12:00:00', $type->sanitizeString('2010-10-04 12.00.00'));

        $this->assertEquals('2010-10-04 11:17', $type->sanitizeString('2010-10-04 11.17AM'));
        $this->assertEquals('2010-10-04 11:17', $type->sanitizeString('2010-10-04 11:17AM'));

        $this->assertEquals('2010-10-04 23:17', $type->sanitizeString('2010-10-04 11.17PM'));
        $this->assertEquals('2010-10-04 23:17', $type->sanitizeString('2010-10-04 11:17PM'));

        $this->assertEquals('2010-10-04 23:17', $type->sanitizeString('2010-10-04 11.17pm'));
        $this->assertEquals('2010-10-04 23:17', $type->sanitizeString('2010-10-04 11:17pm'));

        $this->assertEquals('2010-10-04 11:17:00', $type->sanitizeString('2010-10-04 11.17:00AM'));
        $this->assertEquals('2010-10-04 11:17:00', $type->sanitizeString('2010-10-04 11:17:00AM'));

        $this->assertEquals('2010-10-04 23:17:00', $type->sanitizeString('2010-10-04 11.17:00PM'));
        $this->assertEquals('2010-10-04 23:17:00', $type->sanitizeString('2010-10-04 11:17:00PM'));

        $this->assertEquals('2010-10-04 23:17:00', $type->sanitizeString('2010-10-04 11.17:00pm'));
        $this->assertEquals('2010-10-04 23:17:00', $type->sanitizeString('2010-10-04 11:17:00pm'));
    }


    function testValidation()
    {
        $type = new DateTime();

        $this->assertTrue($type->validateValue('04.10.2010 12:15'));
        $this->assertTrue($type->validateValue('04.10.2010 12.15'));
        $this->assertTrue($type->validateValue('04.10.2010 12.15:00'));
        $this->assertTrue($type->validateValue('04.10.2010 12.15.00'));

        $this->assertTrue($type->validateValue('2010-10-04 12:15'));
        $this->assertTrue($type->validateValue('2010-10-04 12.15'));
        $this->assertTrue($type->validateValue('2010-10-04 12.15:00'));
        $this->assertTrue($type->validateValue('2010-10-04 12.15.00'));

        $this->assertTrue($type->validateValue('2010.10.04 12:15'));
        $this->assertTrue($type->validateValue('2010.10.04 12.15'));
        $this->assertTrue($type->validateValue('2010.10.04 12.15:00'));
        $this->assertTrue($type->validateValue('2010.10.04 12.15.00'));

        $this->assertFalse($type->validateValue('04.10.2010')); // Time is missing
        $this->assertFalse($type->validateValue('04.13.2010 20:10'));
        $this->assertFalse($type->validateValue('04.10.2010 23'));
        $this->assertFalse($type->validateValue('04.10.2010 11:00J'));
        $this->assertFalse($type->validateValue('04.10.2010 25:00'));
    }

    function testValidationOptionalTime()
    {
        $type = new DateTime(true);

        $this->assertTrue($type->validateValue('04.10.2010 12:15'));
        $this->assertTrue($type->validateValue('04.10.2010 12.15'));
        $this->assertTrue($type->validateValue('04.10.2010 12.15:00'));
        $this->assertTrue($type->validateValue('04.10.2010 12.15.00'));

        $this->assertTrue($type->validateValue('2010-10-04 12:15'));
        $this->assertTrue($type->validateValue('2010-10-04 12.15'));
        $this->assertTrue($type->validateValue('2010-10-04 12.15:00'));
        $this->assertTrue($type->validateValue('2010-10-04 12.15.00'));

        $this->assertTrue($type->validateValue('2010.10.04 12:15'));
        $this->assertTrue($type->validateValue('2010.10.04 12.15'));
        $this->assertTrue($type->validateValue('2010.10.04 12.15:00'));
        $this->assertTrue($type->validateValue('2010.10.04 12.15.00'));
        $this->assertTrue($type->validateValue('04.10.2010'));

        $this->assertFalse($type->validateValue('04.13.2010 20:10'));
        $this->assertFalse($type->validateValue('04.10.2010 23'));
        $this->assertFalse($type->validateValue('04.10.2010 11:00J'));
        $this->assertFalse($type->validateValue('04.10.2010 25:00'));
    }


    function testHigher()
    {
        $type = new DateTime();

        $this->assertTrue($type->isHigher('2010-10-04 15:15', '2010-10-04 12:15'));
        $this->assertTrue($type->isHigher('2010-10-05 12:15', '2010-10-04 12:15'));
        $this->assertTrue($type->isHigher('2010-10-04 12:15:01', '2010-10-04 12:15'));

        $this->assertTrue($type->isHigher('2010-10-04 14:15:01+02:00', '2010-10-04 12:15:01+02:00'));

        $this->assertTrue($type->isHigher('2010-10-04 03:00:01+02:00', '2010-10-04 03:00:01+03:00'));
        $this->assertTrue($type->isHigher('2010-10-04 03:00:01+02:00', '2010-10-04 03:00:01+04:00'));
    }


    function testNotHigher()
    {
        $type = new DateTime();

        $this->assertFalse($type->isHigher('2010-10-04 12:15', '2010-10-04 15:25'));
        $this->assertFalse($type->isHigher('2010-10-04 12:15', '2010-10-05 12:25'));
        $this->assertFalse($type->isHigher('2010-10-04 12:15', '2010-10-04 12:15:05'));

        $this->assertFalse($type->isHigher('2010-10-04 12:15:01+02:00', '2010-10-04 14:15:01+02:00'));

        $this->assertFalse($type->isHigher('2010-10-04 03:00:01+02:00', '2010-10-04 04:00:04+03:00'));
        $this->assertFalse($type->isHigher('2010-10-04 04:00:01+04:00', '2010-10-04 03:00:03+02:00'));
    }


    function testLower()
    {
        $type = new DateTime();

        $this->assertTrue($type->isLower('2010-10-04 12:15', '2010-10-04 15:25'));
        $this->assertTrue($type->isLower('2010-10-04 12:15', '2010-10-05 12:25'));
        $this->assertTrue($type->isLower('2010-10-04 12:15', '2010-10-04 12:15:01'));

        $this->assertTrue($type->isLower('2010-10-04 12:15:01+02:00', '2010-10-04 14:15:01+02:00'));
        $this->assertTrue($type->isLower('2010-10-04 11:15:01+00:00', '2010-10-04 13:15:01+01:00'));

        $this->assertTrue($type->isLower('2010-10-04 03:00:01+02:00', '2010-10-04 04:00:04+03:00'));
        $this->assertTrue($type->isLower('2010-10-04 03:00:01+04:00', '2010-10-04 03:00:01+02:00'));
    }


    function testNotLower()
    {
        $type = new DateTime();

        $this->assertFalse($type->isLower('2010-10-04 15:15', '2010-10-04 12:15'));
        $this->assertFalse($type->isLower('2010-10-05 12:15', '2010-10-04 12:15'));
        $this->assertFalse($type->isLower('2010-10-04 12:15:01', '2010-10-04 12:15'));

        $this->assertFalse($type->isLower('2010-10-04 14:15:01+02:00', '2010-10-04 12:15:01+02:00'));
        $this->assertFalse($type->isLower('2010-10-04 03:00:01+02:00', '2010-10-04 03:00:01+03:00'));
        $this->assertFalse($type->isLower('2010-10-04 03:00:01+02:00', '2010-10-04 03:00:01+04:00'));
    }


    function testEquals()
    {
        $type = new DateTime();

        $this->assertTrue($type->isEquals('2010-10-04 12:15', '2010-10-04 12:15'));
        $this->assertTrue($type->isEquals('2010-10-04 12:15', '2010-10-04 12:15:00'));

        $this->assertTrue($type->isEquals('2010-10-04 12:15:01+02:00', '2010-10-04 13:15:01+03:00'));

        $this->assertTrue($type->isEquals('2010-10-04 02:00:01+02:00', '2010-10-04 03:00:01+03:00'));
        $this->assertTrue($type->isEquals('2010-10-04 03:00:01+00:00', '2010-10-04 04:00:01+01:00'));
    }


    function testNotEquals()
    {
        $type = new DateTime();

        $this->assertFalse($type->isEquals('2010-10-04 12:15', '2010-10-04 15:15'));
        $this->assertFalse($type->isEquals('2010-10-04 12:15', '2010-10-05 12:15'));
        $this->assertFalse($type->isEquals('2010-10-04 12:15:01', '2010-10-04 12:15'));

        $this->assertFalse($type->isEquals('2010-10-04 12:15:01+02:00', '2010-10-04 14:15:01+02:00'));
        $this->assertFalse($type->isEquals('2010-10-04 11:15:01+00:00', '2010-10-04 13:15:01+01:00'));

        $this->assertFalse($type->isEquals('2010-10-04 03:00:01+02:00', '2010-10-04 03:00:01+03:00'));
        $this->assertFalse($type->isEquals('2010-10-04 03:00:01+04:00', '2010-10-04 03:00:01+02:00'));
    }

    function testHigherValue()
    {
        $type = new DateTime();

        $this->assertEquals('2010-10-04 15:16:00', $type->getHigherValue('2010-10-04 15:15'));
        $this->assertEquals('2010-10-04 15:15:01', $type->getHigherValue('2010-10-04 15:15:00'));

        $this->assertEquals('2010-10-05 00:00:00', $type->getHigherValue('2010-10-04 23:59'));
        $this->assertEquals('2010-10-04 23:59:01', $type->getHigherValue('2010-10-04 23:59:00'));
        $this->assertEquals('2010-10-05 00:00:00', $type->getHigherValue('2010-10-04 23:59:59'));

        $this->assertEquals('2011-01-01 00:00:00', $type->getHigherValue('2010-12-31 23:59:59'));

        $this->assertEquals('2012-02-29 00:00:00', $type->getHigherValue('2012-02-28 23:59'));
        $this->assertEquals('2011-03-01 00:00:00', $type->getHigherValue('2011-02-28 23:59:59'));
    }
}