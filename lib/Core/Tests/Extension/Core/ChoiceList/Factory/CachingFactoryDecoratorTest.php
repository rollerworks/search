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

namespace Rollerworks\Component\Search\Tests\Extension\Core\ChoiceList\Factory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory\CachingFactoryDecorator;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory\ChoiceListFactory;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceListView;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
final class CachingFactoryDecoratorTest extends TestCase
{
    /**
     * @var MockObject|null
     */
    private $decoratedFactory;

    /**
     * @var CachingFactoryDecorator|null
     */
    private $factory;

    protected function setUp(): void
    {
        $this->decoratedFactory = $this->createMock(ChoiceListFactory::class);
        $this->factory = new CachingFactoryDecorator($this->decoratedFactory);
    }

    /** @test */
    public function create_from_choices_empty(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromChoices')
            ->with([])
            ->willReturn($list)
        ;

        self::assertSame($list, $this->factory->createListFromChoices([]));
        self::assertSame($list, $this->factory->createListFromChoices([]));
    }

    /** @test */
    public function create_from_choices_compares_traversable_choices_as_array(): void
    {
        // The top-most traversable is converted to an array
        $choices1 = new \ArrayIterator(['A' => 'a']);
        $choices2 = ['A' => 'a'];
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromChoices')
            ->with($choices2)
            ->willReturn($list)
        ;

        self::assertSame($list, $this->factory->createListFromChoices($choices1));
        self::assertSame($list, $this->factory->createListFromChoices($choices2));
    }

    /** @test */
    public function create_from_choices_flattens_choices(): void
    {
        $choices1 = ['key' => ['A' => 'a']];
        $choices2 = ['A' => 'a'];
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromChoices')
            ->with($choices1)
            ->willReturn($list)
        ;

        self::assertSame($list, $this->factory->createListFromChoices($choices1));
        self::assertSame($list, $this->factory->createListFromChoices($choices2));
    }

    /**
     * @dataProvider provideSameChoices
     *
     * @test
     */
    public function create_from_choices_same_choices($choice1, $choice2): void
    {
        $choices1 = [$choice1];
        $choices2 = [$choice2];
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromChoices')
            ->with($choices1)
            ->willReturn($list)
        ;

        self::assertSame($list, $this->factory->createListFromChoices($choices1));
        self::assertSame($list, $this->factory->createListFromChoices($choices2));
    }

    /**
     * @dataProvider provideDistinguishedChoices
     *
     * @test
     */
    public function create_from_choices_different_choices($choice1, $choice2): void
    {
        $choices1 = [$choice1];
        $choices2 = [$choice2];
        $list1 = $this->createMock(ChoiceList::class);
        $list2 = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createListFromChoices')
            ->withConsecutive(
                [$choices1],
                [$choices2]
            )
            ->willReturnOnConsecutiveCalls($list1, $list2)
        ;

        self::assertSame($list1, $this->factory->createListFromChoices($choices1));
        self::assertSame($list2, $this->factory->createListFromChoices($choices2));
    }

    /** @test */
    public function create_from_choices_same_value_closure(): void
    {
        $choices = [1];
        $list = $this->createMock(ChoiceList::class);
        $closure = static function (): void {
        };

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromChoices')
            ->with($choices, $closure)
            ->willReturn($list)
        ;

        self::assertSame($list, $this->factory->createListFromChoices($choices, $closure));
        self::assertSame($list, $this->factory->createListFromChoices($choices, $closure));
    }

    /** @test */
    public function create_from_choices_different_value_closure(): void
    {
        $choices = [1];
        $list1 = $this->createMock(ChoiceList::class);
        $list2 = $this->createMock(ChoiceList::class);
        $closure1 = static function (): void {};
        $closure2 = static function (): void {};

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createListFromChoices')
            ->withConsecutive([$choices, $closure1], [$choices, $closure2])
            ->willReturnOnConsecutiveCalls($list1, $list2)
        ;

        self::assertSame($list1, $this->factory->createListFromChoices($choices, $closure1));
        self::assertSame($list2, $this->factory->createListFromChoices($choices, $closure2));
    }

    /** @test */
    public function create_from_loader_same_loader(): void
    {
        $loader = $this->createMock(ChoiceLoader::class);
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromLoader')
            ->with($loader)
            ->willReturn($list)
        ;

        self::assertSame($list, $this->factory->createListFromLoader($loader));
        self::assertSame($list, $this->factory->createListFromLoader($loader));
    }

    /** @test */
    public function create_from_loader_different_loader(): void
    {
        $loader1 = $this->createMock(ChoiceLoader::class);
        $loader2 = $this->createMock(ChoiceLoader::class);
        $list1 = $this->createMock(ChoiceList::class);
        $list2 = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createListFromLoader')
            ->withConsecutive(
                [$loader1],
                [$loader2]
            )
            ->willReturnOnConsecutiveCalls($list1, $list2)
        ;

        self::assertSame($list1, $this->factory->createListFromLoader($loader1));
        self::assertSame($list2, $this->factory->createListFromLoader($loader2));
    }

