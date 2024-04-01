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
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ArrayChoiceList;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToValueTransformer;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
final class ChoiceToValueTransformerTest extends TestCase
{
    /**
     * @var ChoiceToValueTransformer|null
     */
    private $transformer;

    /**
     * @var ChoiceToValueTransformer|null
     */
    private $transformerWithNull;

    protected function setUp(): void
    {
        $list = new ArrayChoiceList(['', false, 'X', true]);
        $listWithNull = new ArrayChoiceList(['', false, 'X', null]);

        $this->transformer = new ChoiceToValueTransformer($list);
        $this->transformerWithNull = new ChoiceToValueTransformer($listWithNull);
    }

    protected function tearDown(): void
    {
        $this->transformer = null;
        $this->transformerWithNull = null;
    }

    public static function transformProvider(): iterable
    {
        return [
            // more extensive test set can be found in FormUtilTest
            ['', '', '', '0'],
            [false, '0', false, '1'],
            ['X', 'X', 'X', '2'],
            [true, '1', null, '3'],
        ];
    }

    /**
     * @dataProvider transformProvider
     *
     * @test
     */
    public function transform($in, $out, $inWithNull, $outWithNull): void
    {
        self::assertSame($out, $this->transformer->transform($in));
        self::assertSame($outWithNull, $this->transformerWithNull->transform($inWithNull));
    }

    public static function reverseTransformProvider(): iterable
    {
        return [
            // values are expected to be valid choice keys already and stay
            // the same
            ['', '', '0', ''],
            ['0', false, '1', false],
            ['X', 'X', '2', 'X'],
            ['1', true, '3', null],
        ];
    }

    /**
     * @dataProvider reverseTransformProvider
     *
     * @test
     */
    public function reverse_transform($in, $out, $inWithNull, $outWithNull): void
    {
        self::assertSame($out, $this->transformer->reverseTransform($in));
        self::assertSame($outWithNull, $this->transformerWithNull->reverseTransform($inWithNull));
    }

    public static function reverseTransformExpectsStringOrNullProvider(): iterable
    {
        return [
            [0],
            [true],
            [false],
            [[]],
        ];
    }

    /**
     * @dataProvider reverseTransformExpectsStringOrNullProvider
     *
     * @test
     */
    public function reverse_transform_expects_string_or_null($value): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer->reverseTransform($value);
    }
}
