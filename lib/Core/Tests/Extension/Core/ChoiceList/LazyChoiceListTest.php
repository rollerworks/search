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

    protected function setUp(): void
    {
        $this->loadedList = $this->createMock(ChoiceList::class);
        $this->loader = $this->createMock(ChoiceLoader::class);
        $this->value = static function (): void {};

        $this->list = new LazyChoiceList($this->loader, $this->value);
    }

    /** @test */
    public function get_choice_loaders_loads_list(): void
    {
        $this->loader->expects(self::exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->willReturn($this->loadedList)
        ;

        // The same list is returned by the loader
        $this->loadedList->expects(self::exactly(2))
            ->method('getChoices')
            ->willReturn(['RESULT'])
        ;

        self::assertSame(['RESULT'], $this->list->getChoices());
        self::assertSame(['RESULT'], $this->list->getChoices());
    }

    /** @test */
    public function get_values_loads_loaded_list(): void
    {
        $this->loader->expects(self::exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->willReturn($this->loadedList)
        ;

        // The same list is returned by the loader
        $this->loadedList->expects(self::exactly(2))
            ->method('getValues')
            ->willReturn(['RESULT'])
        ;

        self::assertSame(['RESULT'], $this->list->getValues());
        self::assertSame(['RESULT'], $this->list->getValues());
    }

    /** @test */
    public function get_structured_values_loads_loaded_lis(): void
    {
        $this->loader->expects(self::exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->willReturn($this->loadedList)
        ;

        // The same list is returned by the loader
        $this->loadedList->expects(self::exactly(2))
            ->method('getStructuredValues')
            ->willReturn(['RESULT'])
        ;

        self::assertSame(['RESULT'], $this->list->getStructuredValues());
        self::assertSame(['RESULT'], $this->list->getStructuredValues());
    }

    /** @test */
    public function get_original_keys_loads_loaded_list(): void
    {
        $this->loader->expects(self::exactly(2))
            ->method('loadChoiceList')
            ->with($this->value)
            ->willReturn($this->loadedList)
        ;

        // The same list is returned by the loader
        $this->loadedList->expects(self::exactly(2))
            ->method('getOriginalKeys')
            ->willReturn(['RESULT'])
        ;

        self::assertSame(['RESULT'], $this->list->getOriginalKeys());
        self::assertSame(['RESULT'], $this->list->getOriginalKeys());
    }

    /** @test */
    public function get_choices_for_values_forwards_call(): void
    {
        $this->loader->expects(self::exactly(2))
            ->method('loadChoicesForValues')
            ->with(['a', 'b'])
            ->willReturn(['RESULT'])
        ;

        self::assertSame(['RESULT'], $this->list->getChoicesForValues(['a', 'b']));
        self::assertSame(['RESULT'], $this->list->getChoicesForValues(['a', 'b']));
    }

    /** @test */
    public function get_values_for_choices_uses_loaded_list(): void
    {
        $this->loader->expects(self::exactly(2))
            ->method('loadValuesForChoices')
            ->with(['a', 'b'])
            ->willReturn(['RESULT'])
        ;

        // load choice list
        $this->list->getChoices();

        self::assertSame(['RESULT'], $this->list->getValuesForChoices(['a', 'b']));
        self::assertSame(['RESULT'], $this->list->getValuesForChoices(['a', 'b']));
    }
}