    /** @test */
    public function create_from_loader_same_value_closure(): void
    {
        $loader = $this->createMock(ChoiceLoader::class);
        $list = $this->createMock(ChoiceList::class);
        $closure = static function (): void {
        };

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromLoader')
            ->with($loader, $closure)
            ->willReturn($list)
        ;

        self::assertSame($list, $this->factory->createListFromLoader($loader, $closure));
        self::assertSame($list, $this->factory->createListFromLoader($loader, $closure));
    }

    /** @test */
    public function create_from_loader_different_value_closure(): void
    {
        $loader = $this->createMock(ChoiceLoader::class);
        $list1 = $this->createMock(ChoiceList::class);
        $list2 = $this->createMock(ChoiceList::class);
        $closure1 = static function (): void {
        };
        $closure2 = static function (): void {
        };

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createListFromLoader')
            ->withConsecutive([$loader, $closure1], [$loader, $closure2])
            ->willReturnOnConsecutiveCalls($list1, $list2)
        ;

        self::assertSame($list1, $this->factory->createListFromLoader($loader, $closure1));
        self::assertSame($list2, $this->factory->createListFromLoader($loader, $closure2));
    }

    /** @test */
    public function create_view_same_preferred_choices(): void
    {
        $preferred = ['a'];
        $list = $this->createMock(ChoiceList::class);
        $view = new ChoiceListView();

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, $preferred)
            ->willReturn($view)
        ;

