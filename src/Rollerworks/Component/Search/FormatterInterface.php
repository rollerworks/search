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
 * ValuesGroup formatter interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FormatterInterface
{
    /**
     * Formats a {@link ValuesGroup}.
     *
     * Formatting is done to remove duplicated values,
     * normalize overlapping constraints and validate/transform a value.
     *
     *  When a value is considered invalid, it should update the tree by calling setViolations().
     *  Its important to set violations for the ValuesBag object, and inform its parent-groups
     *  there is a violation in the descending group(s).
     *
     * Formatters can be run in sequence, and therefor should be as small as possible.
     *
     * If the given top ValuesGroup object contains violations,
     * the formatter is required to ignore the Group and do nothing.
     *
     * @param FieldSet    $fieldSet
     * @param ValuesGroup $valuesGroup
     */
    public function format(FieldSet $fieldSet, ValuesGroup $valuesGroup);
}
