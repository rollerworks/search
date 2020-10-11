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

namespace Rollerworks\Component\Search\Tests\Value;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Value\Range;

/**
 * @internal
 */
final class RangeTest extends TestCase
{
    /**
     * @var Range
     */
    private $value;

    /** @test */
    public function it_has_a_lower_value(): void
    {
        $this->value = new Range(10, 20);
        self::assertEquals(10, $this->value->getLower());
    }

    /** @test */
    public function it_has_an_upper_value(): void
    {
        $this->value = new Range(10, 20);
        self::assertEquals(20, $this->value->getUpper());
    }

    /** @test */
    public function its_lower_value_is_inclusive_by_default(): void
    {
        $this->value = new Range(10, 20);
        self::assertTrue($this->value->isLowerInclusive());
    }

    /** @test */
    public function its_upper_value_should_be_inclusive_by_default(): void
    {
        $this->value = new Range(10, 20);
        self::assertTrue($this->value->isUpperInclusive());
    }

    /** @test */
    public function it_allows_exclusive_lower_value(): void
    {
        $this->value = new Range(10, 20, false);
        self::assertFalse($this->value->isLowerInclusive());
    }

    /** @test */
    public function it_allows_exclusive_upper_value(): void
    {
        $this->value = new Range(10, 20, true, false);
        self::assertFalse($this->value->isUpperInclusive());
    }
}
