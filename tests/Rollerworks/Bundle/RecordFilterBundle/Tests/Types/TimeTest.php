<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Types;

use Rollerworks\Bundle\RecordFilterBundle\Type\Time;
use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

class TimeTest extends DateTimeTestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    public function testSanitize($locale, $input, $expected, $expectFail = false)
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
    public function testDump($locale, $input, $expected, $expectFail = false)
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
    public function testValidation($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Time();
        $messageBag = new MessageBag($this->translator);

        $type->validateValue($input, $messageBag);

        if ($expectFail) {
            $this->assertTrue($messageBag->has('error'), sprintf('Assert "%s" is invalid.', $input));
        } else {
            $this->assertEquals(array(), $messageBag->get('error'), sprintf('Assert "%s" is valid', $input));
        }
    }

    /**
     * @dataProvider getDataForAdvancedValidation
     */
    public function testValidationAdvanced($input, $options = array(), $expectMessage = false)
    {
        if ('en' !== \Locale::getDefault()) {
            \Locale::setDefault('en');
        }

        $type = new Time($options);
        $messageBag = new MessageBag($this->translator);

        $type->validateValue($input, $messageBag);

        if (is_array($expectMessage)) {
            $this->assertEquals($expectMessage, $messageBag->get('error'), sprintf('Assert "%s" is invalid and messages are equal.', $input));
        } else {
            $this->assertEquals(array(), $messageBag->get('error'), sprintf('Assert "%s" is valid', $input));
        }
    }

    /**
     * @dataProvider getDataForFormat
     */
    public function testFormat($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new Time();

        $this->assertEquals($expected, $type->formatOutput(new DateTimeExtended($input, true)));
    }

    /**
     * @dataProvider getDataForCompare
     */
    public function testCompares($locale, $first, $second, $comparison = null)
    {
        \Locale::setDefault($locale);

        $type = new Time();

        $_first  = $type->sanitizeString($first);
        $_second = $type->sanitizeString($second);

        if ('==' === $comparison) {
            $this->assertTrue($type->isEqual($_first, $_second), sprintf('"%s" should equal "%s"', $first, $second));
        } elseif ('!=' === $comparison) {
            $this->assertFalse($type->isEqual($_first, $_second), sprintf('"%s" should not equal "%s"', $first, $second));
        } else {
            $this->assertTrue($type->isLower($_second, $_first), sprintf('"%s" should be lower then "%s"', $first, $second));

            // 00 is both higher and lower
            if ('</>' !== $comparison) {
                $this->assertFalse($type->isLower($_first, $_second), sprintf('"%s" should not be lower then "%s"', $first, $second));
                 $this->assertFalse($type->isHigher($_second, $_first), sprintf('"%s" should be higher then "%s"', $first, $second));
            }

            $this->assertTrue($type->isHigher($_first, $_second), sprintf('"%s" should not be higher then "%s"', $first, $second));
        }
    }

    /**
     * @dataProvider getDataForGetHigherValue
     */
    public function testGetHigherValue($locale, $input, $expected)
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

            // These are legal in ISO
            //array('en_US', '15:00',      '', true),
            //array('en_US', '03:00',      '', true),
            //array('en_US', '03:00',      '', true),

            array('uz_Arab', '۱۳:۰۰', '13:00'),

            // Right-to-left
            array('ar_YE', '١:٠٠ م', '13:00'),
        );
    }

    public static function getDataForAdvancedValidation()
    {
        return array(
            // $input, $options, $expectMessage
            array('15:00', array('max' => '15:00')),
            array('15:00', array('max' => '15:00')),
            array('15:00', array('min' => '15:00')),

            array('15:00', array('max' => '15:00')),
            array('15:00', array('max' => '15:01')),
            array('15:01:00', array('max' => '15:01')),

            array('15:00', array('min' => '15:05'), array('This value should be 3:05 PM or more.')),
            array('15:02', array('max' => '15:01'), array('This value should be 3:01 PM or less.')),
            array('15:01:02', array('max' => '15:01:01'), array('This value should be 3:01:01 PM or less.')),
            array('15:01:01', array('max' => '15:01'), array('This value should be 3:01 PM or less.')),
        );
    }

    public static function getDataForFormat()
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
            array('nl_NL', '23:59:59', '00:00:00'),
            array('nl_NL', '23:20:00', '23:20:01'),
            array('nl_NL', '23:20:10', '23:20:11'),
        );
    }
}
