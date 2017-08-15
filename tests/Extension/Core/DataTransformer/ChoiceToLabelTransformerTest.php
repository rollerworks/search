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
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ArrayChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceGroupView;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceListView;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceView;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToLabelTransformer;

/**
 * @internal
 */
final class ChoiceToLabelTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_transforms_label_to_choice()
    {
        $choiceList = new ArrayChoiceList([null, 1, 2, 3, 4, 5, 6]);
        $choices = new ChoiceListView(
            [
                '0' => new ChoiceView(null, '0', 'unknown'),
                '1' => new ChoiceView(1, '1', 'active'),
                '2' => new ChoiceView(2, '2', 'removed'),
                'Foo' => new ChoiceGroupView('Foo', ['3' => new ChoiceView(3, '3', 'archived')]),
            ],
            [
                '4' => new ChoiceView(4, '4', 'bar'),
                '5' => new ChoiceView(5, '5', 'moo'),
                'Bar' => new ChoiceGroupView('Bar', ['6' => new ChoiceView(6, '6', 'bla')]),
            ]
        );

        $transformer = new ChoiceToLabelTransformer($choiceList, $choices);

        self::assertSame('active', $transformer->transform(1));
        self::assertSame('removed', $transformer->transform(2));
        self::assertSame('archived', $transformer->transform(3));

        self::assertSame('bar', $transformer->transform(4));
        self::assertSame('moo', $transformer->transform(5));
        self::assertSame('bla', $transformer->transform(6));
        self::assertSame('unknown', $transformer->transform(null));
    }

    /**
     * @test
     */
    public function it_reverse_transforms_label_to_choice()
    {
        $choiceList = new ArrayChoiceList([null, 1, 2, 3, 4, $val5 = new \stdClass(), 6]);
        $choices = new ChoiceListView(
            [
                '0' => new ChoiceView(null, '0', 'unknown'),
                '1' => new ChoiceView(1, '1', 'active'),
                '2' => new ChoiceView(2, '2', 'removed'),
                'Foo' => new ChoiceGroupView('Foo', ['3' => new ChoiceView(3, '3', 'archived')]),
            ],
            [
                '4' => new ChoiceView(4, '4', 'bar'),
                '5' => new ChoiceView($val5, '5', 'moo'),
                'Bar' => new ChoiceGroupView('Bar', ['6' => new ChoiceView(6, '6', 'bla')]),
            ]
        );

        $transformer = new ChoiceToLabelTransformer($choiceList, $choices);

        self::assertSame(1, $transformer->reverseTransform('active'));
        self::assertSame(2, $transformer->reverseTransform('removed'));
        self::assertSame(3, $transformer->reverseTransform('archived'));

        self::assertSame(4, $transformer->reverseTransform('bar'));
        self::assertSame($val5, $transformer->reverseTransform('moo'));
        self::assertSame(6, $transformer->reverseTransform('bla'));
        self::assertNull($transformer->reverseTransform('unknown'));
    }
}
