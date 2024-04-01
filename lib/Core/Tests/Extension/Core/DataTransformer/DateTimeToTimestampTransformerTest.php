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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToTimestampTransformer;
use Rollerworks\Component\Search\Tests\assertDateTimeEqualsTrait;

/**
 * @internal
 */
final class DateTimeToTimestampTransformerTest extends TestCase
{
    use assertDateTimeEqualsTrait;

    /** @test */
    public function transform(): void
    {
        $transformer = new DateTimeToTimestampTransformer('UTC', 'UTC');

        $input = new \DateTimeImmutable('2010-02-03 04:05:06 UTC');
        $output = $input->format('U');

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function transform_empty(): void
    {
        $transformer = new DateTimeToTimestampTransformer();

        self::assertNull($transformer->transform(null));
    }

    /** @test */
    public function transform_with_different_timezones(): void
    {
        $transformer = new DateTimeToTimestampTransformer('Asia/Hong_Kong', 'America/New_York');

        $input = new \DateTimeImmutable('2010-02-03 04:05:06 America/New_York');
        $output = $input->format('U');
        $input = $input->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function transform_from_different_timezone(): void
    {
        $transformer = new DateTimeToTimestampTransformer('Asia/Hong_Kong', 'UTC');

        $input = new \DateTimeImmutable('2010-02-03 04:05:06 Asia/Hong_Kong');

        $dateTime = clone $input;
        $dateTime = $dateTime->setTimezone(new \DateTimeZone('UTC'));
        $output = $dateTime->format('U');

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function transform_date_time_immutable(): void
    {
        $transformer = new DateTimeToTimestampTransformer('Asia/Hong_Kong', 'America/New_York');

        $input = new \DateTimeImmutable('2010-02-03 04:05:06 America/New_York');
        $output = $input->format('U');
        $input = $input->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function transform_expects_date_time(): void
    {
        $transformer = new DateTimeToTimestampTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('1234');
    }

    /** @test */
    public function reverse_transform(): void
    {
        $reverseTransformer = new DateTimeToTimestampTransformer('UTC', 'UTC');

        $output = new \DateTimeImmutable('2010-02-03 04:05:06 UTC');
        $input = $output->format('U');

        self::assertDateTimeEquals($output, $reverseTransformer->reverseTransform($input));
    }

    /** @test */
    public function reverse_transform_empty(): void
    {
        $reverseTransformer = new DateTimeToTimestampTransformer();

        self::assertNull($reverseTransformer->reverseTransform(null));
    }

    /** @test */
    public function reverse_transform_with_different_timezones(): void
    {
        $reverseTransformer = new DateTimeToTimestampTransformer('Asia/Hong_Kong', 'America/New_York');

        $output = new \DateTimeImmutable('2010-02-03 04:05:06 America/New_York');
        $input = $output->format('U');
        $output->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        self::assertDateTimeEquals($output, $reverseTransformer->reverseTransform($input));
    }

    /** @test */
    public function reverse_transform_expects_valid_timestamp(): void
    {
        $reverseTransformer = new DateTimeToTimestampTransformer();

        $this->expectException(TransformationFailedException::class);

        $reverseTransformer->reverseTransform('2010-2010-2010');
    }
}
