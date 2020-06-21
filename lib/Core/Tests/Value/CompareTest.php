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
use Rollerworks\Component\Search\Value\Compare;

/**
 * @internal
 */
final class CompareTest extends TestCase
{
    /** @var Compare */
    private $value;

    public function setUp(): void
    {
        $this->value = new Compare(10, '>');
    }

    /** @test */
    public function it_has_a_value()
    {
        self::assertEquals(10, $this->value->getValue());
    }

    /** @test */
    public function it_has_an_operator()
    {
        self::assertEquals('>', $this->value->getOperator());
    }

    /** @test */
    public function it_allows_an_object_as_value()
    {
        $value = new \DateTime();

        $this->value = new Compare($value, '<');

        self::assertEquals($value, $this->value->getValue());
        self::assertEquals('<', $this->value->getOperator());
    }
}
