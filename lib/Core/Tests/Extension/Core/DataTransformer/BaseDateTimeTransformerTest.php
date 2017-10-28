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

namespace Rollerworks\Component\Search\Tests\Extension\Core\DataTransformer;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\BaseDateTimeTransformer;

/**
 * @internal
 */
final class BaseDateTimeTransformerTest extends TestCase
{
    /**
     * @expectedException \Rollerworks\Component\Search\Exception\InvalidArgumentException
     * @expectedExceptionMessage this_timezone_does_not_exist
     */
    public function testConstructFailsIfInputTimezoneIsInvalid()
    {
        $this->getMockBuilder(BaseDateTimeTransformer::class)->setConstructorArgs(['this_timezone_does_not_exist'])->getMock();
    }

    /**
     * @expectedException \Rollerworks\Component\Search\Exception\InvalidArgumentException
     * @expectedExceptionMessage that_timezone_does_not_exist
     */
    public function testConstructFailsIfOutputTimezoneIsInvalid()
    {
        $this->getMockBuilder(BaseDateTimeTransformer::class)->setConstructorArgs([null, 'that_timezone_does_not_exist'])->getMock();
    }
}
