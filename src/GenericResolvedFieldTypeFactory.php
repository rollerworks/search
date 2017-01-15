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

namespace Rollerworks\Component\Search;

class GenericResolvedFieldTypeFactory implements ResolvedFieldTypeFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createResolvedType(FieldTypeInterface $type, array $typeExtensions, ResolvedFieldTypeInterface $parent = null): ResolvedFieldTypeInterface
    {
        return new GenericResolvedFieldType($type, $typeExtensions, $parent);
    }
}
