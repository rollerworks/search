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
use Rollerworks\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

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
    function testDump($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Time();

        if ($expectFail) {
            return;
        }

        $value = $type->sanitizeString($input);
        $this->assertEquals($value->format('H:i:s'), $type->dumpValue($value));
    }

    /**
     * @dataProvider getDataForSanitation
     */
    function testValidation($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Time();

        if ($expectFail) {
            $this->assertFalse($type->validateValue($input), sprintf('Assert "%s" not to be valid with locale "%s".', $input, $locale));
        } else {
            $this->assertTrue($type->validateValue($input), sprintf('Assert "%s" to be valid with locale "%s".', $input, $locale));
        }
    }

    /**
     * @dataProvider getDataForFormat
     */
    function testFormat($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new Time();

        $this->assertEquals($expected, $type->formatOutput(new DateTimeExtended($input, true)));
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
        } elseif ('!=' === $comparison) {
            $this->assertFalse($type->isEquals($_first, $_second), sprintf('"%s" should not equal "%s"', $first, $second));
        } else {
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

    /**
     * @dataProvider getDataForSorting
     */
    function testSorting($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new Time();

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

            array('uz_Arab', '۱۳:۰۰', '13:00'),

            // Right-to-left
            array('ar_YE', '١:٠٠ م', '13:00'),
        );
    }

    static public function getDataForFormat()
    {
        return array(
            // $locale, $input, $expected
            array('nl_NL', '03:15', '03:15'),
            array('nl_NL', '23:59', '23:59'),

            array('en_US', '23:59', '11:59 PM'),
            array('en_US', '03:40', '3:40 AM'),

            array('uz_Arab', '13:00', '۱۳:۰۰'),

            // Right-to-left
            array('ar_YE', '13:00', '١:٠٠ م'),
        );
    }

    static public function getDataForCompare()
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

    static public function getDataForGetHigherValue()
    {
        return array(
            // $locale, $input, $expected
            array('nl_NL', '15:15', '15:16'),
            array('nl_NL', '23:59', '00:00'),
            array('nl_NL', '23:59:59', '00:00:00'),
            array('nl_NL', '23:20:00', '23:20:01'),
            array('nl_NL', '23:20:10', '23:20:11'),
        );
    }

    static public function getDataForSorting()
    {
        return array(
            // $locale, $values, $expected
            array('nl_NL', array(0 => '15:15', 4 => '15:00', 6 => '16:00'), array(4 => '15:00', 0 => '15:15', 6 => '16:00')),
            array('nl_NL', array(1 => '16:00', 3 => '15:15', 4 => '15:00'), array(4 => '15:00', 3 => '15:15', 1 => '16:00')),
            array('nl_NL', array(0 => '16:00', 1 => '15:15', 2 => '15:00', 3 => '00:10', 5 => '00:00'), array(5 => '00:00', 3 => '00:10', 2 => '15:00', 1 => '15:15', 0 => '16:00')),
        );
    }
}
