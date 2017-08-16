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

namespace Rollerworks\Component\Search\Tests\Fixtures;

use Rollerworks\Component\Search\Field\AbstractFieldType;

/**
 * @internal
 */
final class FooSubType extends AbstractFieldType
{
    public function getParent(): ?string
    {
        return FooType::class;
    }
}
