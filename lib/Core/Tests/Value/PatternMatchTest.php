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
use Rollerworks\Component\Search\Value\PatternMatch;

/**
 * @internal
 */
final class PatternMatchTest extends TestCase
{
    /** @var PatternMatch */
    private $value;

    protected function setUp(): void
    {
        $this->value = new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS);
    }

    /** @test */
    public function it_has_a_value(): void
    {
        self::assertEquals('foo', $this->value->getValue());
    }

    /** @test */
    public function it_has_a_pattern_type(): void
    {
        self::assertEquals(PatternMatch::PATTERN_CONTAINS, $this->value->getType());
    }

    /** @test */
    public function it_is_case_sensitive_by_default(): void
    {
        self::assertFalse($this->value->isCaseInsensitive());
    }

    /** @test */
    public function it_allows_case_insensitive(): void
    {
        $this->value = new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS, true);
        self::assertTrue($this->value->isCaseInsensitive());
    }

    /** @test */
    public function it_accepts_a_pattern_type_as_string(): void
    {
        $this->value = new PatternMatch('foo', 'CONTAINS');
        self::assertEquals(PatternMatch::PATTERN_CONTAINS, $this->value->getType());
    }
}
