<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

/**
 * Provide field-alias resolver.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldAliasResolverInterface
{
    /**
     * Resolve the field alias to an real fieldname.
     *
     * Note: If the field alias can not be resolved
     * it should return the $fieldName as-is.
     *
     * @param FieldSet $fieldSet
     * @param string   $fieldAlias
     *
     * @return string
     */
    public function resolveFieldName(FieldSet $fieldSet, $fieldAlias);
}
