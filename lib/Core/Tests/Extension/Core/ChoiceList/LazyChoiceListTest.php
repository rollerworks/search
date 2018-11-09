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

namespace Rollerworks\Component\Search\Tests\Extension\Core\ChoiceList;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\LazyChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
final class LazyChoiceListTest extends TestCase
{
    /**
     * @var LazyChoiceList|null
     */
    private $list;

    /**
     * @var MockObject|null
     */
    private $loadedList;

    /**
     * @var MockObject|null
     */
    private $loader;

    private $value;

    protected function setUp()
    {
        $this->loadedList = $this->createMock(ChoiceList::class);
        $this->loader = $this->createMock(ChoiceLoader::class);
        $this->value = function () {
        };

        $this->list = new LazyChoiceList($this->loader, $this->value);
    }

    public function testGetChoiceLoadersLoadsList()
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->will($this->returnValue($this->loadedList));

        // The same list is returned by the loader
        $this->loadedList->expects($this->exactly(2))
            ->method('getChoices')
            ->will($this->returnValue(['RESULT']));

        self::assertSame(['RESULT'], $this->list->getChoices());
        self::assertSame(['RESULT'], $this->list->getChoices());
    }

    public function testGetValuesLoadsLoadedList()
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->will($this->returnValue($this->loadedList));

        // The same list is returned by the loader
        $this->loadedList->expects($this->exactly(2))
            ->method('getValues')
            ->will($this->returnValue(['RESULT']));

        self::assertSame(['RESULT'], $this->list->getValues());
        self::assertSame(['RESULT'], $this->list->getValues());
    }

    public function testGetStructuredValuesLoadsLoadedLis()
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->will($this->returnValue($this->loadedList));

        // The same list is returned by the loader
        $this->loadedList->expects($this->exactly(2))
            ->method('getStructuredValues')
            ->will($this->returnValue(['RESULT']));

        self::assertSame(['RESULT'], $this->list->getStructuredValues());
        self::assertSame(['RESULT'], $this->list->getStructuredValues());
    }

    public function testGetOriginalKeysLoadsLoadedList()
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->will($this->returnValue($this->loadedList));

        // The same list is returned by the loader
        $this->loadedList->expects($this->exactly(2))
            ->method('getOriginalKeys')
            ->will($this->returnValue(['RESULT']));

        self::assertSame(['RESULT'], $this->list->getOriginalKeys());
        self::assertSame(['RESULT'], $this->list->getOriginalKeys());
    }

    public function testGetChoicesForValuesForwardsCall()
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadChoicesForValues')
            ->with(['a', 'b'])
            ->will($this->returnValue(['RESULT']));

        self::assertSame(['RESULT'], $this->list->getChoicesForValues(['a', 'b']));
        self::assertSame(['RESULT'], $this->list->getChoicesForValues(['a', 'b']));
    }

    public function testGetValuesForChoicesUsesLoadedList()
    {
        $this->loader->expects($this->exactly(2))
            ->method('loadValuesForChoices')
            ->with(['a', 'b'])
            ->will($this->returnValue(['RESULT']));

        // load choice list
        $this->list->getChoices();

        self::assertSame(['RESULT'], $this->list->getValuesForChoices(['a', 'b']));
        self::assertSame(['RESULT'], $this->list->getValuesForChoices(['a', 'b']));
    }
}
