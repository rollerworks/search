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

use Rollerworks\Bundle\RecordFilterBundle\Type\EnumType;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;

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
        $this->assertEquals($expected, $type->dumpValue($type->sanitizeString($input)));
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

        $type->validateValue($input, $messageBag);

        if ($expectFail) {
            $this->assertEquals(array('"' . $input . '" is not accepted, only the following are accepted: Cancelled, Confirmed'), $messageBag->get('error'));
        } else {
            $this->assertEquals(array(), $messageBag->get('error'), sprintf('Assert "%s" is valid', $input));
        }
    }

    public function testFormat()
    {
        $type = new EnumType(array(0 => 'Cancelled', 1 => 'Confirmed'));

        $this->assertEquals('Cancelled', $type->formatOutput($type->sanitizeString('cancelled')));
        $this->assertEquals('Cancelled', $type->formatOutput($type->sanitizeString('Cancelled')));
        $this->assertEquals('Confirmed', $type->formatOutput($type->sanitizeString('confirmed')));
        $this->assertEquals('Confirmed', $type->formatOutput($type->sanitizeString('Confirmed')));

        // Make sure a none-existent value does not give a php notice
        $this->assertEquals('unknown', $type->formatOutput($type->sanitizeString('unknown')));
    }

    public function testFormatWithTranslator()
    {
        $type = new EnumType(array(0 => 'status.cancel', 1 => 'status.confirmed'), $this->translator, 'record_filter');

        $this->assertEquals('Cancelled', $type->formatOutput($type->sanitizeString('cancelled')));
        $this->assertEquals('Cancelled', $type->formatOutput($type->sanitizeString('Cancelled')));
        $this->assertEquals('Confirmed', $type->formatOutput($type->sanitizeString('confirmed')));
        $this->assertEquals('Confirmed', $type->formatOutput($type->sanitizeString('Confirmed')));

        // Make sure a none-existent value does not give a php notice
        $this->assertEquals('unknown', $type->formatOutput($type->sanitizeString('unknown')));
    }

    public function testEqual()
    {
        $type = new EnumType(array(0 => 'Cancelled', 1 => 'Confirmed'));

        $this->assertTrue($type->isEqual($type->sanitizeString('Cancelled'), $type->sanitizeString('cancelled')));
        $this->assertFalse($type->isEqual($type->sanitizeString('Cancelled'), $type->sanitizeString('Confirmed')));
    }

    public function testMatchRegex()
    {
        $type = new EnumType(array(0 => 'Cancelled', 1 => 'Confirmed', 2 => 'Rejected'));
        $regex = sprintf('#%s#uis', $type->getMatcherRegex());

        $this->assertRegExp($regex, 'Cancelled');
        $this->assertRegExp($regex, 'Confirmed');
        $this->assertRegExp($regex, 'Rejected');
        $this->assertNotRegExp($regex, 'unknown');
    }

    public function testOptimizeField()
    {
        $type = new EnumType(array(0 => 'Cancelled', 1 => 'Confirmed', 2 => 'Rejected'));

        $valuesBag = new FilterValuesBag('status', '', array(new SingleValue('Cancelled')));
        $valuesBagNew = new FilterValuesBag('status', '', array(new SingleValue('Cancelled')));
        $messageBag = new MessageBag($this->translator);

        $this->assertNull($type->optimizeField($valuesBag, $messageBag));
        $this->assertEquals($valuesBag, $valuesBagNew);

        $valuesBag = new FilterValuesBag('status', '', array(new SingleValue('Cancelled'), new SingleValue('Confirmed')));
        $valuesBagNew = new FilterValuesBag('status', '', array(new SingleValue('Cancelled'), new SingleValue('Confirmed')));
        $messageBag = new MessageBag($this->translator);

        $this->assertNull($type->optimizeField($valuesBag, $messageBag));
        $this->assertEquals($valuesBag, $valuesBagNew);

        $messageBag = new MessageBag($this->translator);
        $valuesBag = new FilterValuesBag('status', '', array(new SingleValue('Cancelled'), new SingleValue('Confirmed'), new SingleValue('Rejected')));
        $valuesBagNew = new FilterValuesBag('status', '');

        $this->assertFalse($type->optimizeField($valuesBag, $messageBag));
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
