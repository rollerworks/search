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
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\BaseDateTimeTransformer;

/**
 * @internal
 */
final class BaseDateTimeTransformerTest extends TestCase
{
    /** @test */
    public function construct_fails_if_input_timezone_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('this_timezone_does_not_exist');

        $this->getMockBuilder(BaseDateTimeTransformer::class)->setConstructorArgs(['this_timezone_does_not_exist'])->getMock();
    }

    /** @test */
    public function construct_fails_if_output_timezone_is_invalid(): void
    {
        $this->expectExceptionMessage('that_timezone_does_not_exist');
        $this->expectException(InvalidArgumentException::class);

        $this->getMockBuilder(BaseDateTimeTransformer::class)->setConstructorArgs([null, 'that_timezone_does_not_exist'])->getMock();
    }
}