        self::assertSame($view, $this->factory->createView($list, $preferred));
        self::assertSame($view, $this->factory->createView($list, $preferred));
    }

    /** @test */
    public function create_view_different_preferred_choices(): void
    {
        $preferred1 = ['a'];
        $preferred2 = ['b'];
        $list = $this->createMock(ChoiceList::class);
        $view1 = new ChoiceListView();
        $view2 = new ChoiceListView();

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createView')
            ->withConsecutive([$list, $preferred1], [$list, $preferred2])
            ->willReturnOnConsecutiveCalls($view1, $view2)
        ;

        self::assertSame($view1, $this->factory->createView($list, $preferred1));
        self::assertSame($view2, $this->factory->createView($list, $preferred2));
    }

    /** @test */
    public function create_view_same_preferred_choices_closure(): void
    {
        $preferred = static function (): void {
        };
        $list = $this->createMock(ChoiceList::class);
        $view = new ChoiceListView();

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, $preferred)
            ->willReturn($view)
        ;

        self::assertSame($view, $this->factory->createView($list, $preferred));
        self::assertSame($view, $this->factory->createView($list, $preferred));
    }

    /** @test */
    public function create_view_different_preferred_choices_closure(): void
    {
        $preferred1 = static function (): void {};
        $preferred2 = static function (): void {};
        $list = $this->createMock(ChoiceList::class);
        $view1 = new ChoiceListView();
        $view2 = new ChoiceListView();

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createView')
            ->withConsecutive([$list, $preferred1], [$list, $preferred2])
            ->willReturnOnConsecutiveCalls($view1, $view2)
        ;

        self::assertSame($view1, $this->factory->createView($list, $preferred1));
        self::assertSame($view2, $this->factory->createView($list, $preferred2));
    }

    /** @test */
    public function create_view_same_label_closure(): void
    {
        $labels = static function (): void {
        };
        $list = $this->createMock(ChoiceList::class);
        $view = new ChoiceListView();

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, $labels)
            ->willReturn($view)
        ;

        self::assertSame($view, $this->factory->createView($list, null, $labels));
        self::assertSame($view, $this->factory->createView($list, null, $labels));
    }

    /** @test */
    public function create_view_different_label_closure(): void
    {
        $labels1 = static function (): void {};
        $labels2 = static function (): void {};
        $list = $this->createMock(ChoiceList::class);
        $view1 = new ChoiceListView();
        $view2 = new ChoiceListView();

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createView')
            ->withConsecutive([$list, null, $labels1], [$list, null, $labels2])
            ->willReturnOnConsecutiveCalls($view1, $view2)
        ;

        self::assertSame($view1, $this->factory->createView($list, null, $labels1));
        self::assertSame($view2, $this->factory->createView($list, null, $labels2));
    }

    /** @test */
    public function create_view_same_index_closure(): void
    {
        $index = static function (): void {
        };
        $list = $this->createMock(ChoiceList::class);
        $view = new ChoiceListView();

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, $index)
            ->willReturn($view)
        ;

        self::assertSame($view, $this->factory->createView($list, null, null, $index));
        self::assertSame($view, $this->factory->createView($list, null, null, $index));
    }

    /** @test */
    public function create_view_different_index_closure(): void
    {
        $index1 = static function (): void {};
        $index2 = static function (): void {};
        $list = $this->createMock(ChoiceList::class);
        $view1 = new ChoiceListView();
        $view2 = new ChoiceListView();

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createView')
            ->withConsecutive([$list, null, null, $index1], [$list, null, null, $index2])
            ->willReturnOnConsecutiveCalls($view1, $view2)
        ;

        self::assertSame($view1, $this->factory->createView($list, null, null, $index1));
        self::assertSame($view2, $this->factory->createView($list, null, null, $index2));
    }

    /** @test */
    public function create_view_same_group_by_closure(): void
    {
        $groupBy = static function (): void {
        };
        $list = $this->createMock(ChoiceList::class);
        $view = new ChoiceListView();

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, null, $groupBy)
            ->willReturn($view)
        ;

        self::assertSame($view, $this->factory->createView($list, null, null, null, $groupBy));
        self::assertSame($view, $this->factory->createView($list, null, null, null, $groupBy));
    }

    /** @test */
    public function create_view_different_group_by_closure(): void
    {
        $groupBy1 = static function (): void {};
        $groupBy2 = static function (): void {};
        $list = $this->createMock(ChoiceList::class);
        $view1 = new ChoiceListView();
        $view2 = new ChoiceListView();

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createView')
            ->withConsecutive([$list, null, null, null, $groupBy1], [$list, null, null, null, $groupBy2])
            ->willReturnOnConsecutiveCalls($view1, $view2)
        ;

        self::assertSame($view1, $this->factory->createView($list, null, null, null, $groupBy1));
        self::assertSame($view2, $this->factory->createView($list, null, null, null, $groupBy2));
    }

    /** @test */
    public function create_view_same_attributes(): void
    {
        $attr = ['class' => 'foobar'];
        $list = $this->createMock(ChoiceList::class);
        $view = new ChoiceListView();

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, null, null, $attr)
            ->willReturn($view)
        ;

        self::assertSame($view, $this->factory->createView($list, null, null, null, null, $attr));
        self::assertSame($view, $this->factory->createView($list, null, null, null, null, $attr));
    }

    /** @test */
    public function create_view_different_attributes(): void
    {
        $attr1 = ['class' => 'foobar1'];
        $attr2 = ['class' => 'foobar2'];
        $list = $this->createMock(ChoiceList::class);
        $view1 = new ChoiceListView();
        $view2 = new ChoiceListView();

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createView')
            ->withConsecutive(
                [$list, null, null, null, null, $attr1],
                [$list, null, null, null, null, $attr2]
            )
            ->willReturnOnConsecutiveCalls($view1, $view2)
        ;

        self::assertSame($view1, $this->factory->createView($list, null, null, null, null, $attr1));
        self::assertSame($view2, $this->factory->createView($list, null, null, null, null, $attr2));
    }

    /** @test */
    public function create_view_same_attributes_closure(): void
    {
        $attr = static function (): void {
        };
        $list = $this->createMock(ChoiceList::class);
        $view = new ChoiceListView();

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, null, null, $attr)
            ->willReturn($view)
        ;

        self::assertSame($view, $this->factory->createView($list, null, null, null, null, $attr));
        self::assertSame($view, $this->factory->createView($list, null, null, null, null, $attr));
    }

    /** @test */
    public function create_view_different_attributes_closure(): void
    {
        $attr1 = static function (): void {};
        $attr2 = static function (): void {};
        $list = $this->createMock(ChoiceList::class);
        $view1 = new ChoiceListView();
        $view2 = new ChoiceListView();

        $this->decoratedFactory->expects(self::exactly(2))
            ->method('createView')
            ->withConsecutive([$list, null, null, null, null, $attr1], [$list, null, null, null, null, $attr2])
            ->willReturnOnConsecutiveCalls($view1, $view2)
        ;

        self::assertSame($view1, $this->factory->createView($list, null, null, null, null, $attr1));
        self::assertSame($view2, $this->factory->createView($list, null, null, null, null, $attr2));
    }

    public function provideSameChoices(): array
    {
        $object = (object) ['foo' => 'bar'];

        return [
            [0, 0],
            ['a', 'a'],
            // https://github.com/symfony/symfony/issues/10409
            [\chr(181) . 'meter', \chr(181) . 'meter'], // UTF-8
            [$object, $object],
        ];
    }

    public function provideDistinguishedChoices(): array
    {
        return [
            [0, false],
            [0, null],
            [0, '0'],
            [0, ''],
            [1, true],
            [1, '1'],
            [1, 'a'],
            ['', false],
            ['', null],
            [false, null],
            // Same properties, but not identical
            [(object) ['foo' => 'bar'], (object) ['foo' => 'bar']],
        ];
    }

    public function provideSameKeyChoices(): array
    {
        // Only test types here that can be used as array keys
        return [
            [0, 0],
            [0, '0'],
            ['a', 'a'],
            [\chr(181) . 'meter', \chr(181) . 'meter'],
        ];
    }

    public function provideDistinguishedKeyChoices(): array
    {
        // Only test types here that can be used as array keys
        return [
            [0, ''],
            [1, 'a'],
            ['', 'a'],
        ];
    }
}
