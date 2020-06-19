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

    public function transformWithRoundingProvider()
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
     */
    public function testTransformWithRounding(float $input, string $output, $roundingMode)
    {
        $transformer = new IntegerToStringTransformer($roundingMode);

        $this->assertEquals($output, $transformer->transform($input));
    }

    public function testReverseTransform()
    {
        $transformer = new IntegerToStringTransformer();

        $this->assertEquals(1, $transformer->reverseTransform('1'));
        $this->assertEquals(1, $transformer->reverseTransform('1.5'));
        $this->assertEquals(1234, $transformer->reverseTransform('1234.5'));
        $this->assertEquals(12345, $transformer->reverseTransform('12345.912'));
    }

    public function testReverseTransformEmpty()
    {
        $transformer = new IntegerToStringTransformer();

        $this->assertNull($transformer->reverseTransform(''));
    }

    public function reverseTransformWithRoundingProvider()
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
     */
    public function testReverseTransformWithRounding(string $input, $output, int $roundingMode)
    {
        $transformer = new IntegerToStringTransformer($roundingMode);

        $this->assertEquals($output, $transformer->reverseTransform($input));
    }

    public function testReverseTransformExpectsScalar()
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(['1']);
    }

    public function testReverseTransformExpectsValidNumber()
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    public function testReverseTransformDisallowsNaN()
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    public function testReverseTransformDisallowsNaN2()
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    public function testReverseTransformDisallowsInfinity()
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    public function testReverseTransformDisallowsNegativeInfinity()
    {
        $transformer = new IntegerToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }
}
