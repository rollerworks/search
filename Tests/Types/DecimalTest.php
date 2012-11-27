<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests;

use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Type\Decimal;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

class DecimalTest extends \Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    public function testSanitize($locale, $input, $expected, $expectFail = false)
    {
        if ($expectFail) {
            return;
        }

        \Locale::setDefault($locale);

        $type = new Decimal();

        $this->assertEquals($expected, $type->sanitizeString($input));
    }

    /**
     * @dataProvider getDataForMatcher
     */
    public function testMatcher($locale, $input, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Decimal();

        if (!preg_match('#^(' . $type->getMatcherRegex() . ')$#uis', $input, $match)) {
            if ($expectFail) {
                return true;
            }

            $this->fail(sprintf('Input "%s" does not match in regex "%s"', $input, $type->getMatcherRegex()));
        }

        if ($expectFail) {
            $this->fail(sprintf('Input "%s" should not match in regex "%s"', $input, $type->getMatcherRegex()));
        }

        $this->assertEquals($input, $match[1]);
    }

    /**
     * @dataProvider getDataForSanitation
     */
    public function testValidation($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Decimal();
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
        if (!isset($options['locale']) && 'en' !== \Locale::getDefault() ) {
            \Locale::setDefault('en');
        }

        if (isset($options['locale'])) {
            \Locale::setDefault($options['locale']);
            unset($options['locale']);
        }

        $type = new Decimal($options);
        $messageBag = new MessageBag($this->translator);

        $type->validateValue($input, $messageBag);

        if (is_array($expectMessage)) {
            $this->assertEquals($expectMessage, $messageBag->get('error'), sprintf('Assert "%s" is invalid and messages are equal.', $input));
        } else {
            $this->assertEquals(array(), $messageBag->get('error'), sprintf('Assert "%s" is valid', $input));
        }
    }

    /**
     * @dataProvider getDataForCompare
     */
    public function testCompares($first, $second, $comparison = null)
    {
        $type = new Decimal();

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
    public function testGetHigherValue($input, $expected)
    {
        $type = new Decimal();
        $this->assertEquals($expected, $type->getHigherValue($input));
    }

    /**
     * @dataProvider getDataForSorting
     */
    public function testSorting($input, $expected)
    {
        $mapping = function ($input) {
            return new SingleValue($input);
        };

        $input = array_map($mapping, $input);
        $expected = array_map($mapping, $expected);

        $type = new Decimal();

        usort($input, array(&$type, 'sortValuesList'));
        $this->assertEquals($expected, $input);
    }

    public static function getDataForSanitation()
    {
        return array(
            // $locale, $input, $expected, $expectFail
            array('nl_NL', '100,10', '100.10'),
            array('nl_NL', '100,10', '100.10'),

            array('en_US', '100.00', '100.00'),
            array('uz_Arab', '۵٫۵', '05.50'),
        );
    }

    public static function getDataForMatcher()
    {
        return array(
            // $locale, $input, $expectFail
            array('nl_NL', '100,10'),
            array('nl_NL', '100,104224244'),
            array('nl_NL', '100.10'),
            array('nl_NL', '100.104224244'),

            array('nl_NL', '-100,10'),
            array('nl_NL', '-100,104224244'),
            array('nl_NL', '-100.10'),
            array('nl_NL', '-100.104224244'),

            array('nl_NL', '100-10', true),
            array('nl_NL', '100*104224244', true),
            array('nl_NL', '10010', true),
            array('nl_NL', '100/104224244', true),

            array('en_US', '100.00'),
            array('en_US', '-100.00'),

            array('en_US', '-100,00', true),
            array('en_US', '-100,00', true),

            array('uz_Arab', '۵٫۵'),
            array('uz_Arab', '۵٫۵'),
            array('uz_Arab', '-۵٫۵'),
            array('uz_Arab', '-۵٫۵'),
        );
    }

    public static function getDataForAdvancedValidation()
    {
        return array(
            // $input, $options, $expectMessage
            array('12000.1001', array('min' => '12000.1001', 'max_fraction_digits' => 4)),
            array('12000.1001', array('min' => '11000.1001', 'max_fraction_digits' => 4)),

            array('12000.1001', array('max' => '12000.10001', 'max_fraction_digits' => 5)),
            array('12000.1001', array('max' => '12001.1001', 'max_fraction_digits' => 4)),

            array('70000000000000000.1000', array('min' => '70000000000000000.1000', 'max_fraction_digits' => 4)),
            array('70000000000000000.1000', array('max' => '70000000000000000.1000', 'max_fraction_digits' => 4)),
            array('70000000000000000.1000', array('max' => '80000000000000000.1000', 'max_fraction_digits' => 4)),

            array('12000.1001', array('min' => '13000.1001', 'max_fraction_digits' => 4), array('This value should be 13,000.1001 or more.')),
            array('15000.1001', array('max' => '12000.1001', 'max_fraction_digits' => 4), array('This value should be 12,000.1001 or less.')),

            array('12000000.1001', array('min' => '13000000.1001', 'max_fraction_digits' => 4), array('This value should be 13,000,000.1001 or more.')),
            array('15000000.1001', array('max' => '12000000.1001', 'max_fraction_digits' => 4), array('This value should be 12,000,000.1001 or less.')),

            // The following exceeds 32bit

            array('90000000000.1000', array('min' => '900000000000.1001', 'max_fraction_digits' => 4), array('This value should be 900,000,000,000.1001 or more.')),
            array('90000000000.1000', array('max' => '6000000000.1001', 'max_fraction_digits' => 4), array('This value should be 6,000,000,000.1001 or less.')),

            array('900000000000000000.1000', array('min' => '700000000000000000.1000', 'max' => '800000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 800,000,000,000,000,000.1000 or less.')),
            array('7000000000000000.1000', array('min' => '800000000000000000.1000', 'max' => '900000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 800,000,000,000,000,000.1000 or more.')),

            array('900000000000000000.1000', array('min' => '700000000000000000.1000', 'max' => '800000000000000000.1000', 'max_fraction_digits' => 2, 'format_grouping' => false), array('This value should be 800000000000000000.10 or less.')),
            array('7000000000000000.1000', array('min' => '800000000000000000.1000', 'max' => '900000000000000000.1000', 'max_fraction_digits' => 2, 'format_grouping' => false), array('This value should be 800000000000000000.10 or more.')),

            array('900000000000000000.1000', array('min' => '700000000000000000.1000', 'max' => '800000000000000000.1000', 'min_fraction_digits' => 6, 'max_fraction_digits' => 10, 'format_grouping' => false), array('This value should be 800000000000000000.100000 or less.')),
            array('7000000000000000.1000', array('min' => '800000000000000000.1000', 'max' => '900000000000000000.1000', 'min_fraction_digits' => 6, 'max_fraction_digits' => 10, 'format_grouping' => false), array('This value should be 800000000000000000.100000 or more.')),

            // The following exceeds 64bit

            array('50000000000000000000.1000', array('min' => '60000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 60,000,000,000,000,000,000.1000 or more.')),
            array('70000000000000000000.1000', array('max' => '60000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 60,000,000,000,000,000,000.1000 or less.')),

            array('-70000000000000000000.1000', array('min' => '-60000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be -60,000,000,000,000,000,000.1000 or more.')),
            array('70000000000000000000.1000', array('max' => '-60000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be -60,000,000,000,000,000,000.1000 or less.')),

            array('90000000000000000000.1000', array('min' => '70000000000000000000.1000', 'max' => '80000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 80,000,000,000,000,000,000.1000 or less.')),
            array('70000000000000000000.1000', array('min' => '80000000000000000000.1000', 'max' => '90000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 80,000,000,000,000,000,000.1000 or more.')),

            // Tests to make sure numbers are properly formatted in unicode

            array('50000000000000000000٫1000', array('min' => '60000000000000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۶۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٫۱۰۰۰ or more.')),
            array('70000000000000000000٫1000', array('max' => '60000000000000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۶۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٫۱۰۰۰ or less.')),

            array('-70000000000000000000٫1000', array('min' => '-60000000000000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'ar_YE'), array('This value should be ٦٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٫١٠٠٠- or more.')),
            array('70000000000000000000٫1000', array('max' => '-60000000000000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'ar_YE'), array('This value should be ٦٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٠٫١٠٠٠- or less.')),

            array('90000000000000000000٫1000', array('min' => '70000000000000000000.1000', 'max' => '80000000000000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۸۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٫۱۰۰۰ or less.')),
            array('70000000000000000000٫1000', array('min' => '80000000000000000000.1000', 'max' => '90000000000000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۸۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٬۰۰۰٫۱۰۰۰ or more.')),
        );
    }

    public static function getDataForCompare()
    {
        return array(
            // $first (higher), $second (lower), $comparison
            array('100.00', '100.00',  '=='),
            array('100.10', '100.10',  '=='),
            array('100.10', '100.1',   '=='),
            array('100.00', '100.000', '=='),
            array('3000000000000000000000.00', '3000000000000000000000.000', '=='),
            array('200.00', '200.10',  '!='),
            array('200.00', '300.00',  '!='),
            array('3000000000000000000000.00', '2000000000000000000000.00',  '!='),

            array('300.00', '200.00'),
            array('300.00', '200.00'),
            array('200.1',  '200.01'),
            array('3000000000000000000000.00', '2000000000000000000000.00'),
        );
    }

    public static function getDataForGetHigherValue()
    {
        return array(
            // $input, $expected
            array('100.01', '100.02'),
            array('100.01', '100.02'),
            array('100.00', '100.01'),
            array('1000000000000000000000000000.00', '1000000000000000000000000000.01'),
        );
    }

    public function getDataForSorting()
    {
        return array(
            array(array(10.00, 20.00, 30.00, 50), array(10.00, 20.00, 30.00, 50.00)),
            array(array(-10.00, 20.00, 30.00, 50.00), array(-10.00, 20.00, 30.00, 50.00)),
            array(array(10.00, -20.00, 30.00, 50.00), array(-20.00, 10.00, 30.00, 50.00)),

            array(array('10.00', '10.00', '-20.00', '30.00', '50.00'), array('-20.00', '10.00', '10.00', '30.00', '50.00')),

            array(array('100000000000000000000.00', '-200000000000000000000.00', '300000000000000000000.00', '500000000000000000000.00'), array('-200000000000000000000.00', '100000000000000000000.00', '300000000000000000000.00', '500000000000000000000.00')),
            array(array('1000000000000000000000000000000000000000.05', '1000000000000000000000000000000000000000.04', '-2000000000000000000000000000000000000000.00', '3000000000000000000000000000000000000000.00', '5000000000000000000000000000000000000000.00'), array('-2000000000000000000000000000000000000000.00', '1000000000000000000000000000000000000000.04', '1000000000000000000000000000000000000000.05', '3000000000000000000000000000000000000000.00', '5000000000000000000000000000000000000000.00')),

            array(array('3000000000000000000000000000000000000000.00', '3000000000000000000000000000000000000000.00', '-2000000000000000000000000000000000000000.00', '5000000000000000000000000000000000000000.00', '1000000000000000000000000000000000000000.00'), array('-2000000000000000000000000000000000000000.00', '1000000000000000000000000000000000000000.00', '3000000000000000000000000000000000000000.00', '3000000000000000000000000000000000000000.00', '5000000000000000000000000000000000000000.00')),
        );
    }
}
