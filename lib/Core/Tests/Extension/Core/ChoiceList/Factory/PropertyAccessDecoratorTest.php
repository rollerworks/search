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

    protected function setUp()
    {
        $this->decoratedFactory = $this->createMock(ChoiceListFactory::class);
        $this->factory = new PropertyAccessDecorator($this->decoratedFactory);
    }

    private static function assertChoiceListEquals(ChoiceList $list, $expectedValue = 'value')
    {
        self::assertSame([$expectedValue ?? 0 => $expectedValue], $list->getChoices(), 'Choices are not the same');
        self::assertSame([0 => $expectedValue ?? '0'], $list->getStructuredValues(), 'StructuredValues are not the same');
        self::assertSame([$expectedValue ?? 0 => 0], $list->getOriginalKeys(), 'Originals are not the same');
    }

    public function testCreateFromChoicesPropertyPath()
    {
        $choices = [(object) ['property' => 'value']];

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromChoices')
            ->with($choices, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($choices, $callback) {
                return new ArrayChoiceList(array_map($callback, $choices));
            });

        self::assertChoiceListEquals($this->factory->createListFromChoices($choices, 'property'));
    }

    public function testCreateFromChoicesPropertyPathInstance()
    {
        $choices = [(object) ['property' => 'value']];

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromChoices')
            ->with($choices, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($choices, $callback) {
                return new ArrayChoiceList(array_map($callback, $choices));
            });

        self::assertChoiceListEquals($this->factory->createListFromChoices($choices, new PropertyPath('property')));
    }

    public function testCreateFromLoaderPropertyPath()
    {
        $loader = $this->createMock(ChoiceLoader::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromLoader')
            ->with($loader, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($loader, $callback) {
                return new ArrayChoiceList([$callback((object) ['property' => 'value'])]);
            });

        self::assertChoiceListEquals($this->factory->createListFromLoader($loader, 'property'));
    }

    // https://github.com/symfony/symfony/issues/5494
    public function testCreateFromChoicesAssumeNullIfValuePropertyPathUnreadable()
    {
        $choices = [null];

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromChoices')
            ->with($choices, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($choices, $callback) {
                return new ArrayChoiceList(array_map($callback, $choices));
            });

        self::assertChoiceListEquals($this->factory->createListFromChoices($choices, 'property'), null);
    }

    // https://github.com/symfony/symfony/issues/5494
    public function testCreateFromChoiceLoaderAssumeNullIfValuePropertyPathUnreadable()
    {
        $loader = $this->createMock(ChoiceLoader::class);
        $list = null;

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromLoader')
            ->with($loader, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($loader, $callback) {
                return new ArrayChoiceList([$callback(null)]);
            });

        self::assertChoiceListEquals($this->factory->createListFromLoader($loader, 'property'), null);
    }

    public function testCreateFromLoaderPropertyPathInstance()
    {
        $loader = $this->createMock(ChoiceLoader::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createListFromLoader')
            ->with($loader, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($loader, $callback) {
                return new ArrayChoiceList([$callback((object) ['property' => 'value'])]);
            });

        self::assertChoiceListEquals($this->factory->createListFromLoader($loader, new PropertyPath('property')));
    }

    // NB. The tests use the 'choices' of the view to transport the tested component
    // to the tests. Symfony originally returned the value as-is, but RollerworksSearch
    // uses strict return types.

    public function testCreateViewPreferredChoicesAsPropertyPath()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred) {
                return new ChoiceListView([], [$preferred((object) ['property' => true])]);
            });

        $this->assertEquals(
            new ChoiceListView([], [0 => true]),
            $this->factory->createView(
                $list,
                'property'
            )
        );
    }

    public function testCreateViewPreferredChoicesAsPropertyPathInstance()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred) {
                return new ChoiceListView([], [$preferred((object) ['property' => true])]);
            });

        $this->assertEquals(
            new ChoiceListView([], [0 => true]),
            $this->factory->createView(
                $list,
                new PropertyPath('property')
            )
        );
    }

    // https://github.com/symfony/symfony/issues/5494
    public function testCreateViewAssumeNullIfPreferredChoicesPropertyPathUnreadable()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred) {
                return new ChoiceListView([], [$preferred((object) ['category' => null])]);
            });

        $this->assertEquals(
            new ChoiceListView([], [0 => false]),
            $this->factory->createView(
                $list,
                'category.preferred'
            )
        );
    }

    public function testCreateViewLabelsAsPropertyPath()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred, $label) {
                return new ChoiceListView([$label((object) ['property' => 'label'])]);
            });

        $this->assertEquals(
            new ChoiceListView(['label']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                'property'
            )
        );
    }

    public function testCreateViewLabelsAsPropertyPathInstance()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred, $label) {
                return new ChoiceListView([$label((object) ['property' => 'label'])]);
            });

        $this->assertEquals(
            new ChoiceListView(['label']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                new PropertyPath('property')
            )
        );
    }

    public function testCreateViewIndicesAsPropertyPath()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred, $label, $index) {
                return new ChoiceListView([$index((object) ['property' => 'index'])]);
            });

        $this->assertEquals(
            new ChoiceListView(['index']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                null, // label
                'property'
            )
        );
    }

    public function testCreateViewIndicesAsPropertyPathInstance()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred, $label, $index) {
                return new ChoiceListView([$index((object) ['property' => 'index'])]);
            });

        $this->assertEquals(
            new ChoiceListView(['index']),
            $this->factory->createView(
                $list,
                null, // preferred choices
                null, // label
                new PropertyPath('property')
            )
        );
    }

    public function testCreateViewGroupsAsPropertyPath()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred, $label, $index, $groupBy) {
                return new ChoiceListView([$groupBy((object) ['property' => 'group'])]);
            });

        $this->assertEquals(
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

    public function testCreateViewGroupsAsPropertyPathInstance()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred, $label, $index, $groupBy) {
                return new ChoiceListView([$groupBy((object) ['property' => 'group'])]);
            });

        $this->assertEquals(
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
    public function testCreateViewAssumeNullIfGroupsPropertyPathUnreadable()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred, $label, $index, $groupBy) {
                return new ChoiceListView([$groupBy((object) ['group' => null])]);
            });

        $this->assertEquals(
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

    public function testCreateViewAttrAsPropertyPath()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, null, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred, $label, $index, $groupBy, $attr) {
                return new ChoiceListView([$attr((object) ['property' => 'attr'])]);
            });

        $this->assertEquals(
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

    public function testCreateViewAttrAsPropertyPathInstance()
    {
        $list = $this->createMock(ChoiceList::class);

        $this->decoratedFactory->expects($this->once())
            ->method('createView')
            ->with($list, null, null, null, null, $this->isInstanceOf('\Closure'))
            ->willReturnCallback(function ($list, $preferred, $label, $index, $groupBy, $attr) {
                return new ChoiceListView([$attr((object) ['property' => 'attr'])]);
            });

        $this->assertEquals(
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
