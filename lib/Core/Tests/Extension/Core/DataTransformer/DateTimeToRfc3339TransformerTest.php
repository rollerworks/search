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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer;

/**
 * @internal
 */
final class DateTimeToRfc3339TransformerTest extends TestCase
{
    protected $dateTime;
    protected $dateTimeWithoutSeconds;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dateTime = new \DateTimeImmutable('2010-02-03 04:05:06 UTC');
        $this->dateTimeWithoutSeconds = new \DateTimeImmutable('2010-02-03 04:05:00 UTC');
    }

    protected function tearDown(): void
    {
        $this->dateTime = null;
        $this->dateTimeWithoutSeconds = null;
    }

    public function allProvider()
    {
        return [
            ['UTC', 'UTC', '2010-02-03 04:05:06 UTC', '2010-02-03T04:05:06Z'],
            ['UTC', 'UTC', null, ''],
            ['America/New_York', 'Asia/Hong_Kong', '2010-02-03 04:05:06 America/New_York', '2010-02-03T17:05:06+08:00'],
            ['America/New_York', 'Asia/Hong_Kong', null, ''],
            ['UTC', 'Asia/Hong_Kong', '2010-02-03 04:05:06 UTC', '2010-02-03T12:05:06+08:00'],
            ['America/New_York', 'UTC', '2010-02-03 04:05:06 America/New_York', '2010-02-03T09:05:06Z'],
        ];
    }

    public function reverseTransformProvider()
    {
        return array_merge($this->allProvider(), [
            // format without seconds, as appears in some browsers
            ['UTC', 'UTC', '2010-02-03 04:05:00 UTC', '2010-02-03T04:05Z'],
            ['America/New_York', 'Asia/Hong_Kong', '2010-02-03 04:05:00 America/New_York', '2010-02-03T17:05+08:00'],
            ['Europe/Amsterdam', 'Europe/Amsterdam', '2013-08-21 10:30:00 Europe/Amsterdam', '2013-08-21T08:30:00Z'],
        ]);
    }

    /**
     * @dataProvider allProvider
     */
    public function testTransform($fromTz, $toTz, $from, $to)
    {
        $transformer = new DateTimeToRfc3339Transformer($fromTz, $toTz);

        self::assertSame($to, $transformer->transform(null !== $from ? new \DateTimeImmutable($from) : null));
    }

    /**
     * @dataProvider allProvider
     */
    public function testTransformDateTimeImmutable($fromTz, $toTz, $from, $to)
    {
        $transformer = new DateTimeToRfc3339Transformer($fromTz, $toTz);

        self::assertSame($to, $transformer->transform(null !== $from ? new \DateTimeImmutable($from) : null));
    }

    public function testTransformRequiresValidDateTime()
    {
        $transformer = new DateTimeToRfc3339Transformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('2010-01-01');
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform($toTz, $fromTz, $to, $from)
    {
        $transformer = new DateTimeToRfc3339Transformer($toTz, $fromTz);

        if (null !== $to) {
            self::assertEquals(new \DateTimeImmutable($to), $transformer->reverseTransform($from));
        } else {
            self::assertSame($to, $transformer->reverseTransform($from));
        }
    }

    public function testReverseTransformRequiresString()
    {
        $transformer = new DateTimeToRfc3339Transformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(12345);
    }

    public function testReverseTransformWithNonExistingDate()
    {
        $transformer = new DateTimeToRfc3339Transformer('UTC', 'UTC');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('2010-04-31T04:05Z');
    }

    public function testReverseTransformExpectsValidDateString()
    {
        $transformer = new DateTimeToRfc3339Transformer('UTC', 'UTC');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('2010-2010-2010');
    }
}
