<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

class ResolvedFieldTypeFactory implements ResolvedFieldTypeFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createResolvedType(FieldTypeInterface $type, array $typeExtensions, ResolvedFieldTypeInterface $parent = null)
    {
        return new ResolvedFieldType($type, $typeExtensions, $parent);
    }
}
