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

    public function setUp()
    {
        $this->value = new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS);
    }

    /** @test */
    public function it_has_a_value()
    {
        self::assertEquals('foo', $this->value->getValue());
    }

    /** @test */
    public function it_has_a_patternType()
    {
        self::assertEquals(PatternMatch::PATTERN_CONTAINS, $this->value->getType());
    }

    /** @test */
    public function it_is_case_sensitive_by_default()
    {
        self::assertEquals(false, $this->value->isCaseInsensitive());
    }

    /** @test */
    public function it_allows_case_insensitive()
    {
        $this->value = new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS, true);
        self::assertEquals(true, $this->value->isCaseInsensitive());
    }

    /** @test */
    public function it_accepts_a_patternType_as_string()
    {
        $this->value = new PatternMatch('foo', 'CONTAINS');
        self::assertEquals(PatternMatch::PATTERN_CONTAINS, $this->value->getType());
    }
}
