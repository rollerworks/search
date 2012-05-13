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

use Rollerworks\RecordFilterBundle\Type\Number;

class NumberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    function testSanitize($locale, $input, $expected, $expectFail = false)
    {
        if ($expectFail) {
            return;
        }

        \Locale::setDefault($locale);

        $type = new Number();

        $this->assertEquals($expected, $type->sanitizeString($input));
    }

    /**
     * @dataProvider getDataForSanitation
     */
    function testValidation($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Number();

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
    function testCompares($first, $second, $comparison = null)
    {
        $type = new Number();

        if ('==' === $comparison) {
            $this->assertTrue($type->isEquals($first, $second), sprintf('"%s" should equal "%s"', $first, $second));
        }
        elseif ('!=' === $comparison) {
            $this->assertFalse($type->isEquals($first, $second), sprintf('"%s" should not equal "%s"', $first, $second));
        }
        else {
            $this->assertTrue($type->isLower($second, $first), sprintf('"%s" should be lower then "%s"', $second, $first));
            $this->assertFalse($type->isLower($first, $second), sprintf('"%s" should not be lower then "%s"', $first, $second));

            $this->assertTrue($type->isHigher($first, $second), sprintf('"%s" should higher then "%s"', $first, $second));
            $this->assertFalse($type->isHigher($second, $first), sprintf('"%s" should not be higher then "%s"', $second, $first));
        }
    }

    /**
     * @dataProvider getDataForGetHigherValue
     */
    function testGetHigherValue($input, $expected)
    {
        $type = new Number();
        $this->assertEquals($expected, $type->getHigherValue($input));
    }

    static public function getDataForSanitation()
    {
        return array(
            // $locale, $input, $expected, $expectFail
            array('nl_NL', '4446546', '4446546'),
            array('nl_NL', '004446546', '004446546'),
            array('nl_NL', '4446546000000000000000000000', '4446546000000000000000000000'),
            array('en_US', '4446546', '4446546'),
            array('uz_Arab', '۰۵', '05'),
            array('en_US', '۰۵', '05'), // Not really valid, but the validation must past

            array('en_US', '4446546.00', '', true),
            array('en_US', 'D4446546.00', '', true),
            array('en_US', 'A03', '', true),
        );
    }

    static public function getDataForCompare()
    {
        return array(
            // $first (higher), $second (lower), $comparison
            array('4554444644665', '4554444644665',   '=='),
            array('04554444644665', '04554444644665', '=='),
            array('04554444644665', '04554444644665', '=='),
            array('04554444644665', '455444464',      '!='),

            array('700', '600'),
            array('0700', '0600'),
            array('700', '-800'),
            array('0700', '-0800'),
            array('700000000000000000000000000000', '600000000000000000000000000000'),
            array('00700000000000000000000000000000', '00600000000000000000000000000000'),
            array('800000000000000', '-800000000000000'),
            array('44645464446544665', '446454644465'),
        );
    }

    static public function getDataForGetHigherValue()
    {
        return array(
            // $input, $expected
            array('700', '701'),
            array('0700', '0701'),
            array('700000000000000000000000000', '700000000000000000000000001'),
            array('-700000000000000000000000000', '-699999999999999999999999999'),
            array('-700', '-699'),
            array('-0700', '-0699'),
        );
    }
}
