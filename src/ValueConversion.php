<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

/**
 * A ValueConversion allows to convert the value "model" to a valid
 * SQL statement to be used a column value.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ValueConversion
{
    /**
     * Returns the converted value as an SQL statement.
     *
     * The returned result must a be a platform specific SQL statement
     * that can be used as a column's value.
     *
     * Caution: It's important to properly escape any values used in the returned
     * statement, as they are used as-is!
     *
     * The returned result is NOT for a prepared statement value binding.
     *
     * @param mixed           $value   The "model" value format
     * @param array           $options Options of the Field configuration
     * @param ConversionHints $hints   Special information for the conversion process
     *
     * @return string String or any value that can be used in a string
     */
    public function convertValue($value, array $options, ConversionHints $hints);
}
