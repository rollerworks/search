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

namespace Rollerworks\Component\Search\Field;

final class GenericResolvedFieldTypeFactory implements ResolvedFieldTypeFactory
{
    public function createResolvedType(FieldType $type, array $typeExtensions, ?ResolvedFieldType $parent = null): ResolvedFieldType
    {
        return new GenericResolvedFieldType($type, $typeExtensions, $parent);
    }
}
