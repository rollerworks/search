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

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ArrayChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory\DefaultChoiceListFactory;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\LazyChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceGroupView;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceListView;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceView;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
final class DefaultChoiceListFactoryTest extends TestCase
{
    private $obj1;
    private $obj2;
    private $obj3;
    private $obj4;
    private $list;

    /**
     * @var DefaultChoiceListFactory|null
     */
    private $factory;

    public function getValue($object)
    {
        return $object->value;
    }

    public function getScalarValue($choice)
    {
        switch ($choice) {
            case 'a':
                return 'a';
            case 'b':
                return 'b';
            case 'c':
                return '1';
            case 'd':
                return '2';
        }
    }

    public function getLabel($object): string
    {
        return $object->label;
    }

    public function getFormIndex($object)
    {
        return $object->index;
    }

    public function isPreferred($object): bool
    {
        return $this->obj2 === $object || $this->obj3 === $object;
    }

    public function getAttr($object)
    {
        return $object->attr;
    }

    public function getGroup($object): string
    {
        return $this->obj1 === $object || $this->obj2 === $object ? 'Group 1' : 'Group 2';
    }

    public function getGroupAsObject($object): DefaultChoiceListFactoryTest_Castable
    {
        return $this->obj1 === $object || $this->obj2 === $object
            ? new DefaultChoiceListFactoryTest_Castable('Group 1')
            : new DefaultChoiceListFactoryTest_Castable('Group 2');
    }

    protected function setUp(): void
    {
        $this->obj1 = (object) [
            'label' => 'A',
            'index' => 'w',
            'value' => 'a',
            'preferred' => false,
            'group' => 'Group 1',
            'attr' => [],
        ];

        $this->obj2 = (object) [
            'label' => 'B',
            'index' => 'x',
            'value' => 'b',
            'preferred' => true,
            'group' => 'Group 1',
            'attr' => ['attr1' => 'value1'],
        ];

        $this->obj3 = (object) [
            'label' => 'C',
            'index' => 'y',
            'value' => 1,
            'preferred' => true,
            'group' => 'Group 2',
            'attr' => ['attr2' => 'value2'],
        ];

        $this->obj4 = (object) [
            'label' => 'D',
            'index' => 'z',
            'value' => 2,
            'preferred' => false,
            'group' => 'Group 2',
            'attr' => [],
        ];

        $this->factory = new DefaultChoiceListFactory();
        $this->list = new ArrayChoiceList(
            [
                'A' => $this->obj1,
                'B' => $this->obj2,
                'C' => $this->obj3,
                'D' => $this->obj4,
            ]
        );
    }

    public function testCreateFromChoicesEmpty()
    {
        $list = $this->factory->createListFromChoices([]);

        self::assertSame([], $list->getChoices());
        self::assertSame([], $list->getValues());
    }

    public function testCreateFromChoicesFlat()
    {
        $list = $this->factory->createListFromChoices(
            ['A' => $this->obj1, 'B' => $this->obj2, 'C' => $this->obj3, 'D' => $this->obj4]
        );

        $this->assertObjectListWithGeneratedValues($list);
    }

    public function testCreateFromChoicesFlatTraversable()
    {
        $list = $this->factory->createListFromChoices(
            new \ArrayIterator(['A' => $this->obj1, 'B' => $this->obj2, 'C' => $this->obj3, 'D' => $this->obj4])
        );

        $this->assertObjectListWithGeneratedValues($list);
    }

    public function testCreateFromChoicesFlatValuesAsCallable()
    {
        $list = $this->factory->createListFromChoices(
            ['A' => $this->obj1, 'B' => $this->obj2, 'C' => $this->obj3, 'D' => $this->obj4],
            [$this, 'getValue']
        );

        $this->assertObjectListWithCustomValues($list);
    }

    public function testCreateFromChoicesFlatValuesAsClosure()
    {
        $list = $this->factory->createListFromChoices(
            ['A' => $this->obj1, 'B' => $this->obj2, 'C' => $this->obj3, 'D' => $this->obj4],
            function ($object) {
                return $object->value;
            }
        );

        $this->assertObjectListWithCustomValues($list);
    }

