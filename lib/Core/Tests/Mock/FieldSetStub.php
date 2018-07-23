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

namespace Rollerworks\Component\Search\Tests\Mock;

use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\FieldSet;

final class FieldSetStub implements FieldSet
{
    public function getSetName(): ?string
    {
        return 'stub';
    }

    public function get(string $name): FieldConfig
    {
    }

    public function all(): array
    {
        return [];
    }

    public function has(string $name): bool
    {
        return false;
    }

    public function isPrivate(string $name): bool
    {
        return '_' === $name[0];
    }
}
