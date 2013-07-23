<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests;

use Rollerworks\Bundle\RecordFilterBundle\Type\Decimal;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

class DecimalTest extends \Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    public function testSanitize($locale, $input, $expected, $expectFail = false, $big = false)
    {
        if ($expectFail) {
            return;
        }

        $this->checkBig($big);

        \Locale::setDefault($locale);

        $type = new Decimal(array('min_fraction_digits' => 2));

        $this->assertEquals($expected, $type->sanitizeString($input));
    }

    /**
     * @dataProvider getDataForMatcher
     */
    public function testMatcher($locale, $input, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Decimal(array('min_fraction_digits' => 2));

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
    public function testValidation($locale, $input, $expected, $expectFail = false, $big = false)
    {
        $this->checkBig($big);

        \Locale::setDefault($locale);

        $type = new Decimal(array('min_fraction_digits' => 2));
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
    public function testValidationAdvanced($input, $options = array(), $expectMessage = false, $big = false)
    {
        $this->checkBig($big, true);

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
    public function testCompares($first, $second, $comparison = null, $big = false)
    {
        $this->checkBig($big);

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
    public function testGetHigherValue($input, $expected, $big = false)
    {
        $this->checkBig($big);

        $type = new Decimal();
        $this->assertEquals($expected, $type->getHigherValue($input));
    }

    public static function getDataForSanitation()
    {
        return array(
            // $locale, $input, $expected, $expectFail, $big
            array('nl_NL', '100,10', 100.10),
            array('nl_NL', '100,10', 100.10),
            array('nl_NL', '70000000000000000,1000', '70000000000000000.1000'),

            array('en_US', '100.00', 100.00),
            array('uz_Arab', '۵٫۵', 5.50),
            array('uz_Arab', '۵۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰۰٫۵', 50000000000000000000.50, false, true),
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
            // $input, $options, $expectMessage, $big
            array('12000.1001', array('min' => '12000.1001', 'max_fraction_digits' => 4)),
            array('12000.1001', array('min' => '11000.1001', 'max_fraction_digits' => 4)),

            array('12000.1001', array('max' => '12001.1001', 'max_fraction_digits' => 5)),
            array('12000.1001', array('max' => '12001.1001', 'max_fraction_digits' => 4)),

            array('70000000000000000.1000', array('min' => '70000000000000000.1000', 'max_fraction_digits' => 4)),
            array('70000000000000000.1000', array('max' => '70000000000000000.1000', 'max_fraction_digits' => 4)),
            array('70000000000000000.1000', array('max' => '80000000000000000.1000', 'max_fraction_digits' => 4)),

            array('12000.1001', array('min' => '13000.1001', 'max_fraction_digits' => 4), array('This value should be 13,000.1001 or more.')),
            array('15000.1001', array('max' => '12000.1001', 'max_fraction_digits' => 4), array('This value should be 12,000.1001 or less.')),

            array('12000.1001', array('min' => '13000.10015', 'max_fraction_digits' => 4), array('This value should be 13,000.1002 or more.')),
            array('15000.1001', array('max' => '12000.10015', 'max_fraction_digits' => 4), array('This value should be 12,000.1002 or less.')),

            array('-12000.1001', array('min' => '-11000.1001', 'max_fraction_digits' => 4), array('This value should be -11,000.1001 or more.')),
            array('-15000.1001', array('max' => '-16000.1001', 'max_fraction_digits' => 4), array('This value should be -16,000.1001 or less.')),

            array('12000000.1001', array('min' => '13000000.1001', 'max_fraction_digits' => 4), array('This value should be 13,000,000.1001 or more.')),
            array('15000000.1001', array('max' => '12000000.1001', 'max_fraction_digits' => 4), array('This value should be 12,000,000.1001 or less.')),

            // The following exceeds 32bit

            // 9223372036854775807

            array('90000000000.1000', array('min' => '900000000000.1001', 'max_fraction_digits' => 4), array('This value should be 900000000000.1001 or more.')),
            array('90000000000.1000', array('min' => '900000000000.1001', 'max_fraction_digits' => 4), array('This value should be 900,000,000,000.1 or more.'), true),
            array('90000000000.1000', array('max' => '6000000000.1001', 'max_fraction_digits' => 4), array('This value should be 6000000000.1001 or less.')),
            array('90000000000.1000', array('max' => '6000000000.1001', 'max_fraction_digits' => 4), array('This value should be 6,000,000,000.1001 or less.'), true),

            array('90000000000000.1000', array('min' => '70000000000000.1000', 'max' => '80000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 80000000000000.1000 or less.')),
            array('70000000000000.1000', array('min' => '80000000000000.1000', 'max' => '90000000000000.1000', 'min_fraction_digits' => 4, 'max_fraction_digits' => 6), array('This value should be 80,000,000,000,000.1000 or more.'), true),
            array('90000000000000.1000', array('min' => '70000000000000.1000', 'max' => '80000000000000.1000', 'min_fraction_digits' => 4, 'max_fraction_digits' => 6), array('This value should be 80000000000000.1000 or less.')),
            array('70000000000000.1000', array('min' => '80000000000000.1000', 'max' => '90000000000000.1000', 'min_fraction_digits' => 4, 'max_fraction_digits' => 6), array('This value should be 80,000,000,000,000.1000 or more.'), true),

            array('90000000000000.1000', array('min' => '70000000000000.1000', 'max' => '80000000000000.1000', 'max_fraction_digits' => 2, 'format_grouping' => false), array('This value should be 80000000000000.1000 or less.')),
            array('90000000000000.1000', array('min' => '70000000000000.1000', 'max' => '80000000000000.1000', 'min_fraction_digits' => 1, 'max_fraction_digits' => 2, 'format_grouping' => false), array('This value should be 80000000000000.1 or less.'), true),
            array('70000000000000.1000', array('min' => '80000000000000.1000', 'max' => '90000000000000.1000', 'max_fraction_digits' => 2, 'format_grouping' => false), array('This value should be 80000000000000.1000 or more.')),
            array('70000000000000.1000', array('min' => '80000000000000.1000', 'max' => '90000000000000.1000', 'max_fraction_digits' => 2, 'format_grouping' => false), array('This value should be 80000000000000.1 or more.'), true),

            array('90000000000000.1000', array('min' => '70000000000000.1000', 'max' => '80000000000000.1000', 'min_fraction_digits' => 6, 'max_fraction_digits' => 10, 'format_grouping' => false), array('This value should be 80000000000000.1000 or less.')),
            array('90000000000000.1000', array('min' => '70000000000000.1000', 'max' => '80000000000000.1000', 'min_fraction_digits' => 6, 'max_fraction_digits' => 10, 'format_grouping' => false), array('This value should be 80000000000000.100000 or less.'), true),
            array('70000000000000.1000', array('min' => '80000000000000.1000', 'max' => '90000000000000.1000', 'min_fraction_digits' => 6, 'max_fraction_digits' => 10, 'format_grouping' => false), array('This value should be 80000000000000.1000 or more.')),
            array('70000000000000.1000', array('min' => '80000000000000.1000', 'max' => '90000000000000.1000', 'min_fraction_digits' => 6, 'max_fraction_digits' => 10, 'format_grouping' => false), array('This value should be 80000000000000.100000 or more.'), true),

            // The following exceeds 64bit

            array('50000000000000000000.1000', array('min' => '60000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 60000000000000000000.1000 or more.')),
            array('70000000000000000000.1000', array('max' => '60000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 60000000000000000000.1000 or less.')),

            array('-70000000000000000000.1000', array('min' => '-60000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be -60000000000000000000.1000 or more.')),
            array('70000000000000000000.1000', array('max' => '-80000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be -80000000000000000000.1000 or less.')),

            array('90000000000000000000.1000', array('min' => '70000000000000000000.1000', 'max' => '80000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 80000000000000000000.1000 or less.')),
            array('70000000000000000000.1000', array('min' => '80000000000000000000.1000', 'max' => '90000000000000000000.1000', 'max_fraction_digits' => 4), array('This value should be 80000000000000000000.1000 or more.')),

            // Tests to make sure numbers are properly formatted in unicode

            array('50000,1000', array('min' => '60000.1000', 'min_fraction_digits' => 4, 'max_fraction_digits' => 5, 'locale' => 'uz_Arab'), array('This value should be ۶۰٬۰۰۰٫۱۰۰۰ or more.')),
            array('70000٫1000', array('max' => '60000.1000', 'min_fraction_digits' => 4, 'max_fraction_digits' => 5, 'locale' => 'uz_Arab'), array('This value should be ۶۰٬۰۰۰٫۱۰۰۰ or less.')),

            array('50000,1000', array('min' => '60000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۶۰٬۰۰۰٫۱ or more.')),
            array('70000٫1000', array('max' => '60000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۶۰٬۰۰۰٫۱ or less.')),

            array('50000000000٫1000', array('min' => '60000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۶۰٬۰۰۰٬۰۰۰٬۰۰۰٫۱ or more.'), true),
            array('70000000000٫1000', array('max' => '60000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۶۰٬۰۰۰٬۰۰۰٬۰۰۰٫۱ or less.'), true),

            array('-70000000000.1000', array('min' => '-60000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'ar_YE'), array('This value should be ٦٠٠٠٠٠٠٠٠٠٠٫١- or more.'), true), // 43
            array('70000000000.1000', array('max' => '-60000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'ar_YE'), array('This value should be ٦٠٠٠٠٠٠٠٠٠٠٫١- or less.'), true), // 44

            array('90000000000٫1000', array('min' => '70000000000.1000', 'max' => '80000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۸۰٬۰۰۰٬۰۰۰٬۰۰۰٫۱ or less.'), true),
            array('70000000000٫1000', array('min' => '80000000000.1000', 'max' => '90000000000.1000', 'max_fraction_digits' => 4, 'locale' => 'uz_Arab'), array('This value should be ۸۰٬۰۰۰٬۰۰۰٬۰۰۰٫۱ or more.'), true),
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
            array('200.00', '100.000', '>'),
            array('3000000000000.00', '3000000000000.000', '=='),
            array('3000000000000.00', '2000000000000.000', '>'),
            array('200.00', '200.10',  '!='),
            array('200.00', '300.00',  '!='),
            array('3000000000000.00', '2000000000000.00',  '!='),

            array('300.00', '200.00'),
            array('300.00', '200.00'),
            array('200.1',  '200.01'),
            array('3000000000000.00', '2000000000000.00'),
        );
    }

    public static function getDataForGetHigherValue()
    {
        return array(
            // $input, $expected
            array('100.01', '100.02'),
            array('100.01', '100.02'),
            array('100.00', '100.01'),
            array('1000000000000000000.00', '1000000000000000000.01'),
        );
    }

    protected function checkBig($big, $req32bit = false)
    {
        if ($big && PHP_INT_MAX == '2147483647') {
            $this->markTestSkipped('Requires 64bit support.');
        }

        if (!$big && $req32bit && PHP_INT_MAX != '2147483647') {
            $this->markTestSkipped('Only run 32bit platform.');
        }
    }
}
