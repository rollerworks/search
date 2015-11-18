<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Value;

use Rollerworks\Component\Search\Value\PatternMatch;

class PatternMatchTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals('foo', $this->value->getValue());
    }

    /** @test */
    public function it_has_a_patternType()
    {
        $this->assertEquals(PatternMatch::PATTERN_CONTAINS, $this->value->getType());
    }

    /** @test */
    public function it_is_case_sensitive_by_default()
    {
        $this->assertEquals(false, $this->value->isCaseInsensitive());
    }

    /** @test */
    public function it_allows_case_insensitive()
    {
        $this->value = new PatternMatch('foo', PatternMatch::PATTERN_CONTAINS, true);
        $this->assertEquals(true, $this->value->isCaseInsensitive());
    }

    /** @test */
    public function it_throws_when_setting_an_invalid_value()
    {
        $this->setExpectedException('InvalidArgumentException', 'Value of PatternMatch must be a scalar value.');

        new PatternMatch(new \stdClass(), PatternMatch::PATTERN_CONTAINS);
    }

    /** @test */
    public function it_accepts_a_patternType_as_string()
    {
        $this->value = new PatternMatch('foo', 'CONTAINS');
        $this->assertEquals(PatternMatch::PATTERN_CONTAINS, $this->value->getType());
    }
}
