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

use Rollerworks\RecordFilterBundle\Type\Time;

class TimeTest extends DateTimeTestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    function testSanitize($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Time();

        if ($expectFail) {
            $this->setExpectedException('\UnexpectedValueException', sprintf('Input value "%s" is not properly validated.', $input));
        }

        $value = $type->sanitizeString($input);
        $this->assertEquals($expected, $value->format('H:i'));
    }

    /**
     * @dataProvider getDataForSanitation
     */
    function testValidation($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Time();

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

        $type = new Time();

        $_first  = $type->sanitizeString($first);
        $_second = $type->sanitizeString($second);

        if ('==' === $comparison) {
            $this->assertTrue($type->isEquals($_first, $_second), sprintf('"%s" should equal "%s"', $first, $second));
        }
        elseif ('!=' === $comparison) {
            $this->assertFalse($type->isEquals($_first, $_second), sprintf('"%s" should not equal "%s"', $first, $second));
        }
        else {
            $this->assertTrue($type->isLower($_second, $_first), sprintf('"%s" should be lower then "%s"', $first, $second));

            // 00 is both higher and lower
            if ('</>' !== $comparison) {
                $this->assertFalse($type->isLower($_first, $_second), sprintf('"%s" should not be lower then "%s"', $first, $second));
                 $this->assertFalse($type->isHigher($_second, $_first), sprintf('"%s" should be higher then "%s"', $first, $second));
            }

            $this->assertTrue($type->isHigher($_first, $_second), sprintf('"%s" should not higher then "%s"', $first, $second));
        }
    }

    /**
     * @dataProvider getDataForGetHigherValue
     */
    function testGetHigherValue($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new Time();
        $this->assertEquals($type->sanitizeString($expected)->format('H:i:s'), $type->getHigherValue($type->sanitizeString($input))->format('H:i:s'));
    }

    public static function getDataForSanitation()
    {
        return array(
            // $locale, $input, $expected, $expectFail
            array('nl_NL', '12:00', '12:00'),
            array('nl_NL', '03:00', '03:00'),
            array('nl_NL', '15:00', '15:00'),

            array('nl_NL', '04-10-2010', '', true),
            array('nl_NL', '24:00',      '', true),

            array('en_US', '12:00 AM',    '12:00'),
            array('en_US', '12:00 PM',    '00:00'),
            array('en_US', '03:00 AM',    '03:00'),
            array('en_US', '03:00 PM',    '15:00'),

            array('en_US', '04/10/2010', '', true),
            array('en_US', '15:00 PM',   '', true),
            array('en_US', '15:00',      '', true),
            array('en_US', '03:00',      '', true),
            array('en_US', '03:00',      '', true),
        );
    }

    public static function getDataForCompare()
    {
        return array(
            // $locale, $_first (higher), $_second (lower), $comparison
            array('nl_NL', '15:15', '15:15', '=='),
            array('nl_NL', '15:15', '15:00', '!='),
            array('nl_NL', '16:15', '15:15', '!='),

            array('nl_NL', '14:15', '12:15'),
            array('nl_NL', '00:15', '12:15', '</>'),
            array('nl_NL', '03:00', '02:00'),
        );
    }

    public static function getDataForGetHigherValue()
    {
        return array(
            // $locale, $input, $expected
            array('nl_NL', '15:15', '15:16'),
            array('nl_NL', '23:59', '00:00'),
        );
    }
}
