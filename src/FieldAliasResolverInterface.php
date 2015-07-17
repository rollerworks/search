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
     * Resolve a field's alias to a real field name.
     *
     * Note: When a field alias cannot be resolved the field $fieldAlias
     * should be returned without any changes.
     *
     * @param FieldSet $fieldSet
     * @param string   $fieldAlias
     *
     * @return string The real field name
     */
    public function resolveFieldName(FieldSet $fieldSet, $fieldAlias);
}