    public function testCreateFromChoicesGrouped()
    {
        $list = $this->factory->createListFromChoices(
            [
                'Group 1' => ['A' => $this->obj1, 'B' => $this->obj2],
                'Group 2' => ['C' => $this->obj3, 'D' => $this->obj4],
            ]
        );

        $this->assertObjectListWithGeneratedValues($list);
    }

    public function testCreateFromChoicesGroupedTraversable()
    {
        $list = $this->factory->createListFromChoices(
            new \ArrayIterator([
                    'Group 1' => ['A' => $this->obj1, 'B' => $this->obj2],
                    'Group 2' => ['C' => $this->obj3, 'D' => $this->obj4],
                ])
        );

        $this->assertObjectListWithGeneratedValues($list);
    }

    public function testCreateFromChoicesGroupedValuesAsCallable()
    {
        $list = $this->factory->createListFromChoices(
            [
                'Group 1' => ['A' => $this->obj1, 'B' => $this->obj2],
                'Group 2' => ['C' => $this->obj3, 'D' => $this->obj4],
            ],
            [$this, 'getValue']
        );

        $this->assertObjectListWithCustomValues($list);
    }

    public function testCreateFromChoicesGroupedValuesAsClosure()
    {
        $list = $this->factory->createListFromChoices(
            [
                'Group 1' => ['A' => $this->obj1, 'B' => $this->obj2],
                'Group 2' => ['C' => $this->obj3, 'D' => $this->obj4],
            ],
            function ($object) {
                return $object->value;
            }
        );

        $this->assertObjectListWithCustomValues($list);
    }

    public function testCreateFromLoader()
    {
        $loader = $this->createMock(ChoiceLoader::class);

        $list = $this->factory->createListFromLoader($loader);

        $this->assertEquals(new LazyChoiceList($loader), $list);
    }

    public function testCreateFromLoaderWithValues()
    {
        $loader = $this->createMock(ChoiceLoader::class);

        $value = function () {
        };
        $list = $this->factory->createListFromLoader($loader, $value);

        $this->assertEquals(new LazyChoiceList($loader, $value), $list);
    }

    public function testCreateViewFlat()
    {
        $view = $this->factory->createView($this->list);

        $this->assertEquals(
            new ChoiceListView(
                [
                    0 => new ChoiceView($this->obj1, '0', 'A'),
                    1 => new ChoiceView($this->obj2, '1', 'B'),
                    2 => new ChoiceView($this->obj3, '2', 'C'),
                    3 => new ChoiceView($this->obj4, '3', 'D'),
                ], []
            ),
            $view
        );
    }

