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

use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
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

    protected function setUp(): void
    {
        IntlTestHelper::requireIntl($this, '58.1');

        parent::setUp();

        $this->defaultTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        date_default_timezone_set($this->defaultTimezone);
    }

    /** @test */
    public function can_transform_time_without_seconds(): void
    {
        $field = $this->getFactory()->createField('time', TimeType::class);

        $outputTime = new \DateTimeImmutable('1970-01-01 03:04:00 UTC');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('03:04')
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('03:04')
        ;
    }

    /** @test */
    public function can_transform_time_with_seconds(): void
    {
        $field = $this->getFactory()->createField('time', TimeType::class, ['with_seconds' => true]);

        $outputTime = new \DateTimeImmutable('1970-01-01 03:04:05 UTC');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('03:04:05')
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('03:04:05')
        ;
    }

    /** @test */
    public function view_is_configured_properly_with_minutes_and_seconds(): void
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

    /** @test */
    public function view_is_configured_properly_with_minutes_and_no_seconds(): void
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

    /** @test */
    public function view_is_configured_properly_with_no_minutes_and_no_seconds(): void
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

    /** @test */
    public function cannot_initialize_with_seconds_but_without_minutes(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->getFactory()->createField('time', TimeType::class, [
            'with_minutes' => false,
            'with_seconds' => true,
        ]);
    }
}
