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

    protected function setUp()
    {
        $list = new ArrayChoiceList(['', false, 'X', true]);
        $listWithNull = new ArrayChoiceList(['', false, 'X', null]);

        $this->transformer = new ChoiceToValueTransformer($list);
        $this->transformerWithNull = new ChoiceToValueTransformer($listWithNull);
    }

    protected function tearDown()
    {
        $this->transformer = null;
        $this->transformerWithNull = null;
    }

    public function transformProvider()
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
     */
    public function testTransform($in, $out, $inWithNull, $outWithNull)
    {
        $this->assertSame($out, $this->transformer->transform($in));
        $this->assertSame($outWithNull, $this->transformerWithNull->transform($inWithNull));
    }

    public function reverseTransformProvider()
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
     */
    public function testReverseTransform($in, $out, $inWithNull, $outWithNull)
    {
        $this->assertSame($out, $this->transformer->reverseTransform($in));
        $this->assertSame($outWithNull, $this->transformerWithNull->reverseTransform($inWithNull));
    }

    public function reverseTransformExpectsStringOrNullProvider()
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
     */
    public function testReverseTransformExpectsStringOrNull($value)
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer->reverseTransform($value);
    }
}
