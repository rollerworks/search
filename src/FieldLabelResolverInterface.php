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
 * Provide a field-label resolver.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldLabelResolverInterface
{
    /**
     * Resolve the field name to an localized field-alias (label).
     *
     * Note: If the field alias can not be resolved
     * this should return the $fieldName unresolved.
     *
     * @param FieldSet $fieldSet
     * @param string   $fieldName
     *
     * @return string
     */
    public function resolveFieldLabel(FieldSet $fieldSet, $fieldName);
}
