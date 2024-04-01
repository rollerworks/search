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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\IntegerToStringTransformer;

/**
 * @internal
 */
final class IntegerToStringTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \Locale::setDefault('en');
    }

    public static function transformWithRoundingProvider(): iterable
    {
        return [
            // towards positive infinity (1.6 -> 2, -1.6 -> -1)
            [1234.5, '1235', IntegerToStringTransformer::ROUND_CEILING],
            [1234.4, '1235', IntegerToStringTransformer::ROUND_CEILING],
            [-1234.5, '-1234', IntegerToStringTransformer::ROUND_CEILING],
            [-1234.4, '-1234', IntegerToStringTransformer::ROUND_CEILING],
            // towards negative infinity (1.6 -> 1, -1.6 -> -2)
            [1234.5, '1234', IntegerToStringTransformer::ROUND_FLOOR],
            [1234.4, '1234', IntegerToStringTransformer::ROUND_FLOOR],
            [-1234.5, '-1235', IntegerToStringTransformer::ROUND_FLOOR],
            [-1234.4, '-1235', IntegerToStringTransformer::ROUND_FLOOR],
            // away from zero (1.6 -> 2, -1.6 -> 2)
            [1234.5, '1235', IntegerToStringTransformer::ROUND_UP],
            [1234.4, '1235', IntegerToStringTransformer::ROUND_UP],
            [-1234.5, '-1235', IntegerToStringTransformer::ROUND_UP],
            [-1234.4, '-1235', IntegerToStringTransformer::ROUND_UP],
            // towards zero (1.6 -> 1, -1.6 -> -1)
            [1234.5, '1234', IntegerToStringTransformer::ROUND_DOWN],
            [1234.4, '1234', IntegerToStringTransformer::ROUND_DOWN],
            [-1234.5, '-1234', IntegerToStringTransformer::ROUND_DOWN],
            [-1234.4, '-1234', IntegerToStringTransformer::ROUND_DOWN],
            // round halves (.5) to the next even number
            [1234.6, '1235', IntegerToStringTransformer::ROUND_HALF_EVEN],
            [1234.5, '1234', IntegerToStringTransformer::ROUND_HALF_EVEN],
            [1234.4, '1234', IntegerToStringTransformer::ROUND_HALF_EVEN],
            [1233.5, '1234', IntegerToStringTransformer::ROUND_HALF_EVEN],
            [1232.5, '1232', IntegerToStringTransformer::ROUND_HALF_EVEN],
            [-1234.6, '-1235', IntegerToStringTransformer::ROUND_HALF_EVEN],
            [-1234.5, '-1234', IntegerToStringTransformer::ROUND_HALF_EVEN],
            [-1234.4, '-1234', IntegerToStringTransformer::ROUND_HALF_EVEN],
            [-1233.5, '-1234', IntegerToStringTransformer::ROUND_HALF_EVEN],
            [-1232.5, '-1232', IntegerToStringTransformer::ROUND_HALF_EVEN],
            // round halves (.5) away from zero
            [1234.6, '1235', IntegerToStringTransformer::ROUND_HALF_UP],
            [1234.5, '1235', IntegerToStringTransformer::ROUND_HALF_UP],
            [1234.4, '1234', IntegerToStringTransformer::ROUND_HALF_UP],
            [-1234.6, '-1235', IntegerToStringTransformer::ROUND_HALF_UP],
            [-1234.5, '-1235', IntegerToStringTransformer::ROUND_HALF_UP],
            [-1234.4, '-1234', IntegerToStringTransformer::ROUND_HALF_UP],
            // round halves (.5) towards zero
            [1234.6, '1235', IntegerToStringTransformer::ROUND_HALF_DOWN],
            [1234.5, '1234', IntegerToStringTransformer::ROUND_HALF_DOWN],
            [1234.4, '1234', IntegerToStringTransformer::ROUND_HALF_DOWN],
            [-1234.6, '-1235', IntegerToStringTransformer::ROUND_HALF_DOWN],
            [-1234.5, '-1234', IntegerToStringTransformer::ROUND_HALF_DOWN],
            [-1234.4, '-1234', IntegerToStringTransformer::ROUND_HALF_DOWN],
        ];
    }

    /**
     * @dataProvider transformWithRoundingProvider
     *
     * @test
     */
    public function transform_with_rounding(float $input, string $output, $roundingMode): void
    {
        $transformer = new IntegerToStringTransformer($roundingMode);

        self::assertEquals($output, $transformer->transform($input));
    }

    /** @test */
    public function reverse_transform(): void
    {
        $transformer = new IntegerToStringTransformer();

        self::assertEquals(1, $transformer->reverseTransform('1'));
        self::assertEquals(1, $transformer->reverseTransform('1.5'));
        self::assertEquals(1234, $transformer->reverseTransform('1234.5'));
        self::assertEquals(12345, $transformer->reverseTransform('12345.912'));
    }

    /** @test */
    public function reverse_transform_empty(): void
    {
        $transformer = new IntegerToStringTransformer();

        self::assertNull($transformer->reverseTransform(''));
    }

    public static function reverseTransformWithRoundingProvider(): iterable
    {
        return [
            // towards positive infinity (1.6 -> 2, -1.6 -> -1)
            ['1234.5', 1235, IntegerToStringTransformer::ROUND_CEILING],
            ['1234.4', 1235, IntegerToStringTransformer::ROUND_CEILING],
            ['-1234.5', -1234, IntegerToStringTransformer::ROUND_CEILING],
            ['-1234.4', -1234, IntegerToStringTransformer::ROUND_CEILING],
            // towards negative infinity (1.6 -> 1, -1.6 -> -2)
            ['1234.5', 1234, IntegerToStringTransformer::ROUND_FLOOR],
            ['1234.4', 1234, IntegerToStringTransformer::ROUND_FLOOR],
            ['-1234.5', -1235, IntegerToStringTransformer::ROUND_FLOOR],
            ['-1234.4', -1235, IntegerToStringTransformer::ROUND_FLOOR],
            // away from zero (1.6 -> 2, -1.6 -> 2)
            ['1234.5', 1235, IntegerToStringTransformer::ROUND_UP],
            ['1234.4', 1235, IntegerToStringTransformer::ROUND_UP],
            ['-1234.5', -1235, IntegerToStringTransformer::ROUND_UP],
            ['-1234.4', -1235, IntegerToStringTransformer::ROUND_UP],
            // towards zero (1.6 -> 1, -1.6 -> -1)
            ['1234.5', 1234, IntegerToStringTransformer::ROUND_DOWN],
            ['1234.4', 1234, IntegerToStringTransformer::ROUND_DOWN],
            ['-1234.5', -1234, IntegerToStringTransformer::ROUND_DOWN],
            ['-1234.4', -1234, IntegerToStringTransformer::ROUND_DOWN],
            // round halves (.5) to the next even number
            ['1234.6', 1235, IntegerToStringTransformer::ROUND_HALF_EVEN],
            ['1234.5', 1234, IntegerToStringTransformer::ROUND_HALF_EVEN],
            ['1234.4', 1234, IntegerToStringTransformer::ROUND_HALF_EVEN],
            ['1233.5', 1234, IntegerToStringTransformer::ROUND_HALF_EVEN],
            ['1232.5', 1232, IntegerToStringTransformer::ROUND_HALF_EVEN],
            ['-1234.6', -1235, IntegerToStringTransformer::ROUND_HALF_EVEN],
            ['-1234.5', -1234, IntegerToStringTransformer::ROUND_HALF_EVEN],
            ['-1234.4', -1234, IntegerToStringTransformer::ROUND_HALF_EVEN],
            ['-1233.5', -1234, IntegerToStringTransformer::ROUND_HALF_EVEN],
            ['-1232.5', -1232, IntegerToStringTransformer::ROUND_HALF_EVEN],
            // round halves (.5) away from zero
            ['1234.6', 1235, IntegerToStringTransformer::ROUND_HALF_UP],
            ['1234.5', 1235, IntegerToStringTransformer::ROUND_HALF_UP],
            ['1234.4', 1234, IntegerToStringTransformer::ROUND_HALF_UP],
            ['-1234.6', -1235, IntegerToStringTransformer::ROUND_HALF_UP],
            ['-1234.5', -1235, IntegerToStringTransformer::ROUND_HALF_UP],
            ['-1234.4', -1234, IntegerToStringTransformer::ROUND_HALF_UP],
            // round halves (.5) towards zero
            ['1234.6', 1235, IntegerToStringTransformer::ROUND_HALF_DOWN],
            ['1234.5', 1234, IntegerToStringTransformer::ROUND_HALF_DOWN],
            ['1234.4', 1234, IntegerToStringTransformer::ROUND_HALF_DOWN],
            ['-1234.6', -1235, IntegerToStringTransformer::ROUND_HALF_DOWN],
            ['-1234.5', -1234, IntegerToStringTransformer::ROUND_HALF_DOWN],
            ['-1234.4', -1234, IntegerToStringTransformer::ROUND_HALF_DOWN],
        ];
    }

    /**
     * @dataProvider reverseTransformWithRoundingProvider
     *
     * @test
     */
    public function reverse_transform_with_rounding(string $input, $output, int $roundingMode): void
    {
        $transformer = new IntegerToStringTransformer($roundingMode);

        self::assertEquals($output, $transformer->reverseTransform($input));
    }

    /** @test */
    public function reverse_transform_expects_scalar(): void
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(['1']);
    }

    /** @test */
    public function reverse_transform_expects_valid_number(): void
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    /** @test */
    public function reverse_transform_disallows_na_n(): void
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    /** @test */
    public function reverse_transform_disallows_na_n2(): void
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    /** @test */
    public function reverse_transform_disallows_infinity(): void
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    /** @test */
    public function reverse_transform_disallows_negative_infinity(): void
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }
}
