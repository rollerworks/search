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
use Rollerworks\Component\Search\Test\CarbonIntervalComparator;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\Util\IcuVersion;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class DateTimeTypeTest extends SearchIntegrationTestCase
{
    private static ?CarbonIntervalComparator $violationComparator = null;

    /** @test */
    public function view_timezone_can_be_transformed_to_model_timezone(): void
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
            ->andReverseTransformsTo('2010-06-02T03:04:00-10:00', '2010-06-02T03:04:00-10:00')
        ;

        self::assertEquals('This value is not a valid datetime.', $field->getOption('invalid_message'));
    }

    /** @test */
    public function pattern_can_be_configured(): void
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'pattern' => 'MM*yyyy*dd HH:mm',
        ]);

        $outputTime = new \DateTimeImmutable('2010-06-02T13:12:00.000000+0000');

        FieldTransformationAssertion::assertThat($field)
            ->withInput($outputTime->format('m*Y*d H:i'), $outputTime->format('c'))
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('06*2010*02 13:12', '2010-06-02T13:12:00Z')
        ;
    }

    /** @test */
    public function time_format_can_be_configurable(): void
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
            ->andReverseTransformsTo('Jun 2, 2010, 3:04 AM', '2010-06-02T03:04:00Z')
        ;
    }

    /** @test */
    public function invalid_input_should_fail_transformation(): void
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'pattern' => 'MM-yyyy-dd',
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('06*2010*02', '2010-06-02T13:12:00ZZ')
            ->failsToTransforms()
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('06-2010-40', '2010-06*02T13:12:00Z')
            ->failsToTransforms()
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1 week + 2 years')
            ->failsToTransforms()
        ;
    }

    /** @test */
    public function view_is_configured_properly(): void
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

    /** @test */
    public function interval_valid_input(): void
    {
        \Locale::setDefault('nl');

        $field = $this->getFactory()->createField('datetime', DateTimeType::class, ['allow_relative' => true]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1 week 2 jaar', '1 week 2 years')
            ->successfullyTransformsTo(CarbonInterval::fromString('1 weeks 2 years'))
            ->andReverseTransformsTo('2 jaar 1 week', '2 years 1 week')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('-1 week + 2 jaar', '-1 week + 2 years')
            ->successfullyTransformsTo(CarbonInterval::fromString('1 week + 2 years')->invert())
            ->andReverseTransformsTo('-2 jaar 1 week', '-2 years 1 week')
        ;

        self::assertEquals('This value is not a valid datetime or date interval.', $field->getOption('invalid_message'));
    }

    /** @test */
    public function interval_valid_input_in_rtl(): void
    {
        \Locale::setDefault('ar');

        $field = $this->getFactory()->createField('datetime', DateTimeType::class, ['allow_relative' => true]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('3 أيام ساعتين', '3 days 2 hours')
            ->successfullyTransformsTo(CarbonInterval::fromString('3 days 2 hours'))
            ->andReverseTransformsTo('3 أيام ساعتين', '3 days 2 hours')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('-3 أيام ساعتين', '-3 days 2 hours')
            ->successfullyTransformsTo(CarbonInterval::fromString('3 days 2 hour')->invert())
            ->andReverseTransformsTo('-3 أيام ساعتين', '-3 days 2 hours')
        ;
    }

    /** @test */
    public function interval_valid_input_with_iso_format(): void
    {
        \Locale::setDefault('nl');

        $field = $this->getFactory()->createField('datetime', DateTimeType::class, ['allow_relative' => true]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1 week + 2 jaar', '2Y1W')
            ->successfullyTransformsTo(CarbonInterval::fromString('1 week + 2 years'))
            ->andReverseTransformsTo('2 jaar 1 week', '2 years 1 week')
        ;
    }

    /** @test */
    public function interval_with_time_format_can_be_configurable(): void
    {
        \Locale::setDefault('nl');

        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'time_format' => \IntlDateFormatter::SHORT,
            'allow_relative' => true,
        ]);

        $outputTime = new \DateTimeImmutable('2010-06-02 03:04:00 UTC');

        if (IcuVersion::compare(Intl::getIcuVersion(), '73.2', '>=', 1)) {
            FieldTransformationAssertion::assertThat($field)
                ->withInput('2 Juni 2010, 03:04', '2010-06-02T03:04:00Z')
                ->successfullyTransformsTo($outputTime)
                ->andReverseTransformsTo('2 jun 2010, 03:04', '2010-06-02T03:04:00Z')
            ;

            FieldTransformationAssertion::assertThat($field)
                ->withInput('2 Juni 2010 03:04', '2010-06-02T03:04:00Z')
                ->successfullyTransformsTo($outputTime)
                ->andReverseTransformsTo('2 jun 2010, 03:04', '2010-06-02T03:04:00Z')
            ;
        } else {
            FieldTransformationAssertion::assertThat($field)
                ->withInput('2 Juni 2010 03:04', '2010-06-02T03:04:00Z')
                ->successfullyTransformsTo($outputTime)
                ->andReverseTransformsTo('2 jun. 2010 03:04', '2010-06-02T03:04:00Z')
            ;
        }

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1 week + 2 jaar', '2Y1W')
            ->successfullyTransformsTo(CarbonInterval::fromString('1 week + 2 years'))
            ->andReverseTransformsTo('2 jaar 1 week', '2 years 1 week')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('')
            ->successfullyTransformsTo(null)
            ->andReverseTransformsTo('')
        ;
    }

    /** @test */
    public function interval_wrong_input_fails(): void
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, ['allow_relative' => true]);

        // Technically invalid, but Carbon silently ignores them.
        // FieldTransformationAssertion::assertThat($field)->withInput('twe nty', 'twenty')->failsToTransforms();
        // FieldTransformationAssertion::assertThat($field)->withInput('twenty')->failsToTransforms();
        FieldTransformationAssertion::assertThat($field)->withInput('6WW')->failsToTransforms();
        FieldTransformationAssertion::assertThat($field)->withInput('2 wee')->failsToTransforms();
        FieldTransformationAssertion::assertThat($field)->withInput('2 Juni 2010 3:04')->failsToTransforms();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // we test against "nl", so we need the full implementation
        IntlTestHelper::requireFullIntl($this, '66.1');
    }

    /**
     * @beforeClass
     */
    public static function setUpValidatorComparator(): void
    {
        self::$violationComparator = new CarbonIntervalComparator();

        $comparatorFactory = ComparatorFactory::getInstance();
        $comparatorFactory->register(self::$violationComparator);
    }

    /**
     * @afterClass
     */
    public static function tearDownValidatorComparator(): void
    {
        if (self::$violationComparator === null) {
            return;
        }

        $comparatorFactory = ComparatorFactory::getInstance();
        $comparatorFactory->unregister(self::$violationComparator);
        self::$violationComparator = null;
    }
}
