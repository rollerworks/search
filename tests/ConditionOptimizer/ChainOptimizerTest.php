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

namespace Rollerworks\Component\Search\Tests\ConditionOptimizer;

use Prophecy\Prophecy\ObjectProphecy;
use Rollerworks\Component\Search\ConditionOptimizer\ChainOptimizer;
use Rollerworks\Component\Search\SearchConditionOptimizer;
use Rollerworks\Component\Search\Test\SearchConditionOptimizerTestCase;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @internal
 */
final class ChainOptimizerTest extends SearchConditionOptimizerTestCase
{
    /**
     * @var ObjectProphecy|null
     */
    private $optimizer1;

    /**
     * @var ObjectProphecy|null
     */
    private $optimizer2;

    /**
     * @var ChainOptimizer|null
     */
    protected $optimizer;

    protected function setUp()
    {
        parent::setUp();

        $this->optimizer = new ChainOptimizer();

        $this->optimizer1 = $this->prophesize(SearchConditionOptimizer::class);
        $this->optimizer2 = $this->prophesize(SearchConditionOptimizer::class);
        $this->optimizer1->getPriority()->willReturn(0);
        $this->optimizer2->getPriority()->willReturn(5);
    }

    /**
     * @test
     */
    public function it_execute_the_registered_optimizers_priority_order()
    {
        $searchCondition = $this->prophesize('Rollerworks\Component\Search\SearchCondition');
        $searchCondition->getValuesGroup()->willReturn(new ValuesGroup());

        $checkValue = [];

        $this->optimizer1->process($searchCondition)->will(function () use (&$checkValue) {
            $checkValue[] = 2;
        });
        $this->optimizer2->process($searchCondition)->will(function () use (&$checkValue) {
            $checkValue[] = 1;
        });

        $this->optimizer->addOptimizer($this->optimizer1->reveal());
        $this->optimizer->addOptimizer($this->optimizer2->reveal());

        $this->optimizer->process($searchCondition->reveal());
        self::assertSame([1, 2], $checkValue);
    }

    /**
     * @test
     *
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Unable to add optimizer to its own chain.
     */
    public function it_errors_when_adding_self()
    {
        $this->optimizer->addOptimizer($this->optimizer);
    }
}