    public function testCreateViewFlatPreferredChoices()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3]
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatPreferredChoicesEmptyArray()
    {
        $view = $this->factory->createView(
            $this->list,
            []
        );

        $this->assertEquals(
            new ChoiceListView(
                [
                    0 => new ChoiceView($this->obj1, '0', 'A'),
                    1 => new ChoiceView($this->obj2, '1', 'B'),
                    2 => new ChoiceView($this->obj3, '2', 'C'),
                    3 => new ChoiceView($this->obj4, '3', 'D'),
                ], []
            ),
            $view
        );
    }

    public function testCreateViewFlatPreferredChoicesAsCallable()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this, 'isPreferred']
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatPreferredChoicesAsClosure()
    {
        $obj2 = $this->obj2;
        $obj3 = $this->obj3;

        $view = $this->factory->createView(
            $this->list,
            function ($object) use ($obj2, $obj3) {
                return $obj2 === $object || $obj3 === $object;
            }
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatPreferredChoicesClosureReceivesKey()
    {
        $view = $this->factory->createView(
            $this->list,
            function ($object, $key) {
                return 'B' === $key || 'C' === $key;
            }
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatPreferredChoicesClosureReceivesValue()
    {
        $view = $this->factory->createView(
            $this->list,
            function ($object, $key, $value) {
                return '1' === $value || '2' === $value;
            }
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatLabelAsCallable()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            [$this, 'getLabel']
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatLabelAsClosure()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            function ($object) {
                return $object->label;
            }
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatLabelClosureReceivesKey()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            function ($object, $key) {
                return $key;
            }
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatLabelClosureReceivesValue()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            function ($object, $key, $value) {
                switch ($value) {
                    case '0': return 'A';
                    case '1': return 'B';
                    case '2': return 'C';
                    case '3': return 'D';
                }
            }
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatIndexAsCallable()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            [$this, 'getFormIndex']
        );

        $this->assertFlatViewWithCustomIndices($view);
    }

    public function testCreateViewFlatIndexAsClosure()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            function ($object) {
                return $object->index;
            }
        );

        $this->assertFlatViewWithCustomIndices($view);
    }

    public function testCreateViewFlatIndexClosureReceivesKey()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            function ($object, $key) {
                switch ($key) {
                    case 'A': return 'w';
                    case 'B': return 'x';
                    case 'C': return 'y';
                    case 'D': return 'z';
                }
            }
        );

        $this->assertFlatViewWithCustomIndices($view);
    }

    public function testCreateViewFlatIndexClosureReceivesValue()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            function ($object, $key, $value) {
                switch ($value) {
                    case '0': return 'w';
                    case '1': return 'x';
                    case '2': return 'y';
                    case '3': return 'z';
                }
            }
        );

        $this->assertFlatViewWithCustomIndices($view);
    }

    public function testCreateViewFlatGroupByOriginalStructure()
    {
        $list = new ArrayChoiceList([
            'Group 1' => ['A' => $this->obj1, 'B' => $this->obj2],
            'Group 2' => ['C' => $this->obj3, 'D' => $this->obj4],
            'Group empty' => [],
        ]);

        $view = $this->factory->createView(
            $list,
            [$this->obj2, $this->obj3]
        );

        $this->assertGroupedView($view);
    }

    public function testCreateViewFlatGroupByEmpty()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            null // ignored
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatGroupByAsCallable()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            [$this, 'getGroup']
        );

        $this->assertGroupedView($view);
    }

    public function testCreateViewFlatGroupByObjectThatCanBeCastToString()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            [$this, 'getGroupAsObject']
        );

        $this->assertGroupedView($view);
    }

    public function testCreateViewFlatGroupByAsClosure()
    {
        $obj1 = $this->obj1;
        $obj2 = $this->obj2;

        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            function ($object) use ($obj1, $obj2) {
                return $obj1 === $object || $obj2 === $object ? 'Group 1' : 'Group 2';
            }
        );

        $this->assertGroupedView($view);
    }

    public function testCreateViewFlatGroupByClosureReceivesKey()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            function ($object, $key) {
                return 'A' === $key || 'B' === $key ? 'Group 1' : 'Group 2';
            }
        );

        $this->assertGroupedView($view);
    }

    public function testCreateViewFlatGroupByClosureReceivesValue()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            function ($object, $key, $value) {
                return '0' === $value || '1' === $value ? 'Group 1' : 'Group 2';
            }
        );

        $this->assertGroupedView($view);
    }

    public function testCreateViewFlatAttrAsArray()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            null, // group
            [
                'B' => ['attr1' => 'value1'],
                'C' => ['attr2' => 'value2'],
            ]
        );

        $this->assertFlatViewWithAttr($view);
    }

    public function testCreateViewFlatAttrEmpty()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            null, // group
            []
        );

        $this->assertFlatView($view);
    }

    public function testCreateViewFlatAttrAsCallable()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            null, // group
            [$this, 'getAttr']
        );

        $this->assertFlatViewWithAttr($view);
    }

    public function testCreateViewFlatAttrAsClosure()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            null, // group
            function ($object) {
                return $object->attr;
            }
        );

        $this->assertFlatViewWithAttr($view);
    }

    public function testCreateViewFlatAttrClosureReceivesKey()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            null, // group
            function ($object, $key) {
                switch ($key) {
                    case 'B': return ['attr1' => 'value1'];
                    case 'C': return ['attr2' => 'value2'];
                    default: return [];
                }
            }
        );

        $this->assertFlatViewWithAttr($view);
    }

    public function testCreateViewFlatAttrClosureReceivesValue()
    {
        $view = $this->factory->createView(
            $this->list,
            [$this->obj2, $this->obj3],
            null, // label
            null, // index
            null, // group
            function ($object, $key, $value) {
                switch ($value) {
                    case '1': return ['attr1' => 'value1'];
                    case '2': return ['attr2' => 'value2'];
                    default: return [];
                }
            }
        );

        $this->assertFlatViewWithAttr($view);
    }

    private function assertObjectListWithGeneratedValues(ChoiceList $list)
    {
        self::assertSame(['0', '1', '2', '3'], $list->getValues());

        self::assertSame([
            0 => $this->obj1,
            1 => $this->obj2,
            2 => $this->obj3,
            3 => $this->obj4,
        ], $list->getChoices());

        self::assertSame([
            0 => 'A',
            1 => 'B',
            2 => 'C',
            3 => 'D',
        ], $list->getOriginalKeys());
    }

    private function assertScalarListWithCustomValues(ChoiceList $list)
    {
        self::assertSame(['a', 'b', '1', '2'], $list->getValues());

        self::assertSame(
            [
                'a' => 'a',
                'b' => 'b',
                1 => 'c',
                2 => 'd',
            ],
            $list->getChoices()
        );

        self::assertSame(
            [
                'a' => 'A',
                'b' => 'B',
                1 => 'C',
                2 => 'D',
            ],
            $list->getOriginalKeys()
        );
    }

    private function assertObjectListWithCustomValues(ChoiceList $list)
    {
        self::assertSame(['a', 'b', '1', '2'], $list->getValues());

        self::assertSame(
            [
                'a' => $this->obj1,
                'b' => $this->obj2,
                1 => $this->obj3,
                2 => $this->obj4,
            ],
            $list->getChoices()
        );

        self::assertSame(
            [
                'a' => 'A',
                'b' => 'B',
                1 => 'C',
                2 => 'D',
            ],
            $list->getOriginalKeys()
        );
    }

    private function assertFlatView($view)
    {
        $this->assertEquals(
            new ChoiceListView(
                [
                    0 => new ChoiceView($this->obj1, '0', 'A'),
                    3 => new ChoiceView($this->obj4, '3', 'D'),
                ],
                [
                    1 => new ChoiceView($this->obj2, '1', 'B'),
                    2 => new ChoiceView($this->obj3, '2', 'C'),
                ]
            ),
            $view
        );
    }

    private function assertFlatViewWithCustomIndices($view)
    {
        $this->assertEquals(
            new ChoiceListView(
                [
                    'w' => new ChoiceView($this->obj1, '0', 'A'),
                    'z' => new ChoiceView($this->obj4, '3', 'D'),
                ],
                [
                    'x' => new ChoiceView($this->obj2, '1', 'B'),
                    'y' => new ChoiceView($this->obj3, '2', 'C'),
                ]
            ),
            $view
        );
    }

    private function assertFlatViewWithAttr($view)
    {
        $this->assertEquals(
            new ChoiceListView(
                [
                    0 => new ChoiceView($this->obj1, '0', 'A'),
                    3 => new ChoiceView($this->obj4, '3', 'D'),
                ],
                [
                    1 => new ChoiceView(
                        $this->obj2,
                        '1',
                        'B',
                        ['attr1' => 'value1']
                    ),
                    2 => new ChoiceView(
                        $this->obj3,
                        '2',
                        'C',
                        ['attr2' => 'value2']
                    ),
                ]
            ),
            $view
        );
    }

    private function assertGroupedView($view)
    {
        $this->assertEquals(
            new ChoiceListView(
                [
                    'Group 1' => new ChoiceGroupView(
                        'Group 1',
                        [0 => new ChoiceView($this->obj1, '0', 'A')]
                    ),
                    'Group 2' => new ChoiceGroupView(
                        'Group 2',
                        [3 => new ChoiceView($this->obj4, '3', 'D')]
                    ),
                ],
                [
                    'Group 1' => new ChoiceGroupView(
                        'Group 1',
                        [1 => new ChoiceView($this->obj2, '1', 'B')]
                    ),
                    'Group 2' => new ChoiceGroupView(
                        'Group 2',
                        [2 => new ChoiceView($this->obj3, '2', 'C')]
                    ),
                ]
            ),
            $view
        );
    }
}

/** @ignore */
final class DefaultChoiceListFactoryTest_Castable
{
    private $property;

    public function __construct($property)
    {
        $this->property = $property;
    }

    public function __toString()
    {
        return $this->property;
    }
}
