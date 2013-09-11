<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * it should return the $fieldName unresolved.
     *
     * @param FieldSet $fieldSet
     * @param string   $fieldName
     *
     * @return string
     */
    public function resolveFieldLabel(FieldSet $fieldSet, $fieldName);
}
