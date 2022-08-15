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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Rollerworks\Component\Search\Tests\assertDateTimeEqualsTrait;

/**
 * @internal
 */
final class DateTimeToStringTransformerTest extends TestCase
{
    use assertDateTimeEqualsTrait;

    public function dataProvider()
    {
        return [
            ['Y-m-d H:i:s', '2010-02-03 16:05:06', '2010-02-03 16:05:06 UTC'],
            ['Y-m-d H:i:00', '2010-02-03 16:05:00', '2010-02-03 16:05:00 UTC'],
            ['Y-m-d H:i', '2010-02-03 16:05', '2010-02-03 16:05:00 UTC'],
            ['Y-m-d H', '2010-02-03 16', '2010-02-03 16:00:00 UTC'],
            ['Y-m-d', '2010-02-03', '2010-02-03 00:00:00 UTC'],
            ['Y-m', '2010-12', '2010-12-01 00:00:00 UTC'],
            ['Y', '2010', '2010-01-01 00:00:00 UTC'],
            ['d-m-Y', '03-02-2010', '2010-02-03 00:00:00 UTC'],
            ['H:i:s', '16:05:06', '1970-01-01 16:05:06 UTC'],
            ['H:i:00', '16:05:00', '1970-01-01 16:05:00 UTC'],
            ['H:i', '16:05', '1970-01-01 16:05:00 UTC'],
            ['H', '16', '1970-01-01 16:00:00 UTC'],
            ['Y-z', '2010-33', '2010-02-03 00:00:00 UTC'],

            // different day representations
            ['Y-m-j', '2010-02-3', '2010-02-03 00:00:00 UTC'],
            ['z', '33', '1970-02-03 00:00:00 UTC'],

            // not bijective
            // this will not work as PHP will use actual date to replace missing info
            // and after change of date will lookup for closest Wednesday
            // i.e. value: 2010-02, PHP value: 2010-02-(today i.e. 20), parsed date: 2010-02-24
            // array('Y-m-D', '2010-02-Wed', '2010-02-03 00:00:00 UTC'),
            // array('Y-m-l', '2010-02-Wednesday', '2010-02-03 00:00:00 UTC'),

            // different month representations
            ['Y-n-d', '2010-2-03', '2010-02-03 00:00:00 UTC'],
            ['Y-M-d', '2010-Feb-03', '2010-02-03 00:00:00 UTC'],
            ['Y-F-d', '2010-February-03', '2010-02-03 00:00:00 UTC'],

            // different year representations
            ['y-m-d', '10-02-03', '2010-02-03 00:00:00 UTC'],

            // different time representations
            ['G:i:s', '16:05:06', '1970-01-01 16:05:06 UTC'],
            ['g:i:s a', '4:05:06 pm', '1970-01-01 16:05:06 UTC'],
            ['h:i:s a', '04:05:06 pm', '1970-01-01 16:05:06 UTC'],

            // seconds since Unix
            ['U', '1265213106', '2010-02-03 16:05:06 UTC'],

            ['Y-z', '2010-33', '2010-02-03 00:00:00 UTC'],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @test
     */
    public function transform($format, $output, $input): void
    {
        $transformer = new DateTimeToStringTransformer('UTC', 'UTC', $format);

        $input = new \DateTimeImmutable($input);

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function transform_empty(): void
    {
        $transformer = new DateTimeToStringTransformer();

        self::assertSame('', $transformer->transform(null));
    }

    /** @test */
    public function transform_with_different_timezones(): void
    {
        $transformer = new DateTimeToStringTransformer('Asia/Hong_Kong', 'America/New_York', 'Y-m-d H:i:s');

        $input = new \DateTimeImmutable('2010-02-03 12:05:06 America/New_York');
        $output = $input->format('Y-m-d H:i:s');
        $input->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function transform_date_time_immutable(): void
    {
        $transformer = new DateTimeToStringTransformer('Asia/Hong_Kong', 'America/New_York', 'Y-m-d H:i:s');

        $input = new \DateTimeImmutable('2010-02-03 12:05:06 America/New_York');
        $output = $input->format('Y-m-d H:i:s');
        $input = $input->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function transform_expects_date_time(): void
    {
        $transformer = new DateTimeToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('1234');
    }

    /** @test */
    public function reverse_transform_empty(): void
    {
        $reverseTransformer = new DateTimeToStringTransformer();

        self::assertNull($reverseTransformer->reverseTransform(''));
    }

    /** @test */
    public function reverse_transform_with_different_timezones(): void
    {
        $reverseTransformer = new DateTimeToStringTransformer('America/New_York', 'Asia/Hong_Kong', 'Y-m-d H:i:s');

        $output = new \DateTimeImmutable('2010-02-03 16:05:06 Asia/Hong_Kong');
        $input = $output->format('Y-m-d H:i:s');
        $output->setTimezone(new \DateTimeZone('America/New_York'));

        $this->assertDateTimeEquals($output, $reverseTransformer->reverseTransform($input));
    }

    /** @test */
    public function reverse_transform_expects_string(): void
    {
        $reverseTransformer = new DateTimeToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $reverseTransformer->reverseTransform(1234);
    }

    /** @test */
    public function reverse_transform_expects_valid_date_string(): void
    {
        $reverseTransformer = new DateTimeToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $reverseTransformer->reverseTransform('2010-2010-2010');
    }

    /** @test */
    public function reverse_transform_with_non_existing_date(): void
    {
        $reverseTransformer = new DateTimeToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $reverseTransformer->reverseTransform('2010-04-31');
    }
}
