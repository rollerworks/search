<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Types;

use Rollerworks\Bundle\RecordFilterBundle\Type\DateTime;
use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use \Rollerworks\Bundle\RecordFilterBundle\MessageBag;

class DateTimeTest extends DateTimeTestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    public function testSanitize($locale, $input, $expected, $options = array(), $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new DateTime($options);

        if ($expectFail) {
            $this->setExpectedException('\UnexpectedValueException', sprintf('Input value "%s" is not properly validated.', $input));
        }

        $value = $type->sanitizeString($input);
        $this->assertEquals($expected, $value->format('Y-m-d H:i'));
    }

    /**
     * @dataProvider getDataForSanitation
     */
    public function testDump($locale, $input, $expected, $options = array(), $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new DateTime($options);

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
    public function testValidation($locale, $input, $expected, $options = array(), $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new DateTime($options);

        if ($expectFail) {
            $this->assertFalse($type->validateValue($input));
        } else {
            $this->assertTrue($type->validateValue($input));
        }
    }

    /**
     * @dataProvider getDataForAdvancedValidation
     */
    public function testValidationAdvanced($input, $options = array(), $expectMessage = false)
    {
        $type = new DateTime($options);

        if (is_array($expectMessage)) {
            $messageBag = new MessageBag($this->translator);

            $this->assertFalse($type->validateValue($input, $message, $messageBag), sprintf('Assert "%s" is invalid', $input));
            $this->assertEquals($expectMessage, $messageBag->get('error'), sprintf('Assert "%s" is invalid and messages are equal.', $input));
        } else {
            $this->assertTrue($type->validateValue($input), sprintf('Assert "%s" is valid', $input));
        }
    }

    /**
     * @dataProvider getDataForFormat
     */
    public function testFormat($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new DateTime(array('time_optional' => true));

        $this->assertEquals($expected, $type->formatOutput(new DateTimeExtended($input, false !== strpos($input, ':'))));
    }

    /**
     * @dataProvider getDataForCompare
     */
    public function testCompares($locale, $first, $second, $comparison = null)
    {
        \Locale::setDefault($locale);

        $type = new DateTime();

        $first  = $type->sanitizeString($first);
        $second = $type->sanitizeString($second);

        if ('==' === $comparison) {
            $this->assertTrue($type->isEqual($first, $second));
        } elseif ('!=' === $comparison) {
            $this->assertFalse($type->isEqual($first, $second));
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
    public function testGetHigherValue($locale, $input, $expected, $options = array())
    {
        \Locale::setDefault($locale);

        $type = new DateTime($options);
        $this->assertEquals($type->sanitizeString($expected)->format('Y-m-d H:i:s'), $type->getHigherValue($type->sanitizeString($input))->format('Y-m-d H:i:s'));
    }

    /**
     * @dataProvider getDataForSorting
     */
    public function testSorting($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new DateTime(array('time_optional' => true));

        foreach ($input as $index => $value) {
            $input[$index] = new SingleValue($type->sanitizeString($value), $value);
        }

        uasort($input, array(&$type, 'sortValuesList'));

        foreach ($expected as $index => $value) {
            $expected[$index] = new SingleValue($type->sanitizeString($value), $value);
        }

        $this->assertEquals($expected, $input);
    }

    public static function getDataForSanitation()
    {
        return array(
            // $locale, $input, $expected, $options, $expectFail
            array('nl_NL', '04-10-2010 12:00', '2010-10-04 12:00'),
            array('nl_NL', '04-10-2010  12:00', '2010-10-04 12:00'),
            array('nl_NL', '04/10/2010 12:00', '2010-10-04 12:00'),
            array('nl_NL', '04-10-2010 15:00', '2010-10-04 15:00'),
            array('nl_NL', '04-10-2010 15:00', '2010-10-04 15:00', array('time_optional' => true)),
            array('nl_NL', '04-10-2010',       '2010-10-04 00:00', array('time_optional' => true)),
            array('nl_NL', '29-02-2012 15:00', '2012-02-29 15:00'),
            array('nl_NL', '04-10-2010',       '', array('time_optional' => false), true),
            array('nl_NL', '29-02-2011 15:00', '', array('time_optional' => false), true),

            array('en_US', '04/21/2010 12:00 AM', '2010-04-21 12:00'),
            array('en_US', '04-21-2010 12:00 AM', '2010-04-21 12:00'),
            array('en_US', '04/21/10 12:00 AM',   '2010-04-21 12:00'),
            array('en_US', '04/10/2010 03:00 AM', '2010-04-10 03:00'),
            array('en_US', '04/10/2010 12:00 PM', '2010-04-10 00:00'),
            array('en_US', '04/10/2010 03:00 PM', '2010-04-10 15:00', array('time_optional' => true)),
            array('en_US', '04/10/2010',          '2010-04-10 00:00', array('time_optional' => true)),
            array('en_US', '02/29/2012 03:00 PM', '2012-02-29 15:00'),

            array('en_US', '04/10/2010',          '', array(), true),
            array('en_US', '2010/10/04 15:00 PM', '', array(), true),
            array('en_US', '2010/10/04 15:00',    '', array(), true),
            array('en_US', '29/02/2011 03:00',    '', array(), true),
            array('en_US', '29-02-2011 03:00',    '', array(), true),
        );
    }

    public static function getDataForAdvancedValidation()
    {
        return array(
            // $input, $options, $expectMessage
            array('2010-04-10 15:00', array('max' => '2010-05-10 15:00')),
            array('2010-04-10 15:00', array('max' => '2010-04-10 15:00')),
            array('2010-04-10 15:00', array('min' => '2010-03-10 15:00')),

            array('2010-04-10 15:00', array('max' => '2010-05-10 15:00')),
            array('2010-04-10 15:00', array('max' => '2010-04-10 15:01')),

            array('2010-04-10', array('max' => '2010-05-10', 'time_optional' => true)),
            array('2010-04-10', array('max' => '2010-04-10', 'time_optional' => true)),
            array('2010-05-10 15:01:00', array('max' => '2010-05-10 15:01')),

            array('2010-04-10 15:00', array('min' => '2010-06-10 15:00'), array('This value should be 6/10/2010 3:00 PM or more')),
            array('2010-05-10 15:02', array('max' => '2010-05-10 15:01'), array('This value should be 5/10/2010 3:01 PM or less')),
            array('2010-05-10 15:01:02', array('max' => '2010-05-10 15:01:01'), array('This value should be 5/10/2010 3:01:01 PM or less')),

            array('2010-05-10 15:01:01', array('max' => '2010-05-10 15:01'), array('This value should be 5/10/2010 3:01 PM or less')),
            array('2010-05-10', array('max' => '2010-04-10', 'time_optional' => true), array('This value should be 4/10/2010 or less')),
        );
    }

    public static function getDataForFormat()
    {
        return array(
            // $locale, $input, $expected
            array('nl_NL', '2010-10-04', '04-10-2010'),
            array('nl_NL', '2010-05-04', '04-05-2010'),
            array('nl_NL', '1990-05-04', '04-05-1990'),

            array('nl_NL', '2010-10-04 15:00', '04-10-2010 15:00'),
            array('nl_NL', '2010-05-04 23:15', '04-05-2010 23:15'),
            array('nl_NL', '1990-05-04 00:30', '04-05-1990 00:30'),

            array('en_US', '2010-04-21', '4/21/2010'),
            array('en_US', '2010-10-21', '10/21/2010'),
            array('en_US', '2010-04-21 15:00', '4/21/2010 3:00 PM'),
            array('en_US', '2010-10-21 15:00', '10/21/2010 3:00 PM'),

            array('uz_Arab', '2010-05-04', '۲۰۱۰-۰۵-۰۴'),
            array('uz_Arab', '2010-05-04 15:00', '۲۰۱۰-۰۵-۰۴ ۱۵:۰۰'),

            // Right-to-left
            array('ar_YE', '2010-05-04', '٤‏/٥‏/٢٠١٠'),
            array('ar_YE', '2010-05-04 15:00', '٤‏/٥‏/٢٠١٠ ٣:٠٠ م'),
        );
    }

    public static function getDataForCompare()
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

    public static function getDataForGetHigherValue()
    {
        return array(
            // $locale, $input, $expected, $options
            array('nl_NL', '04-10-2010 15:15', '04-10-2010 15:16'),
            array('nl_NL', '04-10-2010 23:59', '05-10-2010 00:00'),
            array('nl_NL', '04-10-2010 23:59:59', '05-10-2010 00:00:00'),
            array('nl_NL', '04-10-2010 23:20:59', '04-10-2010 23:21:00'),
            array('nl_NL', '04-10-2010 23:20:10', '04-10-2010 23:20:11'),

            array('nl_NL', '04-10-2010',       '05-10-2010', array('time_optional' => true)),
            array('nl_NL', '04-10-2010',       '05-10-2010', array('time_optional' => true)),
        );
    }

    public static function getDataForSorting()
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
