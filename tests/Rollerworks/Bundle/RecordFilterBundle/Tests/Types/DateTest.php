<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Types;

use Rollerworks\Bundle\RecordFilterBundle\Type\Date;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

class DateTest extends DateTimeTestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    public function testSanitize($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Date();

        if ($expectFail) {
            $this->setExpectedException('\UnexpectedValueException', sprintf('Input value "%s" is not properly validated.', $input));
        }

        $value = $type->sanitizeString($input);
        $this->assertEquals($expected, $value->format('Y-m-d'));
    }

    /**
     * @dataProvider getDataForSanitation
     */
    public function testDump($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Date();

        if ($expectFail) {
            return;
        }

        $value = $type->sanitizeString($input);
        $this->assertEquals($value->format('Y-m-d'), $type->dumpValue($value));
    }

    /**
     * @dataProvider getDataForSanitation
     */
    public function testValidation($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Date();
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
        $type = new Date($options);
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

        $type = new Date();

        $this->assertEquals($expected, $type->formatOutput(new \DateTime($input)));
    }

    /**
     * @dataProvider getDataForCompare
     */
    public function testCompares($locale, $first, $second, $comparison = null)
    {
        \Locale::setDefault($locale);

        $type = new Date();

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
    public function testGetHigherValue($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new Date();
        $this->assertEquals($type->sanitizeString($expected)->format('Y-m-d'), $type->getHigherValue($type->sanitizeString($input))->format('Y-m-d'));
    }

    public static function getDataForSanitation()
    {
        return array(
            // $locale, $input, $expected, $expectFail
            array('nl_NL', '04-10-2010', '2010-10-04'),
            array('nl_NL', '04-10-10',   '2010-10-04'),
            array('nl_NL', '04/10/2010', '2010-10-04'),
            array('nl_NL', '31-12-2010', '2010-12-31'),
            array('nl_NL', '29-02-2012', '2012-02-29'),
            array('nl_NL', '29-02-2011', '', true),
            array('nl_NL', '04-10-2010 12:00', '', true),

            array('en_US', '04/21/2010', '2010-04-21'),
            array('en_US', '04-21-2010', '2010-04-21'),
            array('en_US', '04/21/10',   '2010-04-21'),
            array('en_US', '12/31/2010', '2010-12-31'),
            array('en_US', '02/29/2012', '2012-02-29'),
            array('en_US', '29/02/2011', '', true),
        );
    }

    public static function getDataForAdvancedValidation()
    {
        return array(
            // $input, $options, $expectMessage
            array('2010-04-10', array('max' => '2010-05-10')),
            array('2010-04-10', array('max' => '2010-04-10')),
            array('2010-04-10', array('min' => '2010-03-10')),

            array('2010-04-10', array('max' => '2010-05-10')),
            array('2010-04-10', array('max' => '2010-04-10')),

            array('2010-04-10', array('min' => '2010-06-10'), array('This value should be 6/10/2010 or more.')),
            array('2010-05-11', array('max' => '2010-05-10'), array('This value should be 5/10/2010 or less.')),
            array('2010-05-11', array('min' => '2010-04-01', 'max' => '2010-04-10'), array('This value should be 4/10/2010 or less.')),
            array('2010-03-9', array('min' => '2010-04-01', 'max' => '2010-04-10'), array('This value should be 4/1/2010 or more.')),
        );
    }

    public static function getDataForFormat()
    {
        return array(
            // $locale, $input, $expected
            array('nl_NL', '2010-10-04', '04-10-2010'),
            array('nl_NL', '2010-05-04', '04-05-2010'),
            array('nl_NL', '1990-05-04', '04-05-1990'),

            array('en_US', '2010-04-21', '4/21/2010'),
            array('en_US', '2010-10-21', '10/21/2010'),

            array('uz_Arab', '2010-05-04', '۲۰۱۰-۰۵-۰۴'),

            // Right-to-left
            array('ar_YE', '2010-05-04', '٤‏/٥‏/٢٠١٠'),
        );
    }

    public static function getDataForCompare()
    {
        return array(
            // $locale, $first (higher), $second (lower), $comparison
            array('nl_NL', '04-10-2010', '04-10-2010', '=='),
            array('nl_NL', '04-10-2010', '05-10-2010', '!='),

            array('nl_NL', '04-11-2010', '04-10-2010'),
            array('nl_NL', '05-10-2010', '04-10-2010'),
        );
    }

    public static function getDataForGetHigherValue()
    {
        return array(
            // $locale, $input, $expected
            array('nl_NL', '04-10-2010', '05-10-2010'),
            array('nl_NL', '30-11-2010', '01-12-2010'),
            array('nl_NL', '31-12-2010', '01-01-2011'),
        );
    }
}
