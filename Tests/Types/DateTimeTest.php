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
    function testValidation($locale, $input, $expected, $timeOptional, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new DateTime($timeOptional);

        if ($expectFail) {
            $this->assertFalse($type->validateValue($input));
        }
        else {
            $this->assertTrue($type->validateValue($input));
        }
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
        }
        elseif ('!=' === $comparison) {
            $this->assertFalse($type->isEquals($first, $second));
        }
        else {
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
            // $locale, $input, $expected
            array('nl_NL', '04-10-2010 15:15', '04-10-2010 15:16'),
            array('nl_NL', '04-10-2010 23:59', '05-10-2010 00:00'),
            array('nl_NL', '04-10-2010',       '05-10-2010', true),
        );
    }
}
