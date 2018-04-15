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

namespace Rollerworks\Component\Search\Tests\Extension\Core\ValueComparison;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Extension\Core\ValueComparator\DateValueComparator;

/**
 * @internal
 */
final class DateValueComparisonTest extends TestCase
{
    /** @var DateValueComparator */
    private $comparison;

    protected function setUp()
    {
        $this->comparison = new DateValueComparator();
    }

    /** @test */
    public function it_returns_true_when_dates_equal()
    {
        $date1 = new \DateTime('2013-09-21 12:46:00');
        $date2 = new \DateTime('2013-09-21 12:46:00');

        self::assertTrue($this->comparison->isEqual($date1, $date2, []));
    }

    /** @test */
    public function it_returns_false_when_dates_are_not_equal()
    {
        $date1 = new \DateTime('2013-09-21 12:46:00');
        $date2 = new \DateTime('2013-09-22 12:46:00');

        self::assertFalse($this->comparison->isEqual($date1, $date2, []));

        $date1 = new \DateTime('2013-09-21 12:46:00');
        $date2 = new \DateTime('2013-09-21 12:40:00');

        self::assertFalse($this->comparison->isEqual($date1, $date2, []));
    }

    /** @test */
    public function it_returns_true_when_first_date_is_higher()
    {
        $date1 = new \DateTime('2013-09-23 12:46:00');
        $date2 = new \DateTime('2013-09-21 12:46:00');

        self::assertTrue($this->comparison->isHigher($date1, $date2, []));
    }

    /** @test */
    public function it_returns_true_when_first_date_is_lower()
    {
        $date1 = new \DateTime('2013-09-21 12:46:00');
        $date2 = new \DateTime('2013-09-23 12:46:00');

        self::assertTrue($this->comparison->isLower($date1, $date2, []));
    }
}
