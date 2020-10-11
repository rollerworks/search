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
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ArrayChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory\ChoiceListFactory;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory\PropertyAccessDecorator;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceListView;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
final class PropertyAccessDecoratorTest extends TestCase
{
    /**
     * @var MockObject|null
     */
    private $decoratedFactory;

    /**
     * @var PropertyAccessDecorator|null
     */
    private $factory;

    protected function setUp(): void
    {
        $this->decoratedFactory = $this->createMock(ChoiceListFactory::class);
        $this->factory = new PropertyAccessDecorator($this->decoratedFactory);
    }

    private static function assertChoiceListEquals(ChoiceList $list, $expectedValue = 'value'): void
    {
        self::assertSame([$expectedValue ?? 0 => $expectedValue], $list->getChoices(), 'Choices are not the same');
        self::assertSame([0 => $expectedValue ?? '0'], $list->getStructuredValues(), 'StructuredValues are not the same');
        self::assertSame([$expectedValue ?? 0 => 0], $list->getOriginalKeys(), 'Originals are not the same');
    }

    /** @test */
    public function create_from_choices_property_path(): void
    {
        $choices = [(object) ['property' => 'value']];

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromChoices')
            ->with($choices, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($choices, $callback) {
                return new ArrayChoiceList(\array_map($callback, $choices));
            });

        self::assertChoiceListEquals($this->factory->createListFromChoices($choices, 'property'));
    }

    /** @test */
    public function create_from_choices_property_path_instance(): void
    {
        $choices = [(object) ['property' => 'value']];

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromChoices')
            ->with($choices, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($choices, $callback) {
                return new ArrayChoiceList(\array_map($callback, $choices));
            });

        self::assertChoiceListEquals($this->factory->createListFromChoices($choices, new PropertyPath('property')));
    }

    /** @test */
    public function create_from_loader_property_path(): void
    {
        $loader = $this->createMock(ChoiceLoader::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromLoader')
            ->with($loader, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($loader, $callback) {
                return new ArrayChoiceList([$callback((object) ['property' => 'value'])]);
            });

        self::assertChoiceListEquals($this->factory->createListFromLoader($loader, 'property'));
    }

    // https://github.com/symfony/symfony/issues/5494

    /** @test */
    public function create_from_choices_assume_null_if_value_property_path_unreadable(): void
    {
        $choices = [null];

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromChoices')
            ->with($choices, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($choices, $callback) {
                return new ArrayChoiceList(\array_map($callback, $choices));
            });

        self::assertChoiceListEquals($this->factory->createListFromChoices($choices, 'property'), null);
    }

    // https://github.com/symfony/symfony/issues/5494

    /** @test */
    public function create_from_choice_loader_assume_null_if_value_property_path_unreadable(): void
    {
        $loader = $this->createMock(ChoiceLoader::class);
        $list = null;

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromLoader')
            ->with($loader, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($loader, $callback) {
                return new ArrayChoiceList([$callback(null)]);
            });

        self::assertChoiceListEquals($this->factory->createListFromLoader($loader, 'property'), null);
    }

    /** @test */
    public function create_from_loader_property_path_instance(): void
    {
        $loader = $this->createMock(ChoiceLoader::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createListFromLoader')
            ->with($loader, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($loader, $callback) {
                return new ArrayChoiceList([$callback((object) ['property' => 'value'])]);
            });

        self::assertChoiceListEquals($this->factory->createListFromLoader($loader, new PropertyPath('property')));
    }

    // NB. The tests use the 'choices' of the view to transport the tested component
    // to the tests. Symfony originally returned the value as-is, but RollerworksSearch
    // uses strict return types.

    /** @test */
    public function create_view_preferred_choices_as_property_path(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred) {
                return new ChoiceListView([], [$preferred((object) ['property' => true])]);
            });

        self::assertEquals(
            new ChoiceListView([], [0 => true]),
            $this->factory->createView(
                $list,
                'property'
            )
        );
    }

