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

/**
 * FieldAliasResolverInterface must be implemented by every AliasResolver.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldAliasResolverInterface
{
    /**
     * Resolve the field alias to a real fieldname.
     *
     * Note: If a field alias can not be resolved
     * it should return the $fieldName as-is.
     *
     * @param FieldSet $fieldSet
     * @param string   $fieldAlias
     *
     * @return string
     */
    public function resolveFieldName(FieldSet $fieldSet, $fieldAlias);
}
