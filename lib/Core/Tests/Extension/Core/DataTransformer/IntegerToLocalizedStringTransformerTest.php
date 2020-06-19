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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class IntegerToLocalizedStringTransformerTest extends TestCase
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
            [1234.5, '1235', IntegerToLocalizedStringTransformer::ROUND_CEILING],
            [1234.4, '1235', IntegerToLocalizedStringTransformer::ROUND_CEILING],
            [-1234.5, '-1234', IntegerToLocalizedStringTransformer::ROUND_CEILING],
            [-1234.4, '-1234', IntegerToLocalizedStringTransformer::ROUND_CEILING],
            // towards negative infinity (1.6 -> 1, -1.6 -> -2)
            [1234.5, '1234', IntegerToLocalizedStringTransformer::ROUND_FLOOR],
            [1234.4, '1234', IntegerToLocalizedStringTransformer::ROUND_FLOOR],
            [-1234.5, '-1235', IntegerToLocalizedStringTransformer::ROUND_FLOOR],
            [-1234.4, '-1235', IntegerToLocalizedStringTransformer::ROUND_FLOOR],
            // away from zero (1.6 -> 2, -1.6 -> 2)
            [1234.5, '1235', IntegerToLocalizedStringTransformer::ROUND_UP],
            [1234.4, '1235', IntegerToLocalizedStringTransformer::ROUND_UP],
            [-1234.5, '-1235', IntegerToLocalizedStringTransformer::ROUND_UP],
            [-1234.4, '-1235', IntegerToLocalizedStringTransformer::ROUND_UP],
            // towards zero (1.6 -> 1, -1.6 -> -1)
            [1234.5, '1234', IntegerToLocalizedStringTransformer::ROUND_DOWN],
            [1234.4, '1234', IntegerToLocalizedStringTransformer::ROUND_DOWN],
            [-1234.5, '-1234', IntegerToLocalizedStringTransformer::ROUND_DOWN],
            [-1234.4, '-1234', IntegerToLocalizedStringTransformer::ROUND_DOWN],
            // round halves (.5) to the next even number
            [1234.6, '1235', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1234.5, '1234', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1234.4, '1234', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1233.5, '1234', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1232.5, '1232', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [-1234.6, '-1235', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [-1234.5, '-1234', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [-1234.4, '-1234', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [-1233.5, '-1234', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [-1232.5, '-1232', IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            // round halves (.5) away from zero
            [1234.6, '1235', IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            [1234.5, '1235', IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            [1234.4, '1234', IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            [-1234.6, '-1235', IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            [-1234.5, '-1235', IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            [-1234.4, '-1234', IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            // round halves (.5) towards zero
            [1234.6, '1235', IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1234.5, '1234', IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1234.4, '1234', IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [-1234.6, '-1235', IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [-1234.5, '-1234', IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [-1234.4, '-1234', IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
        ];
    }

    /**
     * @dataProvider transformWithRoundingProvider
     */
    public function testTransformWithRounding($input, $output, $roundingMode)
    {
        $transformer = new IntegerToLocalizedStringTransformer(null, $roundingMode);

        $this->assertEquals($output, $transformer->transform($input));
    }

    public function testReverseTransform()
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('de_AT');

        $transformer = new IntegerToLocalizedStringTransformer();

        $this->assertEquals(1, $transformer->reverseTransform('1'));
        $this->assertEquals(12345, $transformer->reverseTransform('12345'));
    }

    public function testReverseTransformEmpty()
    {
        $transformer = new IntegerToLocalizedStringTransformer();

        $this->assertNull($transformer->reverseTransform(''));
    }

    public function testReverseTransformWithGrouping()
    {
        // Since we test against "de_DE", we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('de_DE');

        $transformer = new IntegerToLocalizedStringTransformer(true);

        $this->assertEquals(1234, $transformer->reverseTransform('1.234'));
        $this->assertEquals(12345, $transformer->reverseTransform('12.345'));
        $this->assertEquals(1234, $transformer->reverseTransform('1234'));
        $this->assertEquals(12345, $transformer->reverseTransform('12345'));
    }

    public function reverseTransformWithRoundingProvider()
    {
        return [
            // towards positive infinity (1.6 -> 2, -1.6 -> -1)
            ['1234,5', 1235, IntegerToLocalizedStringTransformer::ROUND_CEILING],
            ['1234,4', 1235, IntegerToLocalizedStringTransformer::ROUND_CEILING],
            ['-1234,5', -1234, IntegerToLocalizedStringTransformer::ROUND_CEILING],
            ['-1234,4', -1234, IntegerToLocalizedStringTransformer::ROUND_CEILING],
            // towards negative infinity (1.6 -> 1, -1.6 -> -2)
            ['1234,5', 1234, IntegerToLocalizedStringTransformer::ROUND_FLOOR],
            ['1234,4', 1234, IntegerToLocalizedStringTransformer::ROUND_FLOOR],
            ['-1234,5', -1235, IntegerToLocalizedStringTransformer::ROUND_FLOOR],
            ['-1234,4', -1235, IntegerToLocalizedStringTransformer::ROUND_FLOOR],
            // away from zero (1.6 -> 2, -1.6 -> 2)
            ['1234,5', 1235, IntegerToLocalizedStringTransformer::ROUND_UP],
            ['1234,4', 1235, IntegerToLocalizedStringTransformer::ROUND_UP],
            ['-1234,5', -1235, IntegerToLocalizedStringTransformer::ROUND_UP],
            ['-1234,4', -1235, IntegerToLocalizedStringTransformer::ROUND_UP],
            // towards zero (1.6 -> 1, -1.6 -> -1)
            ['1234,5', 1234, IntegerToLocalizedStringTransformer::ROUND_DOWN],
            ['1234,4', 1234, IntegerToLocalizedStringTransformer::ROUND_DOWN],
            ['-1234,5', -1234, IntegerToLocalizedStringTransformer::ROUND_DOWN],
            ['-1234,4', -1234, IntegerToLocalizedStringTransformer::ROUND_DOWN],
            // round halves (.5) to the next even number
            ['1234,6', 1235, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            ['1234,5', 1234, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            ['1234,4', 1234, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            ['1233,5', 1234, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            ['1232,5', 1232, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            ['-1234,6', -1235, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            ['-1234,5', -1234, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            ['-1234,4', -1234, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            ['-1233,5', -1234, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            ['-1232,5', -1232, IntegerToLocalizedStringTransformer::ROUND_HALF_EVEN],
            // round halves (.5) away from zero
            ['1234,6', 1235, IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            ['1234,5', 1235, IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            ['1234,4', 1234, IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            ['-1234,6', -1235, IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            ['-1234,5', -1235, IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            ['-1234,4', -1234, IntegerToLocalizedStringTransformer::ROUND_HALF_UP],
            // round halves (.5) towards zero
            ['1234,6', 1235, IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            ['1234,5', 1234, IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            ['1234,4', 1234, IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            ['-1234,6', -1235, IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            ['-1234,5', -1234, IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
            ['-1234,4', -1234, IntegerToLocalizedStringTransformer::ROUND_HALF_DOWN],
        ];
    }

    /**
     * @dataProvider reverseTransformWithRoundingProvider
     */
    public function testReverseTransformWithRounding($input, $output, $roundingMode)
    {
        $transformer = new IntegerToLocalizedStringTransformer(null, $roundingMode);

        $this->assertEquals($output, $transformer->reverseTransform($input));
    }

    public function testReverseTransformExpectsString()
    {
        $transformer = new IntegerToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(1);
    }

    public function testReverseTransformExpectsValidNumber()
    {
        $transformer = new IntegerToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    /**
     * @dataProvider floatNumberProvider
     */
    public function testReverseTransformExpectsInteger($number, $locale)
    {
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault($locale);

        $transformer = new IntegerToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform($number);
    }

    public function floatNumberProvider()
    {
        return [
            ['12345.912', 'en'],
            ['1.234,5', 'de_DE'],
        ];
    }

    public function testReverseTransformDisallowsNaN()
    {
        $transformer = new IntegerToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    public function testReverseTransformDisallowsNaN2()
    {
        $transformer = new IntegerToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    public function testReverseTransformDisallowsInfinity()
    {
        $transformer = new IntegerToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    public function testReverseTransformDisallowsNegativeInfinity()
    {
        $transformer = new IntegerToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }
}
