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

use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Test\FieldTypeTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

class DateTimeTypeTest extends FieldTypeTestCase
{
    public function testCanBeCreated()
    {
        $this->getFactory()->createField('datetime', 'datetime');
    }

    public function testViewTimezoneCanBeTransformedToModelTimezone()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', [
            'model_timezone' => 'America/New_York',
            'view_timezone' => 'Pacific/Tahiti',
            'pattern' => DateTimeType::HTML5_FORMAT,
        ]);

        $outputTime = new \DateTime('2010-06-02 03:04:00 Pacific/Tahiti');
        $outputTime->setTimezone(new \DateTimeZone('America/New_York'));

        $this->assertTransformedEquals($field, $outputTime, '2010-06-02T03:04:00-10:00');
    }

    public function testFormatCanBeConfigured()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', [
            'format' => 'MM*yyyy*dd',
        ]);

        $outputTime = new \DateTime('2010-06-02');
        $this->assertTransformedEquals($field, $outputTime, '06*2010*02');
    }

    public function testPatternIsUsedInFormatBc()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', [
            'pattern' => 'MM*yyyy*dd',
        ]);

        $outputTime = new \DateTime('2010-06-02');
        $this->assertTransformedEquals($field, $outputTime, '06*2010*02');
    }

    public function testTimePatternCanBeConfigurable()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', [
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'time_format' => \IntlDateFormatter::SHORT,
        ]);

        $outputTime = new \DateTime('2010-06-02 03:04:00 UTC');
        $this->assertTransformedEquals($field, $outputTime, 'Jun 2, 2010, 3:04 AM');
    }

    public function testInvalidInputShouldFailTransformation()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', [
            'pattern' => 'MM-yyyy-dd',
        ]);

        $this->assertTransformedFails($field, '06*2010*02');
    }

    public function testViewIsConfiguredProperly()
    {
        $field = $this->getFactory()->createField('datetime', 'datetime', [
            'date_format' => \IntlDateFormatter::SHORT,
            'time_format' => \IntlDateFormatter::SHORT,
        ]);

        $field->setDataLocked();
        $fieldView = $field->createView();

        $this->assertArrayHasKey('timezone', $fieldView->vars);
        $this->assertArrayHasKey('format', $fieldView->vars);

        $this->assertEquals(date_default_timezone_get(), $fieldView->vars['timezone']);
        $this->assertEquals('M/d/yy, h:mm a', $fieldView->vars['format']);
    }

    protected function setUp()
    {
        //IntlTestHelper::requireIntl($this);

        parent::setUp();
    }
}