    /** @test */
    public function create_view_preferred_choices_as_property_path_instance(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred) {
                return new ChoiceListView([], [$preferred((object) ['property' => true])]);
            });

        self::assertEquals(
            new ChoiceListView([], [0 => true]),
            $this->factory->createView(
                $list,
                new PropertyPath('property')
            )
        );
    }

    // https://github.com/symfony/symfony/issues/5494

    /** @test */
    public function create_view_assume_null_if_preferred_choices_property_path_unreadable(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred) {
                return new ChoiceListView([], [$preferred((object) ['category' => null])]);
            });

        self::assertEquals(
            new ChoiceListView([], [0 => false]),
            $this->factory->createView(
                $list,
                'category.preferred'
            )
        );
    }

    /** @test */
    public function create_view_labels_as_property_path(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred, $label) {
                return new ChoiceListView([$label((object) ['property' => 'label'])]);
            });

        self::assertEquals(
            new ChoiceListView(['label']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                'property'
            )
        );
    }

    /** @test */
    public function create_view_labels_as_property_path_instance(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred, $label) {
                return new ChoiceListView([$label((object) ['property' => 'label'])]);
            });

        self::assertEquals(
            new ChoiceListView(['label']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                new PropertyPath('property')
            )
        );
    }

    /** @test */
    public function create_view_indices_as_property_path(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred, $label, $index) {
                return new ChoiceListView([$index((object) ['property' => 'index'])]);
            });

        self::assertEquals(
            new ChoiceListView(['index']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                null, // label
                'property'
            )
        );
    }

    /** @test */
    public function create_view_indices_as_property_path_instance(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred, $label, $index) {
                return new ChoiceListView([$index((object) ['property' => 'index'])]);
            });

        self::assertEquals(
            new ChoiceListView(['index']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                null, // label
                new PropertyPath('property')
            )
        );
    }

    /** @test */
    public function create_view_groups_as_property_path(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, null, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred, $label, $index, $groupBy) {
                return new ChoiceListView([$groupBy((object) ['property' => 'group'])]);
            });

        self::assertEquals(
            new ChoiceListView(['group']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                null, // label
                null, // index
                'property'
            )
        );
    }

    /** @test */
    public function create_view_groups_as_property_path_instance(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, null, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred, $label, $index, $groupBy) {
                return new ChoiceListView([$groupBy((object) ['property' => 'group'])]);
            });

        self::assertEquals(
            new ChoiceListView(['group']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                null, // label
                null, // index
                new PropertyPath('property')
            )
        );
    }

    // https://github.com/symfony/symfony/issues/5494

    /** @test */
    public function create_view_assume_null_if_groups_property_path_unreadable(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, null, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred, $label, $index, $groupBy) {
                return new ChoiceListView([$groupBy((object) ['group' => null])]);
            });

        self::assertEquals(
            new ChoiceListView([null]),
            $this->factory->createView(
                $list,
                null, // preferred choices
                null, // label
                null, // index
                'group.name'
            )
        );
    }

    /** @test */
    public function create_view_attr_as_property_path(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, null, null, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred, $label, $index, $groupBy, $attr) {
                return new ChoiceListView([$attr((object) ['property' => 'attr'])]);
            });

        self::assertEquals(
            new ChoiceListView(['attr']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                null, // label
                null, // index
                null, // groups
                'property'
            )
        );
    }

    /** @test */
    public function create_view_attr_as_property_path_instance(): void
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects(self::once())
            ->method('createView')
            ->with($list, null, null, null, null, self::isInstanceOf('\Closure'))
            ->willReturnCallback(static function ($list, $preferred, $label, $index, $groupBy, $attr) {
                return new ChoiceListView([$attr((object) ['property' => 'attr'])]);
            });

        self::assertEquals(
            new ChoiceListView(['attr']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                null, // label
                null, // index
                null, // groups
                new PropertyPath('property')
            )
        );
    }
}
