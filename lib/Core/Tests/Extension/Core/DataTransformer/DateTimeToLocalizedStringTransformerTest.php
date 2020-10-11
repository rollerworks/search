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

namespace Rollerworks\Component\Search\Tests\Extension\Core\DataTransformer;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Rollerworks\Component\Search\Tests\assertDateTimeEqualsTrait;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class DateTimeToLocalizedStringTransformerTest extends TestCase
{
    use assertDateTimeEqualsTrait;

    protected $dateTime;
    protected $dateTimeWithoutSeconds;

    protected function setUp(): void
    {
        parent::setUp();

        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_AT');

        $this->dateTime = new \DateTimeImmutable('2010-02-03 04:05:06 UTC');
        $this->dateTimeWithoutSeconds = new \DateTimeImmutable('2010-02-03 04:05:00 UTC');
    }

    protected function tearDown(): void
    {
        $this->dateTime = null;
        $this->dateTimeWithoutSeconds = null;
    }

    public static function assertEquals($expected, $actual, string $message = '', float $delta = 0.0, int $maxDepth = 10, bool $canonicalize = false, bool $ignoreCase = false): void
    {
        if ($expected instanceof \DateTimeInterface && $actual instanceof \DateTimeInterface) {
            $expected = $expected->format('c');
            $actual = $actual->format('c');
        }

        parent::assertEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
    }

