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
    protected function setUp()
    {
        parent::setUp();

        \Locale::setDefault('en');
    }

    public function provideTransformations()
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
     */
    public function testTransform($from, string $to)
    {
        $transformer = new NumberToStringTransformer();

        $this->assertSame($to, $transformer->transform($from));
    }

    public function testTransformWithScale()
    {
        $transformer = new NumberToStringTransformer(2);

        $this->assertEquals('1234.50', $transformer->transform(1234.5));
        $this->assertEquals('678.92', $transformer->transform(678.916));
    }

    public function transformWithRoundingProvider()
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
     */
    public function testTransformWithRounding(int $scale, $input, string $output, int $roundingMode)
    {
        $transformer = new NumberToStringTransformer($scale, $roundingMode);

        $this->assertEquals($output, $transformer->transform($input));
    }

    public function testTransformDoesNotRoundIfNoScale()
    {
        $transformer = new NumberToStringTransformer(null, NumberToStringTransformer::ROUND_DOWN);

        $this->assertEquals('1234.547', $transformer->transform(1234.547));
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testReverseTransform($to, $from)
    {
        $transformer = new NumberToStringTransformer();

        $this->assertEquals($to, $transformer->reverseTransform($from));
    }

    public function reverseTransformWithRoundingProvider()
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
     */
    public function testReverseTransformWithRounding(int $scale, string $input, $output, int $roundingMode)
    {
        $transformer = new NumberToStringTransformer($scale, $roundingMode);

        $this->assertEquals($output, $transformer->reverseTransform($input));
    }

    public function testReverseTransformDoesNotRoundIfNoScale()
    {
        $transformer = new NumberToStringTransformer(null, NumberToStringTransformer::ROUND_DOWN);

        $this->assertEquals(1234.547, $transformer->reverseTransform('1234.547'));
    }

    public function testTransformExpectsNumeric()
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('foo');
    }

    public function testReverseTransformExpectsScalar()
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(['1']);
    }

    public function testReverseTransformExpectsValidNumber()
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    /**
     * @see https://github.com/symfony/symfony/issues/3161
     */
    public function testReverseTransformDisallowsNaN()
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    public function testReverseTransformDisallowsNaN2()
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    public function testReverseTransformDisallowsInfinity()
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    public function testReverseTransformDisallowsInfinity2()
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞,123');
    }

    public function testReverseTransformDisallowsNegativeInfinity()
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }

    public function testReverseTransformDisallowsLeadingExtraCharacters()
    {
        $transformer = new NumberToStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo123');
    }

    public function testReverseTransformBigInt()
    {
        $transformer = new NumberToStringTransformer();

        $this->assertEquals(PHP_INT_MAX - 1, (int) $transformer->reverseTransform((string) (PHP_INT_MAX - 1)));
    }

    public function testReverseTransformSmallInt()
    {
        $transformer = new NumberToStringTransformer();

        $this->assertSame(1.0, $transformer->reverseTransform('1.0'));
        $this->assertSame(1, $transformer->reverseTransform('1'));
    }
}
