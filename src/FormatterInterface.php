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
     */
    public function format(SearchConditionInterface $condition);
}
