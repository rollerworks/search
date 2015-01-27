<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Test\FieldTypeTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

class DateTimeTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->getFactory()->createField('datetime', 'datetime');
    }

    public function testDifferentTimezonesDateTime()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', array(
            'model_timezone' => 'America/New_York',
            'view_timezone' => 'Pacific/Tahiti',
        ));

        $outputTime = new \DateTime('2010-06-02 03:04:00 Pacific/Tahiti');
        $outputTime->setTimezone(new \DateTimeZone('America/New_York'));

        $this->assertTransformedEquals($field, $outputTime, '2010-06-02T03:04:00-10:00');
    }

    public function testWithSeconds()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', array(
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'with_seconds' => true,
        ));

        $outputTime = new \DateTime('2010-06-02 03:04:05 UTC');
        $this->assertTransformedEquals($field, $outputTime, '2010-06-02T03:04:05Z');
    }

    public function testDifferentPattern()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', array(
            'format' => "MM*yyyy*dd",
        ));

        $outputTime = new \DateTime('2010-06-02');
        $this->assertTransformedEquals($field, $outputTime, '06*2010*02');
    }

    public function testHtml5Pattern()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', array(
            'format' => DateTimeType::HTML5_FORMAT,
        ));

        $outputTime = new \DateTime('2010-06-02T03:04:05-10:00');
        $this->assertTransformedEquals($field, $outputTime, "2010-06-02T03:04:05-10:00");
    }

    public function testWrongInputFails()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', array(
            'format' => "MM-yyyy-dd",
        ));

        $this->assertTransformedFails($field, '06*2010*02');
    }

    protected function setUp()
    {
        IntlTestHelper::requireIntl($this);

        parent::setUp();
    }

    protected function getTestedType()
    {
        return 'datetime';
    }
}
