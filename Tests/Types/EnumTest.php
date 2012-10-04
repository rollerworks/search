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

use Rollerworks\Bundle\RecordFilterBundle\Type\EnumType;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

class EnumTest extends \Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase
{
    /**
     * @dataProvider getDataForSanitation
     */
    public function testSanitize($input, $values, $expected, $translatorDomain = null, $expectFail = false)
    {
        if ($expectFail) {
            return;
        }

        if ($translatorDomain) {
            $type = new EnumType($values, $this->translator, $translatorDomain);
        } else {
            $type = new EnumType($values);
        }

        $this->assertEquals($expected, $type->sanitizeString($input));
    }

    /**
     * @dataProvider getDataForSanitation
     */
    public function testValidation($input, $values, $expected, $translatorDomain = null, $expectFail = false)
    {
        $messageBag = new MessageBag($this->translator);

        if ($translatorDomain) {
            $type = new EnumType($values, $this->translator, $translatorDomain);
        } else {
            $type = new EnumType($values);
        }

        if ($expectFail) {
            $this->assertFalse($type->validateValue($input, $message, $messageBag));

            $this->assertEquals(array('"' . $input . '" is not accepted, only the following are accepted: Cancelled, Confirmed'), $messageBag->get('error'));
        } else {
            $this->assertTrue($type->validateValue($input, $message, $messageBag));
        }
    }

    public static function getDataForSanitation()
    {
        return array(
            // $input, $values $expected, $translatorDomain, $expectFail
            array('Cancelled', array(0 => 'Cancelled', 1 => 'Confirmed'), 0),
            array('cancelled', array(0 => 'Cancelled', 1 => 'Confirmed'), 0),
            array('CANCELLED', array(0 => 'Cancelled', 1 => 'Confirmed'), 0),

            array('Confirmed', array(0 => 'Cancelled', 1 => 'Confirmed'), 1),
            array('confirmed', array(0 => 'Cancelled', 1 => 'Confirmed'), 1),
            array('CONFIRMED', array(0 => 'Cancelled', 1 => 'Confirmed'), 1),

            array('Cancelled', array(0 => 'status.cancel', 1 => 'status.confirmed'), 0, 'record_filter'),
            array('cancelled', array(0 => 'status.cancel', 1 => 'status.confirmed'), 0, 'record_filter'),
            array('CANCELLED', array(0 => 'status.cancel', 1 => 'status.confirmed'), 0, 'record_filter'),

            array('Confirmed', array(0 => 'status.cancel', 1 => 'status.confirmed'), 1, 'record_filter'),
            array('confirmed', array(0 => 'status.cancel', 1 => 'status.confirmed'), 1, 'record_filter'),
            array('CONFIRMED', array(0 => 'status.cancel', 1 => 'status.confirmed'), 1, 'record_filter'),

            array('unknown', array(0 => 'Cancelled', 1 => 'Confirmed'), -1, 'record_filter', true),
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $this->translator->addResource('array', array(
            'status.cancel'    => 'Cancelled',
            'status.confirmed' => 'Confirmed',
        ), 'en', 'record_filter');
    }
}
