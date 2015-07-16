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
    public function testCanBeCreated()
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

    public function testCanTransformTimeWithoutSeconds()
    {
        $field = $this->getFactory()->createField('time', 'time');

        $outputTime = new \DateTime('1970-01-01 03:04:00 UTC');

        $this->assertTransformedEquals($field, $outputTime, '03:04', '03:04');
    }

    public function testCanTransformTimeWithSeconds()
    {
        $field = $this->getFactory()->createField('time', 'time', ['with_seconds' => true]);

        $outputTime = new \DateTime('1970-01-01 03:04:05 UTC');

        $this->assertTransformedEquals($field, $outputTime, '03:04:05', '03:04:05');
    }

    public function testViewIsConfiguredProperlyWithMinutesAndSeconds()
    {
        $field = $this->getFactory()->createField('datetime', 'time', [
            'with_minutes' => true,
            'with_seconds' => true,
        ]);

        $field->setDataLocked();
        $fieldView = $field->createView();

        $this->assertArrayHasKey('pattern', $fieldView->vars);
        $this->assertArrayHasKey('with_seconds', $fieldView->vars);
        $this->assertArrayHasKey('with_seconds', $fieldView->vars);

        $this->assertEquals('H:i:s', $fieldView->vars['pattern']);
    }

    public function testViewIsConfiguredProperlyWithMinutesAndNoSeconds()
    {
        $field = $this->getFactory()->createField('datetime', 'time', [
            'with_minutes' => true,
            'with_seconds' => false,
        ]);

        $field->setDataLocked();
        $fieldView = $field->createView();

        $this->assertArrayHasKey('pattern', $fieldView->vars);
        $this->assertArrayHasKey('with_seconds', $fieldView->vars);
        $this->assertArrayHasKey('with_seconds', $fieldView->vars);

        $this->assertEquals('H:i', $fieldView->vars['pattern']);
    }

    public function testViewIsConfiguredProperlyWithNoMinutesAndNoSeconds()
    {
        $field = $this->getFactory()->createField('datetime', 'time', [
            'with_minutes' => false,
            'with_seconds' => false,
        ]);

        $field->setDataLocked();
        $fieldView = $field->createView();

        $this->assertArrayHasKey('pattern', $fieldView->vars);
        $this->assertArrayHasKey('with_seconds', $fieldView->vars);
        $this->assertArrayHasKey('with_seconds', $fieldView->vars);

        $this->assertEquals('H', $fieldView->vars['pattern']);
    }

    /**
     * @expectedException \Rollerworks\Component\Search\Exception\InvalidConfigurationException
     */
    public function testCannotInitializeWithSecondsButWithoutMinutes()
    {
        $this->getFactory()->createField('time', 'time', [
            'with_minutes' => false,
            'with_seconds' => true,
        ]);
    }
}
