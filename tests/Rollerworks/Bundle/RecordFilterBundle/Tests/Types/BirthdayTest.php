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

use Rollerworks\Bundle\RecordFilterBundle\Type\Birthday;
use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

class BirthdayTest extends DateTimeTestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    public function testSanitize($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Birthday();

        if ($expectFail) {
            $this->setExpectedException('\UnexpectedValueException', sprintf('Input value "%s" is not properly validated.', $input));
        }

        $value = $type->sanitizeString($input);

        if (is_object($value)) {
            $this->assertEquals($expected, $value->format('Y-m-d'));
        } else {
            $this->assertEquals($expected, $value);
        }

        if (!$expectFail) {
            if (is_object($value)) {
                $this->assertEquals($value->format('Y-m-d'), $type->dumpValue($value));
            } else {
                $this->assertEquals($expected, $type->dumpValue($value));
            }
        }
    }

    /**
     * @dataProvider getDataForSanitation
     */
    public function testValidation($locale, $input, $expected, $expectFail = false)
    {
        \Locale::setDefault($locale);

        $type = new Birthday();
        $messageBag = new MessageBag($this->translator);

        $type->validateValue($input, $messageBag);

        if ($expectFail) {
            $this->assertTrue($messageBag->has('error'), sprintf('Assert "%s" is invalid.', $input));
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

        $type = new Birthday();

        if (ctype_digit((string) $expected) || preg_match('/^(\p{N}+)$/u', $expected)) {
            $this->assertEquals($expected, $type->formatOutput($input));
        } else {
            $this->assertEquals($expected, $type->formatOutput(new DateTimeExtended($input)));
        }
    }

    /**
     * @dataProvider getDataForCompare
     */
    public function testCompares($locale, $first, $second, $comparison = null)
    {
        \Locale::setDefault($locale);

        $type = new Birthday();

        $first  = $type->sanitizeString($first);
        $second = $type->sanitizeString($second);

        if ('==' === $comparison) {
            $this->assertTrue($type->isEqual($first, $second));
        } elseif ('!=' === $comparison) {
            $this->assertFalse($type->isEqual($first, $second));
        } elseif ('!>' === $comparison) {
            $this->assertFalse($type->isLower($first, $second));
            $this->assertFalse($type->isHigher($second, $first));
        } else {
            $this->assertTrue($type->isLower($second, $first));
            $this->assertFalse($type->isLower($first, $second));

            $this->assertFalse($type->isHigher($second, $first));
            $this->assertTrue($type->isHigher($first, $second));
        }
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
            array('nl_NL', '04-10-2010 12:00', '', true),

            array('en_US', '04/21/2010', '2010-04-21'),
            array('en_US', '04-21-2010', '2010-04-21'),
            array('en_US', '04/21/10',   '2010-04-21'),
            array('en_US', '12/31/2010', '2010-12-31'),
            array('en_US', '02/29/2012', '2012-02-29'),
            array('en_US', '29/02/2011', '', true),
            array('nl_NL', date_create('tomorrow')->format('Y-m-d'), '', true),

            // Age
            array('nl_NL', '24', 24),
            array('nl_NL', 24, 24),
            array('nl_NL', '200', 200),
            array('nl_NL', 'twenty', '', true),

            array('en_US', '24', 24),
            array('en_US', '24', 24),
            array('en_US', '200', 200),

            array('uz_Arab', '۴', 4),

            // Right-to-left
            array('ar_YE', '٤', 4),
        );
    }

    public static function getDataForFormat()
    {
        return array(
            // $locale, $input, $expected
            array('nl_NL', '2010-10-04', '04-10-2010'),
            array('nl_NL', '2010-05-04', '04-05-2010'),
            array('nl_NL', '1990-05-04', '04-05-1990'),

            array('nl_NL', '24', 24),
            array('nl_NL', 24, 24),

            array('en_US', '2010-04-21', '4/21/2010'),
            array('en_US', '2010-10-21', '10/21/2010'),
            array('en_US', '24', '24'),

            array('uz_Arab', '2010-05-04', '۲۰۱۰-۰۵-۰۴'),
            array('uz_Arab', '4', '۴'),

            // Right-to-left
            array('ar_YE', 4, '٤'),
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

            array('nl_NL', 24, 25, '!='),
            array('nl_NL', 25, 25, '=='),
            array('nl_NL', 25, 25, '=='),

            array('nl_NL', '05-10-2010', 25, '!>'),
            array('nl_NL', 25, '05-10-2010', '!>'),
        );
    }
}
