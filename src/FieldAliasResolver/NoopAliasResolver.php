<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\FieldAliasResolver;

use Rollerworks\Component\Search\FieldAliasResolverInterface;
use Rollerworks\Component\Search\FieldSet;

class NoopAliasResolver implements FieldAliasResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveFieldName(FieldSet $fieldSet, $fieldAlias)
    {
        return $fieldAlias;
    }
}
