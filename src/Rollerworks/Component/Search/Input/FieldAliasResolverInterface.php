<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\FieldSet;

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
     * @param string   $fieldName
     *
     * @return string
     */
    public function resolveFieldName(FieldSet $fieldSet, $fieldName);
}
