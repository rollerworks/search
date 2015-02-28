<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Test\FieldTypeTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

class TimeTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->getFactory()->createField('time', 'time');
    }

    private $defaultTimezone;

    protected function setUp()
    {
        IntlTestHelper::requireIntl($this);

        parent::setUp();

        $this->defaultTimezone = date_default_timezone_get();
    }

    protected function tearDown()
    {
        parent::tearDown();

        date_default_timezone_set($this->defaultTimezone);
    }

    public function testTime()
    {
        $field = $this->getFactory()->createField('time', 'time');

        $outputTime = new \DateTime('1970-01-01 03:04:00 UTC');

        $this->assertTransformedEquals($field, $outputTime, '03:04', '03:04');
    }

    public function testTimeWithoutMinutes()
    {
        $field = $this->getFactory()->createField('time', 'time', array('with_minutes' => false));

        $outputTime = new \DateTime('1970-01-01 03:00:00 UTC');

        $this->assertTransformedEquals($field, $outputTime, '03', '03');
    }

    public function testTimeWithSeconds()
    {
        $field = $this->getFactory()->createField('time', 'time', array('with_seconds' => true));

        $outputTime = new \DateTime('1970-01-01 03:04:05 UTC');

        $this->assertTransformedEquals($field, $outputTime, '03:04:05', '03:04:05');
    }

    /**
     * @expectedException \Rollerworks\Component\Search\Exception\InvalidConfigurationException
     */
    public function testInitializeWithSecondsAndWithoutMinutes()
    {
        $this->getFactory()->createField('time', 'time', array(
            'with_minutes' => false,
            'with_seconds' => true,
        ));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testThrowExceptionIfHoursIsInvalid()
    {
        $this->getFactory()->createField('time', 'time', array(
            'hours' => 'bad value',
        ));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testThrowExceptionIfMinutesIsInvalid()
    {
        $this->getFactory()->createField('time', 'time', array(
            'minutes' => 'bad value',
        ));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testThrowExceptionIfSecondsIsInvalid()
    {
        $this->getFactory()->createField('time', 'time', array(
            'seconds' => 'bad value',
        ));
    }

    protected function getTestedType()
    {
        return 'time';
    }
}
