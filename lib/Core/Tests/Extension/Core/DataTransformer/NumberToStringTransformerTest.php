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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\NumberToStringTransformer;

/**
 * @internal
 */
final class NumberToStringTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \Locale::setDefault('en');
    }

    public static function provideTransformations(): iterable
    {
        return [
            [null, ''],
            [1, '1'],
            [1.5, '1.5'],
            [1234.5, '1234.5'],
            [12345.912, '12345.912'],
        ];
    }

    /**
     * @dataProvider provideTransformations
     *
     * @test
     */
    public function transform($from, string $to): void
    {
        $transformer = new NumberToStringTransformer();

        self::assertSame($to, $transformer->transform($from));
    }

    /** @test */
    public function transform_with_scale(): void
    {
        $transformer = new NumberToStringTransformer(2);

        self::assertSame('1234.5', $transformer->transform(1234.5));
        self::assertSame('678.92', $transformer->transform(678.916));
    }

    public static function transformWithRoundingProvider(): iterable
    {
        return [
            // towards positive infinity (1.6 -> 2, -1.6 -> -1)
            [0, 1234.5, '1235', NumberToStringTransformer::ROUND_CEILING],
            [0, 1234.4, '1235', NumberToStringTransformer::ROUND_CEILING],
            [0, -1234.5, '-1234', NumberToStringTransformer::ROUND_CEILING],
            [0, -1234.4, '-1234', NumberToStringTransformer::ROUND_CEILING],
            [1, 123.45, '123.5', NumberToStringTransformer::ROUND_CEILING],
            [1, 123.44, '123.5', NumberToStringTransformer::ROUND_CEILING],
            [1, -123.45, '-123.4', NumberToStringTransformer::ROUND_CEILING],
            [1, -123.44, '-123.4', NumberToStringTransformer::ROUND_CEILING],
            // towards negative infinity (1.6 -> 1, -1.6 -> -2)
            [0, 1234.5, '1234', NumberToStringTransformer::ROUND_FLOOR],
            [0, 1234.4, '1234', NumberToStringTransformer::ROUND_FLOOR],
            [0, -1234.5, '-1235', NumberToStringTransformer::ROUND_FLOOR],
            [0, -1234.4, '-1235', NumberToStringTransformer::ROUND_FLOOR],
            [1, 123.45, '123.4', NumberToStringTransformer::ROUND_FLOOR],
            [1, 123.44, '123.4', NumberToStringTransformer::ROUND_FLOOR],
            [1, -123.45, '-123.5', NumberToStringTransformer::ROUND_FLOOR],
            [1, -123.44, '-123.5', NumberToStringTransformer::ROUND_FLOOR],
            // away from zero (1.6 -> 2, -1.6 -> 2)
            [0, 1234.5, '1235', NumberToStringTransformer::ROUND_UP],
            [0, 1234.4, '1235', NumberToStringTransformer::ROUND_UP],
            [0, -1234.5, '-1235', NumberToStringTransformer::ROUND_UP],
            [0, -1234.4, '-1235', NumberToStringTransformer::ROUND_UP],
            [1, 123.45, '123.5', NumberToStringTransformer::ROUND_UP],
            [1, 123.44, '123.5', NumberToStringTransformer::ROUND_UP],
            [1, -123.45, '-123.5', NumberToStringTransformer::ROUND_UP],
            [1, -123.44, '-123.5', NumberToStringTransformer::ROUND_UP],
            // towards zero (1.6 -> 1, -1.6 -> -1)
            [0, 1234.5, '1234', NumberToStringTransformer::ROUND_DOWN],
            [0, 1234.4, '1234', NumberToStringTransformer::ROUND_DOWN],
            [0, -1234.5, '-1234', NumberToStringTransformer::ROUND_DOWN],
            [0, -1234.4, '-1234', NumberToStringTransformer::ROUND_DOWN],
            [1, 123.45, '123.4', NumberToStringTransformer::ROUND_DOWN],
            [1, 123.44, '123.4', NumberToStringTransformer::ROUND_DOWN],
            [1, -123.45, '-123.4', NumberToStringTransformer::ROUND_DOWN],
            [1, -123.44, '-123.4', NumberToStringTransformer::ROUND_DOWN],
            // round halves (.5) to the next even number
            [0, 1234.6, '1235', NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, 1234.5, '1234', NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, 1234.4, '1234', NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, 1233.5, '1234', NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, 1232.5, '1232', NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, -1234.6, '-1235', NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, -1234.5, '-1234', NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, -1234.4, '-1234', NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, -1233.5, '-1234', NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, -1232.5, '-1232', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, 123.46, '123.5', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, 123.45, '123.4', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, 123.44, '123.4', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, 123.35, '123.4', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, 123.25, '123.2', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, -123.46, '-123.5', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, -123.45, '-123.4', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, -123.44, '-123.4', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, -123.35, '-123.4', NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, -123.25, '-123.2', NumberToStringTransformer::ROUND_HALF_EVEN],
            // round halves (.5) away from zero
            [0, 1234.6, '1235', NumberToStringTransformer::ROUND_HALF_UP],
            [0, 1234.5, '1235', NumberToStringTransformer::ROUND_HALF_UP],
            [0, 1234.4, '1234', NumberToStringTransformer::ROUND_HALF_UP],
            [0, -1234.6, '-1235', NumberToStringTransformer::ROUND_HALF_UP],
            [0, -1234.5, '-1235', NumberToStringTransformer::ROUND_HALF_UP],
            [0, -1234.4, '-1234', NumberToStringTransformer::ROUND_HALF_UP],
            [1, 123.46, '123.5', NumberToStringTransformer::ROUND_HALF_UP],
            [1, 123.45, '123.5', NumberToStringTransformer::ROUND_HALF_UP],
            [1, 123.44, '123.4', NumberToStringTransformer::ROUND_HALF_UP],
            [1, -123.46, '-123.5', NumberToStringTransformer::ROUND_HALF_UP],
            [1, -123.45, '-123.5', NumberToStringTransformer::ROUND_HALF_UP],
            [1, -123.44, '-123.4', NumberToStringTransformer::ROUND_HALF_UP],
            // round halves (.5) towards zero
            [0, 1234.6, '1235', NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, 1234.5, '1234', NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, 1234.4, '1234', NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, -1234.6, '-1235', NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, -1234.5, '-1234', NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, -1234.4, '-1234', NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, 123.46, '123.5', NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, 123.45, '123.4', NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, 123.44, '123.4', NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, -123.46, '-123.5', NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, -123.45, '-123.4', NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, -123.44, '-123.4', NumberToStringTransformer::ROUND_HALF_DOWN],
        ];
    }

    /**
     * @dataProvider transformWithRoundingProvider
     *
     * @test
     */
    public function transform_with_rounding(int $scale, $input, string $output, int $roundingMode): void
    {
        $transformer = new NumberToStringTransformer($scale, false, $roundingMode);

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function transform_does_not_round_if_no_scale(): void
    {
        $transformer = new NumberToStringTransformer(null, false, NumberToStringTransformer::ROUND_DOWN);

        self::assertEquals('1234.547', $transformer->transform(1234.547));
    }

    /**
     * @dataProvider provideTransformations
     *
     * @test
     */
    public function reverse_transform($to, $from): void
    {
        $transformer = new NumberToStringTransformer();

        self::assertEquals($to, $transformer->reverseTransform($from));
    }

    public static function reverseTransformWithRoundingProvider(): iterable
    {
        return [
            // towards positive infinity (1.6 -> 2, -1.6 -> -1)
            [0, '1234.5', 1235, NumberToStringTransformer::ROUND_CEILING],
            [0, '1234.4', 1235, NumberToStringTransformer::ROUND_CEILING],
            [0, '-1234.5', -1234, NumberToStringTransformer::ROUND_CEILING],
            [0, '-1234.4', -1234, NumberToStringTransformer::ROUND_CEILING],
            [1, '123.45', 123.5, NumberToStringTransformer::ROUND_CEILING],
            [1, '123.44', 123.5, NumberToStringTransformer::ROUND_CEILING],
            [1, '-123.45', -123.4, NumberToStringTransformer::ROUND_CEILING],
            [1, '-123.44', -123.4, NumberToStringTransformer::ROUND_CEILING],
            // towards negative infinity (1.6 -> 1, -1.6 -> -2)
            [0, '1234.5', 1234, NumberToStringTransformer::ROUND_FLOOR],
            [0, '1234.4', 1234, NumberToStringTransformer::ROUND_FLOOR],
            [0, '-1234.5', -1235, NumberToStringTransformer::ROUND_FLOOR],
            [0, '-1234.4', -1235, NumberToStringTransformer::ROUND_FLOOR],
            [1, '123.45', 123.4, NumberToStringTransformer::ROUND_FLOOR],
            [1, '123.44', 123.4, NumberToStringTransformer::ROUND_FLOOR],
            [1, '-123.45', -123.5, NumberToStringTransformer::ROUND_FLOOR],
            [1, '-123.44', -123.5, NumberToStringTransformer::ROUND_FLOOR],
            // away from zero (1.6 -> 2, -1.6 -> 2)
            [0, '1234.5', 1235, NumberToStringTransformer::ROUND_UP],
            [0, '1234.4', 1235, NumberToStringTransformer::ROUND_UP],
            [0, '-1234.5', -1235, NumberToStringTransformer::ROUND_UP],
            [0, '-1234.4', -1235, NumberToStringTransformer::ROUND_UP],
            [1, '123.45', 123.5, NumberToStringTransformer::ROUND_UP],
            [1, '123.44', 123.5, NumberToStringTransformer::ROUND_UP],
            [1, '-123.45', -123.5, NumberToStringTransformer::ROUND_UP],
            [1, '-123.44', -123.5, NumberToStringTransformer::ROUND_UP],
            // towards zero (1.6 -> 1, -1.6 -> -1)
            [0, '1234.5', 1234, NumberToStringTransformer::ROUND_DOWN],
            [0, '1234.4', 1234, NumberToStringTransformer::ROUND_DOWN],
            [0, '-1234.5', -1234, NumberToStringTransformer::ROUND_DOWN],
            [0, '-1234.4', -1234, NumberToStringTransformer::ROUND_DOWN],
            [1, '123.45', 123.4, NumberToStringTransformer::ROUND_DOWN],
            [1, '123.44', 123.4, NumberToStringTransformer::ROUND_DOWN],
            [1, '-123.45', -123.4, NumberToStringTransformer::ROUND_DOWN],
            [1, '-123.44', -123.4, NumberToStringTransformer::ROUND_DOWN],
            // round halves (.5) to the next even number
            [0, '1234.6', 1235, NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, '1234.5', 1234, NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, '1234.4', 1234, NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, '1233.5', 1234, NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, '1232.5', 1232, NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, '-1234.6', -1235, NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, '-1234.5', -1234, NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, '-1234.4', -1234, NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, '-1233.5', -1234, NumberToStringTransformer::ROUND_HALF_EVEN],
            [0, '-1232.5', -1232, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '123.46', 123.5, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '123.45', 123.4, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '123.44', 123.4, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '123.35', 123.4, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '123.25', 123.2, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '-123.46', -123.5, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '-123.45', -123.4, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '-123.44', -123.4, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '-123.35', -123.4, NumberToStringTransformer::ROUND_HALF_EVEN],
            [1, '-123.25', -123.2, NumberToStringTransformer::ROUND_HALF_EVEN],
            // round halves (.5) away from zero
            [0, '1234.6', 1235, NumberToStringTransformer::ROUND_HALF_UP],
            [0, '1234.5', 1235, NumberToStringTransformer::ROUND_HALF_UP],
            [0, '1234.4', 1234, NumberToStringTransformer::ROUND_HALF_UP],
            [0, '-1234.6', -1235, NumberToStringTransformer::ROUND_HALF_UP],
            [0, '-1234.5', -1235, NumberToStringTransformer::ROUND_HALF_UP],
            [0, '-1234.4', -1234, NumberToStringTransformer::ROUND_HALF_UP],
            [1, '123.46', 123.5, NumberToStringTransformer::ROUND_HALF_UP],
            [1, '123.45', 123.5, NumberToStringTransformer::ROUND_HALF_UP],
            [1, '123.44', 123.4, NumberToStringTransformer::ROUND_HALF_UP],
            [1, '-123.46', -123.5, NumberToStringTransformer::ROUND_HALF_UP],
            [1, '-123.45', -123.5, NumberToStringTransformer::ROUND_HALF_UP],
            [1, '-123.44', -123.4, NumberToStringTransformer::ROUND_HALF_UP],
            // round halves (.5) towards zero
            [0, '1234.6', 1235, NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, '1234.5', 1234, NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, '1234.4', 1234, NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, '-1234.6', -1235, NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, '-1234.5', -1234, NumberToStringTransformer::ROUND_HALF_DOWN],
            [0, '-1234.4', -1234, NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, '123.46', 123.5, NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, '123.45', 123.4, NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, '123.44', 123.4, NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, '-123.46', -123.5, NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, '-123.45', -123.4, NumberToStringTransformer::ROUND_HALF_DOWN],
            [1, '-123.44', -123.4, NumberToStringTransformer::ROUND_HALF_DOWN],
        ];
    }

    /**
     * @dataProvider reverseTransformWithRoundingProvider
     *
     * @test
     */
    public function reverse_transform_with_rounding(int $scale, string $input, $output, int $roundingMode): void
    {
        $transformer = new NumberToStringTransformer($scale, false, $roundingMode);

        self::assertEquals($output, $transformer->reverseTransform($input));
    }

    /** @test */
    public function reverse_transform_does_not_round_if_no_scale(): void
    {
        $transformer = new NumberToStringTransformer(null, false, NumberToStringTransformer::ROUND_DOWN);

        self::assertEquals(1234.547, $transformer->reverseTransform('1234.547'));
    }

    /** @test */
    public function transform_expects_numeric(): void
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('foo');
    }

    /** @test */
    public function reverse_transform_expects_scalar(): void
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(['1']);
    }

    /** @test */
    public function reverse_transform_expects_valid_number(): void
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    /**
     * @see https://github.com/symfony/symfony/issues/3161
     *
     * @test
     */
    public function reverse_transform_disallows_na_n(): void
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    /** @test */
    public function reverse_transform_disallows_na_n2(): void
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    /** @test */
    public function reverse_transform_disallows_infinity(): void
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    /** @test */
    public function reverse_transform_disallows_infinity2(): void
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞,123');
    }

    /** @test */
    public function reverse_transform_disallows_negative_infinity(): void
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }

    /** @test */
    public function reverse_transform_disallows_leading_extra_characters(): void
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo123');
    }

    /** @test */
    public function reverse_transform_big_int(): void
    {
        $transformer = new NumberToStringTransformer();

        self::assertEquals(\PHP_INT_MAX - 1, (int) $transformer->reverseTransform((string) (\PHP_INT_MAX - 1)));
    }

    /** @test */
    public function reverse_transform_small_int(): void
    {
        $transformer = new NumberToStringTransformer();

        self::assertSame(1.0, $transformer->reverseTransform('1.0'));
        self::assertSame(1, $transformer->reverseTransform('1'));
    }
}
