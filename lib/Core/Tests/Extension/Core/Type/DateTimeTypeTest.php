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

use Carbon\CarbonInterval;
use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class DateTimeTypeTest extends SearchIntegrationTestCase
{
    public function testViewTimezoneCanBeTransformedToModelTimezone()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'model_timezone' => 'America/New_York',
            'view_timezone' => 'Pacific/Tahiti',
            'pattern' => 'yyyy-MM-dd\'T\'HH:mm:ssZZZZZ',
        ]);

        $outputTime = new \DateTimeImmutable('2010-06-02 03:04:00 Pacific/Tahiti');
        $outputTime->setTimezone(new \DateTimeZone('America/New_York'));

        FieldTransformationAssertion::assertThat($field)
            ->withInput('2010-06-02T03:04:00-10:00', '2010-06-02T03:04:00-10:00')
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('2010-06-02T03:04:00-10:00', '2010-06-02T03:04:00-10:00');

        self::assertEquals('This value is not a valid datetime.', $field->getOption('invalid_message'));
    }

    public function testPatternCanBeConfigured()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'pattern' => 'MM*yyyy*dd HH:mm',
        ]);

        $outputTime = new \DateTimeImmutable('2010-06-02T13:12:00.000000+0000');

        FieldTransformationAssertion::assertThat($field)
            ->withInput($outputTime->format('m*Y*d H:i'), $outputTime->format('c'))
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('06*2010*02 13:12', '2010-06-02T13:12:00Z');
    }

    public function testTimeFormatCanBeConfigurable()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'time_format' => \IntlDateFormatter::SHORT,
        ]);

        $outputTime = new \DateTimeImmutable('2010-06-02 03:04:00 UTC');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Jun 2, 2010, 3:04 AM', '2010-06-02T03:04:00Z')
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('Jun 2, 2010, 3:04 AM', '2010-06-02T03:04:00Z');
    }

    public function testInvalidInputShouldFailTransformation()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'pattern' => 'MM-yyyy-dd',
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('06*2010*02', '2010-06-02T13:12:00Z')
            ->failsToTransforms();

        FieldTransformationAssertion::assertThat($field)
            ->withInput('06-2010-02', '2010-06*02T13:12:00Z')
            ->failsToTransforms();

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1 week + 2 years')
            ->failsToTransforms();
    }

    public function testViewIsConfiguredProperly()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'date_format' => \IntlDateFormatter::SHORT,
            'time_format' => \IntlDateFormatter::SHORT,
        ]);

        $field->finalizeConfig();
        $fieldView = $field->createView(new FieldSetView());

        self::assertArrayHasKey('timezone', $fieldView->vars);
        self::assertArrayHasKey('pattern', $fieldView->vars);

        self::assertEquals(date_default_timezone_get(), $fieldView->vars['timezone']);
        self::assertEquals('M/d/yy, h:mm a', $fieldView->vars['pattern']);
    }

    public function testIntervalValidInput()
    {
        \Locale::setDefault('nl');

        $field = $this->getFactory()->createField('datetime', DateTimeType::class, ['allow_relative' => true]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1 week 2 jaar', '1 week 2 years')
            ->successfullyTransformsTo(CarbonInterval::fromString('1 week 2 years'))
            ->andReverseTransformsTo('2 jaar 1 week', '2 years 1 week');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('-1 week + 2 jaar', '-1 week + 2 years')
            ->successfullyTransformsTo(CarbonInterval::fromString('1 week + 2 years')->invert())
            ->andReverseTransformsTo('-2 jaar 1 week', '-2 years 1 week');

        self::assertEquals('This value is not a valid datetime or date interval.', $field->getOption('invalid_message'));
    }

    public function testIntervalValidInputInRtl()
    {
        \Locale::setDefault('ar');

        $field = $this->getFactory()->createField('datetime', DateTimeType::class, ['allow_relative' => true]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('3 أيام ساعتين', '3 days 2 hours')
            ->successfullyTransformsTo(CarbonInterval::fromString('3 days 2 hours'))
            ->andReverseTransformsTo('3 أيام ساعتين', '3 days 2 hours');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('-3 أيام ساعتين', '-3 days 2 hours')
            ->successfullyTransformsTo(CarbonInterval::fromString('3 days 2 hours')->invert())
            ->andReverseTransformsTo('-3 أيام ساعتين', '-3 days 2 hours');
    }

    public function testIntervalValidInputWithIsoFormat()
    {
        \Locale::setDefault('nl');

        $field = $this->getFactory()->createField('datetime', DateTimeType::class, ['allow_relative' => true]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1 week + 2 jaar', '2Y1W')
            ->successfullyTransformsTo(CarbonInterval::fromString('1 week + 2 years'))
            ->andReverseTransformsTo('2 jaar 1 week', '2 years 1 week');
    }

    public function testIntervalWithTimeFormatCanBeConfigurable()
    {
        \Locale::setDefault('nl');

        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'time_format' => \IntlDateFormatter::SHORT,
            'allow_relative' => true,
        ]);

        $outputTime = new \DateTimeImmutable('2010-06-02 03:04:00 UTC');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('2 Juni 2010 3:04', '2010-06-02T03:04:00Z')
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('2 jun. 2010 03:04', '2010-06-02T03:04:00Z');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1 week + 2 jaar', '2Y1W')
            ->successfullyTransformsTo(CarbonInterval::fromString('1 week + 2 years'))
            ->andReverseTransformsTo('2 jaar 1 week', '2 years 1 week');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('')
            ->successfullyTransformsTo(null)
            ->andReverseTransformsTo('');
    }

    public function testIntervalWrongInputFails()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, ['allow_relative' => true]);

        FieldTransformationAssertion::assertThat($field)->withInput('twenty')->failsToTransforms();
        FieldTransformationAssertion::assertThat($field)->withInput('twenty')->failsToTransforms();
        FieldTransformationAssertion::assertThat($field)->withInput('6WW')->failsToTransforms();
        FieldTransformationAssertion::assertThat($field)->withInput('2 wee')->failsToTransforms();
        FieldTransformationAssertion::assertThat($field)->withInput('2 Juni 2010 3:04')->failsToTransforms();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // we test against "nl", so we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');
    }
}
