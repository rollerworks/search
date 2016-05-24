<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\ChoiceList;

use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;

final class ChoiceListTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals('creditcard', $this->choiceList->getChoiceForValue(0));
        $this->assertEquals('cash', $this->choiceList->getChoiceForValue(1));
    }

    /**
     * @test
     */
    public function its_choice_returns_null_when_the_value_is_not_set()
    {
        $this->assertNull($this->choiceList->getChoiceForValue(2));
    }

    /**
     * @test
     */
    public function it_returns_the_choice_by_label()
    {
        $this->assertEquals('creditcard', $this->choiceList->getChoiceForLabel('credit-card-payment'));
        $this->assertEquals('cash', $this->choiceList->getChoiceForLabel('cash-payment'));
    }

    /**
     * @test
     */
    public function its_choice_returns_null_when_the_label_is_not_set()
    {
        $this->assertNull($this->choiceList->getChoiceForValue('paypal'));
    }

    /**
     * @test
     */
    public function it_returns_the_label_by_choice()
    {
        $this->assertEquals('credit-card-payment', $this->choiceList->getLabelForChoice('creditcard'));
        $this->assertEquals('cash-payment', $this->choiceList->getLabelForChoice('cash'));
    }

    /**
     * @test
     */
    public function its_label_returns_null_when_the_choice_is_not_set()
    {
        $this->assertNull($this->choiceList->getLabelForChoice('paypal'));
    }
}
