<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search\Extension\Core\ValueComparison;

use PhpSpec\ObjectBehavior;

class DateValueComparisonSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Extension\Core\ValueComparison\DateValueComparison');
    }

    public function it_returns_true_when_dates_equal()
    {
        $date1 = new \DateTime('2013-09-21 12:46:00');
        $date2 = new \DateTime('2013-09-21 12:46:00');

        $this->isEqual($date1, $date2, array())->shouldReturn(true);
    }

    public function it_returns_false_when_dates_are_not_equal()
    {
        $date1 = new \DateTime('2013-09-21 12:46:00');
        $date2 = new \DateTime('2013-09-22 12:46:00');
        $this->isEqual($date1, $date2, array())->shouldReturn(false);

        $date1 = new \DateTime('2013-09-21 12:46:00');
        $date2 = new \DateTime('2013-09-21 12:40:00');
        $this->isEqual($date1, $date2, array())->shouldReturn(false);
    }

    public function it_returns_true_when_first_date_is_higher()
    {
        $date1 = new \DateTime('2013-09-23 12:46:00');
        $date2 = new \DateTime('2013-09-21 12:46:00');

        $this->isHigher($date1, $date2, array())->shouldReturn(true);
    }

    public function it_returns_true_when_first_date_is_lower()
    {
        $date1 = new \DateTime('2013-09-21 12:46:00');
        $date2 = new \DateTime('2013-09-23 12:46:00');

        $this->isLower($date1, $date2, array())->shouldReturn(true);
    }

    public function its_incremented_value_is_one_day()
    {
        $date = new \DateTime('2013-09-21 12:46:00');

        $this->getIncrementedValue($date, array())->shouldBeLike(new \DateTime('2013-09-22 12:46:00'));
    }
}
