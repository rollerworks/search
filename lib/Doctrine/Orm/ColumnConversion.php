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

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;

/**
 * A ColumnConversion allows to wrap the query's column in a custom
 * DQL statement (as-is).
 *
 * This interface can be combined with the ValueConversion interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ColumnConversion
{
    /**
     * Return the $column wrapped inside an DQL statement like: MY_FUNCTION(column).
     *
     * @param string          $column  The column name and table alias, eg. i.id
     * @param array           $options Options of the Field configuration
     * @param ConversionHints $hints   Special information for the conversion process
     */
    public function convertColumn(string $column, array $options, ConversionHints $hints): string;
}
