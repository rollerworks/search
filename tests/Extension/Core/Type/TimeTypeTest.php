<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Extension\Core\Type\TimeType;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class TimeTypeTest extends SearchIntegrationTestCase
{
    private $defaultTimezone;

    protected function setUp()
    {
        IntlTestHelper::requireIntl($this, '58.1');

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
        $field = $this->getFactory()->createField('time', TimeType::class);

        $outputTime = new \DateTime('1970-01-01 03:04:00 UTC');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('03:04')
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('03:04');
    }

    public function testCanTransformTimeWithSeconds()
    {
        $field = $this->getFactory()->createField('time', TimeType::class, ['with_seconds' => true]);

        $outputTime = new \DateTime('1970-01-01 03:04:05 UTC');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('03:04:05')
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('03:04:05');
    }

    public function testViewIsConfiguredProperlyWithMinutesAndSeconds()
    {
        $field = $this->getFactory()->createField('datetime', TimeType::class, [
            'with_minutes' => true,
            'with_seconds' => true,
        ]);

        $field->finalizeConfig();
        $fieldView = $field->createView(new FieldSetView());

        self::assertArrayHasKey('pattern', $fieldView->vars);
        self::assertArrayHasKey('with_seconds', $fieldView->vars);
        self::assertArrayHasKey('with_seconds', $fieldView->vars);

        self::assertEquals('H:i:s', $fieldView->vars['pattern']);
    }

    public function testViewIsConfiguredProperlyWithMinutesAndNoSeconds()
    {
        $field = $this->getFactory()->createField('datetime', TimeType::class, [
            'with_minutes' => true,
            'with_seconds' => false,
        ]);

        $field->finalizeConfig();
        $fieldView = $field->createView(new FieldSetView());

        self::assertArrayHasKey('pattern', $fieldView->vars);
        self::assertArrayHasKey('with_seconds', $fieldView->vars);
        self::assertArrayHasKey('with_seconds', $fieldView->vars);

        self::assertEquals('H:i', $fieldView->vars['pattern']);
    }

    public function testViewIsConfiguredProperlyWithNoMinutesAndNoSeconds()
    {
        $field = $this->getFactory()->createField('datetime', TimeType::class, [
            'with_minutes' => false,
            'with_seconds' => false,
        ]);

        $field->finalizeConfig();
        $fieldView = $field->createView(new FieldSetView());

        self::assertArrayHasKey('pattern', $fieldView->vars);
        self::assertArrayHasKey('with_seconds', $fieldView->vars);
        self::assertArrayHasKey('with_seconds', $fieldView->vars);

        self::assertEquals('H', $fieldView->vars['pattern']);
    }

    /**
     * @expectedException \Rollerworks\Component\Search\Exception\InvalidConfigurationException
     */
    public function testCannotInitializeWithSecondsButWithoutMinutes()
    {
        $this->getFactory()->createField('time', TimeType::class, [
            'with_minutes' => false,
            'with_seconds' => true,
        ]);
    }
}
