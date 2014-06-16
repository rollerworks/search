<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
     * Formats a {@link ValuesGroup} with the provided {@link FieldSet}.
     *
     * Formatting is done to remove duplicated values,
     * normalize overlapping constraints and validate/transform a value.
     *
     * Formatters can be run in sequence, and therefor should be as small as possible.
     *
     * If the given top ValuesGroup object contains violations,
     * the formatter is required to ignore the Group and do nothing.
     *
     * @param SearchConditionInterface $condition
     *
     * @return void
     */
    public function format(SearchConditionInterface $condition);
}
