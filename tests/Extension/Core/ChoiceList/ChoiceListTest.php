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

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;

final class ChoiceListTest extends TestCase
{
    /**
     * @var ChoiceList
     */
    private $choiceList;

    public function setUp()
    {
        $this->choiceList = new ChoiceList(['creditcard', 'cash'], ['credit-card-payment', 'cash-payment']);
    }

    /**
     * @test
     */
    public function it_returns_the_choice_by_value()
    {
        self::assertEquals('creditcard', $this->choiceList->getChoiceForValue(0));
        self::assertEquals('cash', $this->choiceList->getChoiceForValue(1));
    }

    /**
     * @test
     */
    public function its_choice_returns_null_when_the_value_is_not_set()
    {
        self::assertNull($this->choiceList->getChoiceForValue(2));
    }

    /**
     * @test
     */
    public function it_returns_the_choice_by_label()
    {
        self::assertEquals('creditcard', $this->choiceList->getChoiceForLabel('credit-card-payment'));
        self::assertEquals('cash', $this->choiceList->getChoiceForLabel('cash-payment'));
    }

    /**
     * @test
     */
    public function its_choice_returns_null_when_the_label_is_not_set()
    {
        self::assertNull($this->choiceList->getChoiceForValue('paypal'));
    }

    /**
     * @test
     */
    public function it_returns_the_label_by_choice()
    {
        self::assertEquals('credit-card-payment', $this->choiceList->getLabelForChoice('creditcard'));
        self::assertEquals('cash-payment', $this->choiceList->getLabelForChoice('cash'));
    }

    /**
     * @test
     */
    public function its_label_returns_null_when_the_choice_is_not_set()
    {
        self::assertNull($this->choiceList->getLabelForChoice('paypal'));
    }
}
