<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Types;

use Rollerworks\RecordFilterBundle\Type\DateTime;
use Rollerworks\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

class DateTimeTest extends DateTimeTestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    function testSanitize($locale, $input, $expected, $timeOptional, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new DateTime($timeOptional);

        if ($expectFail) {
            $this->setExpectedException('\UnexpectedValueException', sprintf('Input value "%s" is not properly validated.', $input));
        }

        $value = $type->sanitizeString($input);
        $this->assertEquals($expected, $value->format('Y-m-d H:i'));
    }

    /**
     * @dataProvider getDataForSanitation
     */
    function testDump($locale, $input, $expected, $timeOptional, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new DateTime($timeOptional);

        if ($expectFail) {
            return;
        }

        if ('29-02-2011 15:00' === $input) {
            var_dump($expectFail);
        }

        $value = $type->sanitizeString($input);
        $this->assertEquals($value->format('Y-m-d\TH:i:s'), $type->dumpValue($value));
    }

    /**
     * @dataProvider getDataForSanitation
     */
    function testValidation($locale, $input, $expected, $timeOptional, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new DateTime($timeOptional);

        if ($expectFail) {
            $this->assertFalse($type->validateValue($input));
        } else {
            $this->assertTrue($type->validateValue($input));
        }
    }

    /**
     * @dataProvider getDataForFormat
     */
    function testFormat($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new DateTime(true);

        $this->assertEquals($expected, $type->formatOutput(new DateTimeExtended($input)));
    }

    /**
     * @dataProvider getDataForCompare
     */
    function testCompares($locale, $first, $second, $comparison = null)
    {
        \Locale::setDefault($locale);

        $type = new DateTime();

        $first  = $type->sanitizeString($first);
        $second = $type->sanitizeString($second);

        if ('==' === $comparison) {
            $this->assertTrue($type->isEquals($first, $second));
        } elseif ('!=' === $comparison) {
            $this->assertFalse($type->isEquals($first, $second));
        } else {
            $this->assertTrue($type->isLower($second, $first));
            $this->assertFalse($type->isLower($first, $second));

            $this->assertFalse($type->isHigher($second, $first));
            $this->assertTrue($type->isHigher($first, $second));
        }
    }

    /**
     * @dataProvider getDataForGetHigherValue
     */
    function testGetHigherValue($locale, $input, $expected, $timeOptional = false)
    {
        \Locale::setDefault($locale);

        $type = new DateTime($timeOptional);
        $this->assertEquals($type->sanitizeString($expected)->format('Y-m-d H:i:s'), $type->getHigherValue($type->sanitizeString($input))->format('Y-m-d H:i:s'));
    }

    /**
     * @dataProvider getDataForSorting
     */
    function testSorting($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new DateTime(true);

        foreach ($input as $index => $value) {
            $input[$index] = new SingleValue($type->sanitizeString($value), $value);
        }

        uasort($input, array(&$type, 'sortValuesList'));

        foreach ($expected as $index => $value) {
            $expected[$index] = new SingleValue($type->sanitizeString($value), $value);
        }

        $this->assertEquals($expected, $input);
    }

    static public function getDataForSanitation()
    {
        return array(
            // $locale, $input, $expected, $timeOptional, $expectFail
            array('nl_NL', '04-10-2010 12:00', '2010-10-04 12:00', false),
            array('nl_NL', '04-10-2010  12:00', '2010-10-04 12:00', false),
            array('nl_NL', '04/10/2010 12:00', '2010-10-04 12:00', false),
            array('nl_NL', '04-10-2010 15:00', '2010-10-04 15:00', false),
            array('nl_NL', '04-10-2010 15:00', '2010-10-04 15:00', true),
            array('nl_NL', '04-10-2010',       '2010-10-04 00:00', true),
            array('nl_NL', '29-02-2012 15:00', '2012-02-29 15:00', false),
            array('nl_NL', '04-10-2010',       '', false, true),
            array('nl_NL', '29-02-2011 15:00', '', false, true),

            array('en_US', '04/21/2010 12:00 AM', '2010-04-21 12:00', false),
            array('en_US', '04-21-2010 12:00 AM', '2010-04-21 12:00', false),
            array('en_US', '04/21/10 12:00 AM',   '2010-04-21 12:00', false),
            array('en_US', '04/10/2010 03:00 AM', '2010-04-10 03:00', false),
            array('en_US', '04/10/2010 12:00 PM', '2010-04-10 00:00', false),
            array('en_US', '04/10/2010 03:00 PM', '2010-04-10 15:00', true),
            array('en_US', '04/10/2010',          '2010-04-10 00:00', true),
            array('en_US', '02/29/2012 03:00 PM', '2012-02-29 15:00', false),
            array('en_US', '04/10/2010',          '', false, true),
            array('en_US', '2010/10/04 15:00 PM', '', false, true),
            array('en_US', '2010/10/04 15:00',    '', false, true),
            array('en_US', '29/02/2011 03:00',    '', false, true),
            array('en_US', '29-02-2011 03:00',    '', false, true),
        );
    }

    static public function getDataForFormat()
    {
        return array(
            // $locale, $input, $expected
            array('nl_NL', '2010-10-04', '04-10-2010 00:00'),
            array('nl_NL', '2010-05-04', '04-05-2010 00:00'),
            array('nl_NL', '1990-05-04', '04-05-1990 00:00'),

            array('nl_NL', '2010-10-04 15:00', '04-10-2010 15:00'),
            array('nl_NL', '2010-05-04 23:15', '04-05-2010 23:15'),
            array('nl_NL', '1990-05-04 00:30', '04-05-1990 00:30'),

            array('en_US', '2010-04-21', '4/21/2010 12:00 AM'),
            array('en_US', '2010-10-21', '10/21/2010 12:00 AM'),
            array('en_US', '2010-04-21 15:00', '4/21/2010 3:00 PM'),
            array('en_US', '2010-10-21 15:00', '10/21/2010 3:00 PM'),

            array('uz_Arab', '2010-05-04', '۲۰۱۰-۰۵-۰۴ ۰۰:۰۰'),
            array('uz_Arab', '2010-05-04 15:00', '۲۰۱۰-۰۵-۰۴ ۱۵:۰۰'),

            // Right-to-left
            array('ar_YE', '2010-05-04', '٤‏/٥‏/٢٠١٠ ١٢:٠٠ ص'),
            array('ar_YE', '2010-05-04 15:00', '٤‏/٥‏/٢٠١٠ ٣:٠٠ م'),
        );
    }

    static public function getDataForCompare()
    {
        return array(
            // $locale, $first, $second, $comparison
            array('nl_NL', '04-10-2010 15:15', '04-10-2010 15:15', '=='),
            array('nl_NL', '04-10-2010 15:15', '04-10-2010 15:00', '!='),
            array('nl_NL', '04-10-2010 15:15', '04-10-2011 15:15', '!='),

            array('nl_NL', '04-10-2010 14:15', '04-10-2010 12:15'),
            array('nl_NL', '05-10-2010 10:15', '04-10-2010 12:15'),
            array('nl_NL', '04-10-2010 03:00', '04-10-2010 02:00'),
        );
    }

    static public function getDataForGetHigherValue()
    {
        return array(
            // $locale, $input, $expected, $timeOptional
            array('nl_NL', '04-10-2010 15:15', '04-10-2010 15:16'),
            array('nl_NL', '04-10-2010 23:59', '05-10-2010 00:00'),
            array('nl_NL', '04-10-2010 23:59:59', '05-10-2010 00:00:00'),
            array('nl_NL', '04-10-2010 23:20:59', '04-10-2010 23:21:00'),
            array('nl_NL', '04-10-2010 23:20:10', '04-10-2010 23:20:11'),

            array('nl_NL', '04-10-2010',       '05-10-2010', true),
            array('nl_NL', '04-10-2010',       '05-10-2010', true),
        );
    }

    static public function getDataForSorting()
    {
        return array(
            // $locale, $values, $expected
            array('nl_NL', array(0 => '15-04-2010', 4 => '05-03-2010', 6 => '14-05-2012', 7 => '15-04-2010 00:00'), array(4 => '05-03-2010', 0 => '15-04-2010', 7 => '15-04-2010 00:00', 6 => '14-05-2012')),
            array('nl_NL', array(1 => '16-04-2010', 3 => '15-04-2010', 4 => '15-02-2011'), array(4 => '15-02-2011', 3 => '15-04-2010', 1 => '16-04-2010')),

            array('nl_NL', array(0 => '15-04-2010 12:00', 4 => '15-04-2010 00:00', 6 => '15-04-2010 13:00'), array(4 => '15-04-2010 00:00', 0 => '15-04-2010 12:00', 6 => '15-04-2010 13:00')),
            array('nl_NL', array(1 => '15-04-2010 12:00', 3 => '15-04-2010 00:00', 4 => '14-04-2010 23:59'), array(4 => '14-04-2010 23:59', 3 => '15-04-2010 00:00', 1 => '15-04-2010 12:00')),
            array('nl_NL',
                array(1 => '15-04-2010 12:00', 3 => '15-04-2010 00:00', 4 => '14-04-2010 23:59', 5 => '16-04-2010 00:00'),
                array(4 => '14-04-2010 23:59', 3 => '15-04-2010 00:00', 1 => '15-04-2010 12:00', 5 => '16-04-2010 00:00')),
        );
    }
}
