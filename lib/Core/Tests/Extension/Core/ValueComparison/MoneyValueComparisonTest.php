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

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Rollerworks\Component\Search\Extension\Core\ValueComparator\MoneyValueComparator;

/**
 * @internal
 */
final class MoneyValueComparisonTest extends TestCase
{
    /** @var MoneyValueComparator */
    private $comparison;

    protected function setUp(): void
    {
        $this->comparison = new MoneyValueComparator();
    }

    /** @test */
    public function it_returns_true_equal(): void
    {
        $value1 = new MoneyValue(Money::EUR(1200));
        $value2 = new MoneyValue(Money::EUR(1200));

        self::assertTrue($this->comparison->isEqual($value1, $value2, []));

        $value1 = new MoneyValue(Money::EUR(1200));
        $value2 = new MoneyValue(Money::EUR(1200), false);

        self::assertTrue($this->comparison->isEqual($value1, $value2, []));
    }

    /** @test */
    public function it_returns_false_when_not_equal(): void
    {
        $value1 = new MoneyValue(Money::EUR(1200));
        $value2 = new MoneyValue(Money::EUR(1215));

        self::assertFalse($this->comparison->isEqual($value1, $value2, []));

        // Compare with same amount but different currency
        $value1 = new MoneyValue(Money::EUR(1200));
        $value2 = new MoneyValue(Money::USD(1200));

        self::assertFalse($this->comparison->isEqual($value1, $value2, []));
    }

    /** @test */
    public function it_returns_true_when_first_value_is_higher(): void
    {
        $value1 = new MoneyValue(Money::EUR(1500));
        $value2 = new MoneyValue(Money::EUR(1200));

        self::assertTrue($this->comparison->isHigher($value1, $value2, []));

        $value1 = new MoneyValue(Money::EUR(1210));
        $value2 = new MoneyValue(Money::EUR(1200));

        self::assertTrue($this->comparison->isHigher($value1, $value2, []));
    }

    /** @test */
    public function it_returns_true_when_first_value_is_lower(): void
    {
        $value1 = new MoneyValue(Money::EUR(1000));
        $value2 = new MoneyValue(Money::EUR(1200));

        self::assertTrue($this->comparison->isLower($value1, $value2, []));

        $value1 = new MoneyValue(Money::EUR(1200));
        $value2 = new MoneyValue(Money::EUR(1210));

        self::assertTrue($this->comparison->isLower($value1, $value2, []));
    }

    /** @test */
    public function it_returns_false_when_first_value_is_not_higher(): void
    {
        $value1 = new MoneyValue(Money::EUR(1200));
        $value2 = new MoneyValue(Money::EUR(1500));

        self::assertFalse($this->comparison->isHigher($value1, $value2, []));

        // Diff currency.
        $value1 = new MoneyValue(Money::EUR(1210));
        $value2 = new MoneyValue(Money::USD(1200));

        self::assertFalse($this->comparison->isHigher($value1, $value2, []));
    }

    /** @test */
    public function it_returns_false_when_first_value_is_not_lower(): void
    {
        $value1 = new MoneyValue(Money::EUR(1200));
        $value2 = new MoneyValue(Money::EUR(1000));

        self::assertFalse($this->comparison->isLower($value1, $value2, []));

        $value1 = new MoneyValue(Money::EUR(1000));
        $value2 = new MoneyValue(Money::USD(1200));

        self::assertFalse($this->comparison->isLower($value1, $value2, []));
    }
}
