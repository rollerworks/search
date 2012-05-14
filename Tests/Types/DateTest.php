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

use Rollerworks\RecordFilterBundle\Type\Date;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

class DateTest extends DateTimeTestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    function testSanitize($locale, $input, $expected, $expectFail = false)
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
    function testDump($locale, $input, $expected, $expectFail = false)
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
    function testValidation($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Date();

        if ($expectFail) {
            $this->assertFalse($type->validateValue($input));
        }
        else {
            $this->assertTrue($type->validateValue($input));
        }
    }

    /**
     * @dataProvider getDataForFormat
     */
    function testFormat($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new Date();

        $this->assertEquals($expected, $type->formatOutput(new \DateTime($input)));
    }

    /**
     * @dataProvider getDataForCompare
     */
    function testCompares($locale, $first, $second, $comparison = null)
    {
        \Locale::setDefault($locale);

        $type = new Date();

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
    function testGetHigherValue($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new Date();
        $this->assertEquals($type->sanitizeString($expected)->format('Y-m-d'), $type->getHigherValue($type->sanitizeString($input))->format('Y-m-d'));
    }

    /**
     * @dataProvider getDataForSorting
     */
    function testSorting($locale, $input, $expected)
    {
        \Locale::setDefault($locale);

        $type = new Date();

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

    static public function getDataForFormat()
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

    static public function getDataForCompare()
    {
        return array(
            // $locale, $first (higher), $second (lower), $comparison
            array('nl_NL', '04-10-2010', '04-10-2010', '=='),
            array('nl_NL', '04-10-2010', '05-10-2010', '!='),

            array('nl_NL', '04-11-2010', '04-10-2010'),
            array('nl_NL', '05-10-2010', '04-10-2010'),
        );
    }

    static public function getDataForGetHigherValue()
    {
        return array(
            // $locale, $input, $expected
            array('nl_NL', '04-10-2010', '05-10-2010'),
            array('nl_NL', '30-11-2010', '01-12-2010'),
            array('nl_NL', '31-12-2010', '01-01-2011'),
        );
    }

    static public function getDataForSorting()
    {
        return array(
            // $locale, $values, $expected
            array('nl_NL', array(0 => '15-04-2010', 4 => '05-03-2010', 6 => '14-05-2012'), array(4 => '05-03-2010', 0 => '15-04-2010', 6 => '14-05-2012')),
            array('nl_NL', array(1 => '16-04-2010', 3 => '15-04-2010', 4 => '15-02-2011'), array(4 => '15-02-2011', 3 => '15-04-2010', 1 => '16-04-2010')),
        );
    }
}
