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
 * A ColumnConversion allows to wrap the query's column in a a custom SQL statement.
 *
 * This interface can be combined with ValueConversionInterface and
 * ConversionStrategyInterface for more flexibility.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ColumnConversion
{
    /**
     * Return the $column wrapped inside an SQL statement like: MY_FUNCTION(column).
     *
     * The returned result must a be a platform specific SQL statement
     * that can be used as a column in query.
     *
     * Caution: It's important to properly escape any values used in the returned
     * statement, as they are used as-is.
     *
     * @param string          $column  The column name and table alias, eg. i.id
     * @param array           $options Options of the Field configuration
     * @param ConversionHints $hints   Special information for the conversion process
     *
     * @return string
     */
    public function convertColumn(string $column, array $options, ConversionHints $hints): string;
}
