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

        $this->dateTime = new \DateTime('2010-02-03 04:05:06 UTC');
        $this->dateTimeWithoutSeconds = new \DateTime('2010-02-03 04:05:00 UTC');
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
     */
    public function testTransform($dateFormat, $timeFormat, $pattern, $output, $input)
    {
        $transformer = new DateTimeToLocalizedStringTransformer(
            'UTC',
            'UTC',
            $dateFormat,
            $timeFormat,
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $input = new \DateTime($input);

        self::assertEquals($output, $transformer->transform($input));
    }

    public function testTransformFullTime()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', null, \IntlDateFormatter::FULL);

        self::assertEquals('03.02.2010, 04:05:06 Koordinierte Weltzeit', $transformer->transform($this->dateTime));
    }

    public function testTransformToDifferentLocale()
    {
        \Locale::setDefault('en_US');

        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC');

        self::assertEquals('Feb 3, 2010, 4:05 AM', $transformer->transform($this->dateTime));
    }

    public function testTransformEmpty()
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        self::assertSame('', $transformer->transform(null));
    }

    public function testTransformWithDifferentTimezones()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('America/New_York', 'Asia/Hong_Kong');

        $input = new \DateTime('2010-02-03 04:05:06 America/New_York');

        $dateTime = clone $input;
        $dateTime->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        self::assertEquals($dateTime->format('d.m.Y, H:i'), $transformer->transform($input));
    }

    public function testReverseTransformWithNoConstructorParameters()
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('Europe/Rome');

        $transformer = new DateTimeToLocalizedStringTransformer();

        $dateTime = new \DateTime('2010-02-03 04:05');

        self::assertEquals(
            $dateTime->format('c'),
            $transformer->reverseTransform('03.02.2010, 04:05')->format('c')
        );

        date_default_timezone_set($tz);
    }

    public function testTransformWithDifferentPatterns()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, \IntlDateFormatter::GREGORIAN, 'MM*yyyy*dd HH|mm|ss');

        self::assertEquals('02*2010*03 04|05|06', $transformer->transform($this->dateTime));
    }

    public function testTransformDateTimeImmutableTimezones()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('America/New_York', 'Asia/Hong_Kong');

        $input = new \DateTimeImmutable('2010-02-03 04:05:06 America/New_York');

        $dateTime = clone $input;
        $dateTime = $dateTime->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        self::assertEquals($dateTime->format('d.m.Y, H:i'), $transformer->transform($input));
    }

    public function testTransformRequiresValidDateTime()
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('2010-01-01');
    }

    public function testTransformWrapsIntlErrors()
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform(1.5);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testReverseTransform($dateFormat, $timeFormat, $pattern, $input, $output)
    {
        $transformer = new DateTimeToLocalizedStringTransformer(
            'UTC',
            'UTC',
            $dateFormat,
            $timeFormat,
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $output = new \DateTime($output);

        self::assertEquals($output, $transformer->reverseTransform($input));
    }

    public function testReverseTransformFullTime()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', null, \IntlDateFormatter::FULL);

        self::assertDateTimeEquals($this->dateTime, $transformer->reverseTransform('03.02.2010, 04:05:06 GMT+00:00'));
    }

    public function testReverseTransformFromDifferentLocale()
    {
        \Locale::setDefault('en_US');

        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC');

        self::assertDateTimeEquals($this->dateTimeWithoutSeconds, $transformer->reverseTransform('Feb 3, 2010, 04:05 AM'));
    }

    public function testReverseTransformWithDifferentTimezones()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('America/New_York', 'Asia/Hong_Kong');

        $dateTime = new \DateTime('2010-02-03 04:05:00 Asia/Hong_Kong');
        $dateTime->setTimezone(new \DateTimeZone('America/New_York'));

        self::assertDateTimeEquals($dateTime, $transformer->reverseTransform('03.02.2010, 04:05'));
    }

    public function testReverseTransformWithDifferentPatterns()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, \IntlDateFormatter::GREGORIAN, 'MM*yyyy*dd HH|mm|ss');

        self::assertDateTimeEquals($this->dateTime, $transformer->reverseTransform('02*2010*03 04|05|06'));
    }

    public function testReverseTransformDateOnlyWithDstIssue()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('Europe/Rome', 'Europe/Rome', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, \IntlDateFormatter::GREGORIAN, 'dd/MM/yyyy');

        self::assertDateTimeEquals(
            new \DateTime('1978-05-28', new \DateTimeZone('Europe/Rome')),
            $transformer->reverseTransform('28/05/1978')
        );
    }

    public function testReverseTransformDateOnlyWithDstIssueAndEscapedText()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('Europe/Rome', 'Europe/Rome', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, \IntlDateFormatter::GREGORIAN, "'day': dd 'month': MM 'year': yyyy");

        self::assertDateTimeEquals(
            new \DateTime('1978-05-28', new \DateTimeZone('Europe/Rome')),
            $transformer->reverseTransform('day: 28 month: 05 year: 1978')
        );
    }

    public function testReverseTransformEmpty()
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        self::assertNull($transformer->reverseTransform(''));
    }

    public function testReverseTransformRequiresString()
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(12345);
    }

    public function testReverseTransformWrapsIntlErrors()
    {
        $transformer = new DateTimeToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('12345');
    }

    public function testValidateDateFormatOption()
    {
        $this->expectException(UnexpectedTypeException::class);

        new DateTimeToLocalizedStringTransformer(null, null, 99);
    }

    public function testValidateTimeFormatOption()
    {
        $this->expectException(UnexpectedTypeException::class);

        new DateTimeToLocalizedStringTransformer(null, null, null, 99);
    }

    public function testReverseTransformWithNonExistingDate()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC', \IntlDateFormatter::SHORT);

        $this->expectException(TransformationFailedException::class);

        self::assertDateTimeEquals($this->dateTimeWithoutSeconds, $transformer->reverseTransform('31.04.10 04:05'));
    }

    public function testReverseTransformOutOfTimestampRange()
    {
        $transformer = new DateTimeToLocalizedStringTransformer('UTC', 'UTC');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1789-07-14');
    }
}