    public function dataProvider()
    {
        return [
            [\IntlDateFormatter::SHORT, null, null, '03.02.10, 04:05', '2010-02-03 04:05:00 UTC'],
            [\IntlDateFormatter::MEDIUM, null, null, '03.02.2010, 04:05', '2010-02-03 04:05:00 UTC'],
            [\IntlDateFormatter::LONG, null, null, '3. Februar 2010 um 04:05', '2010-02-03 04:05:00 UTC'],
            [\IntlDateFormatter::FULL, null, null, 'Mittwoch, 3. Februar 2010 um 04:05', '2010-02-03 04:05:00 UTC'],
            [\IntlDateFormatter::SHORT, \IntlDateFormatter::NONE, null, '03.02.10', '2010-02-03 00:00:00 UTC'],
            [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE, null, '03.02.2010', '2010-02-03 00:00:00 UTC'],
            [\IntlDateFormatter::LONG, \IntlDateFormatter::NONE, null, '3. Februar 2010', '2010-02-03 00:00:00 UTC'],
            [\IntlDateFormatter::FULL, \IntlDateFormatter::NONE, null, 'Mittwoch, 3. Februar 2010', '2010-02-03 00:00:00 UTC'],
            [null, \IntlDateFormatter::SHORT, null, '03.02.2010, 04:05', '2010-02-03 04:05:00 UTC'],
            [null, \IntlDateFormatter::MEDIUM, null, '03.02.2010, 04:05:06', '2010-02-03 04:05:06 UTC'],
            [null, \IntlDateFormatter::LONG, null, '03.02.2010, 04:05:06 UTC', '2010-02-03 04:05:06 UTC'],
            // see below for extra test case for time format FULL
            [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT, null, '04:05', '1970-01-01 04:05:00 UTC'],
            [\IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM, null, '04:05:06', '1970-01-01 04:05:06 UTC'],
            [\IntlDateFormatter::NONE, \IntlDateFormatter::LONG, null, '04:05:06 UTC', '1970-01-01 04:05:06 UTC'],
            [null, null, 'yyyy-MM-dd HH:mm:00', '2010-02-03 04:05:00', '2010-02-03 04:05:00 UTC'],
            [null, null, 'yyyy-MM-dd HH:mm', '2010-02-03 04:05', '2010-02-03 04:05:00 UTC'],
            [null, null, 'yyyy-MM-dd HH', '2010-02-03 04', '2010-02-03 04:00:00 UTC'],
            [null, null, 'yyyy-MM-dd', '2010-02-03', '2010-02-03 00:00:00 UTC'],
            [null, null, 'yyyy-MM', '2010-02', '2010-02-01 00:00:00 UTC'],
            [null, null, 'yyyy', '2010', '2010-01-01 00:00:00 UTC'],
            [null, null, 'dd-MM-yyyy', '03-02-2010', '2010-02-03 00:00:00 UTC'],
            [null, null, 'HH:mm:ss', '04:05:06', '1970-01-01 04:05:06 UTC'],
            [null, null, 'HH:mm:00', '04:05:00', '1970-01-01 04:05:00 UTC'],
            [null, null, 'HH:mm', '04:05', '1970-01-01 04:05:00 UTC'],
            [null, null, 'HH', '04', '1970-01-01 04:00:00 UTC'],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @test
     */
    public function transform($dateFormat, $timeFormat, $pattern, $output, $input): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer(
            'UTC',
            'UTC',
            $dateFormat,
            $timeFormat,
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $input = new \DateTimeImmutable($input);

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function transform_full_time(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', null, \IntlDateFormatter::FULL);

        self::assertEquals('03.02.2010, 04:05:06 Koordinierte Weltzeit', $transformer->transform($this->dateTime));
    }

    /** @test */
    public function transform_to_different_locale(): void
    {
        \Locale::setDefault('en_US');

        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC');

        self::assertEquals('Feb 3, 2010, 4:05 AM', $transformer->transform($this->dateTime));
    }

    /** @test */
    public function transform_empty(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        self::assertSame('', $transformer->transform(null));
    }

    /** @test */
    public function transform_with_different_timezones(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('America/New_York', 'Asia/Hong_Kong');

        $input = new \DateTimeImmutable('2010-02-03 04:05:06 America/New_York');
        $dateTime = $input->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        self::assertEquals($dateTime->format('d.m.Y, H:i'), $transformer->transform($input));
    }

    /** @test */
    public function reverse_transform_with_no_constructor_parameters(): void
    {
        $tz = \date_default_timezone_get();
        \date_default_timezone_set('Europe/Rome');

        $transformer = new DateTimeToLocalizedStringTransformer();

        $dateTime = new \DateTimeImmutable('2010-02-03 04:05');

        self::assertEquals(
            $dateTime->format('c'),
            $transformer->reverseTransform('03.02.2010, 04:05')->format('c')
        );

        \date_default_timezone_set($tz);
    }

    /** @test */
    public function transform_with_different_patterns(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, \IntlDateFormatter::GREGORIAN, 'MM*yyyy*dd HH|mm|ss');

        self::assertEquals('02*2010*03 04|05|06', $transformer->transform($this->dateTime));
    }

    /** @test */
    public function transform_date_time_immutable_timezones(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('America/New_York', 'Asia/Hong_Kong');

        $input = new \DateTimeImmutable('2010-02-03 04:05:06 America/New_York');

        $dateTime = clone $input;
        $dateTime = $dateTime->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        self::assertEquals($dateTime->format('d.m.Y, H:i'), $transformer->transform($input));
    }

    /** @test */
    public function transform_requires_valid_date_time(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('2010-01-01');
    }

    /** @test */
    public function transform_wraps_intl_errors(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform(1.5);
    }

    /**
     * @dataProvider dataProvider
     *
     * @test
     */
    public function reverse_transform($dateFormat, $timeFormat, $pattern, $input, $output): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer(
            'UTC',
            'UTC',
            $dateFormat,
            $timeFormat,
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $output = new \DateTimeImmutable($output);

        self::assertEquals($output, $transformer->reverseTransform($input));
    }

    /** @test */
    public function reverse_transform_full_time(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', null, \IntlDateFormatter::FULL);

        self::assertDateTimeEquals($this->dateTime, $transformer->reverseTransform('03.02.2010, 04:05:06 GMT+00:00'));
    }

    /** @test */
    public function reverse_transform_from_different_locale(): void
    {
        \Locale::setDefault('en_US');

        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC');

        self::assertDateTimeEquals($this->dateTimeWithoutSeconds, $transformer->reverseTransform('Feb 3, 2010, 04:05 AM'));
    }

    /** @test */
    public function reverse_transform_with_different_timezones(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('America/New_York', 'Asia/Hong_Kong');

        $dateTime = new \DateTimeImmutable('2010-02-03 04:05:00 Asia/Hong_Kong');
        $dateTime->setTimezone(new \DateTimeZone('America/New_York'));

        self::assertDateTimeEquals($dateTime, $transformer->reverseTransform('03.02.2010, 04:05'));
    }

    /** @test */
    public function reverse_transform_with_different_patterns(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, \IntlDateFormatter::GREGORIAN, 'MM*yyyy*dd HH|mm|ss');

        self::assertDateTimeEquals($this->dateTime, $transformer->reverseTransform('02*2010*03 04|05|06'));
    }

    /** @test */
    public function reverse_transform_date_only_with_dst_issue(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('Europe/Rome', 'Europe/Rome', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, \IntlDateFormatter::GREGORIAN, 'dd/MM/yyyy');

        self::assertDateTimeEquals(
            new \DateTimeImmutable('1978-05-28', new \DateTimeZone('Europe/Rome')),
            $transformer->reverseTransform('28/05/1978')
        );
    }

    /** @test */
    public function reverse_transform_date_only_with_dst_issue_and_escaped_text(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('Europe/Rome', 'Europe/Rome', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, \IntlDateFormatter::GREGORIAN, "'day': dd 'month': MM 'year': yyyy");

        self::assertDateTimeEquals(
            new \DateTimeImmutable('1978-05-28', new \DateTimeZone('Europe/Rome')),
            $transformer->reverseTransform('day: 28 month: 05 year: 1978')
        );
    }

    /** @test */
    public function reverse_transform_empty(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        self::assertNull($transformer->reverseTransform(''));
    }

    /** @test */
    public function reverse_transform_requires_string(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(12345);
    }

    /** @test */
    public function reverse_transform_wraps_intl_errors(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('12345');
    }

    /** @test */
    public function validate_date_format_option(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        new DateTimeToLocalizedStringTransformer(null, null, 99);
    }

    /** @test */
    public function validate_time_format_option(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        new DateTimeToLocalizedStringTransformer(null, null, null, 99);
    }

    /** @test */
    public function reverse_transform_with_non_existing_date(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', \IntlDateFormatter::SHORT);

        $this->expectException(TransformationFailedException::class);

        self::assertDateTimeEquals($this->dateTimeWithoutSeconds, $transformer->reverseTransform('31.04.10 04:05'));
    }

    /** @test */
    public function reverse_transform_out_of_timestamp_range(): void
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1789-07-14');
    }
}
