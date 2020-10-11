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

namespace Rollerworks\Component\Search\Tests;

use PHPUnit\Framework\Assert;

trait assertDateTimeEqualsTrait
{
    public static function assertDateTimeEquals(\DateTimeInterface $expected, \DateTimeInterface $actual): void
    {
        Assert::assertEquals(
            $expected->format('U'),
            $actual->format('U'),
            $expected->format('c') . ' <=> ' . $actual->format('c')
        );
    }
}
