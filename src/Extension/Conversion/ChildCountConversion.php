<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion;

use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;

/**
 * ItemCountConversion.
 *
 * Allows counting the number of parent/children references.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ChildCountConversion implements SqlFieldConversionInterface
{
    public function convertSqlField($column, array $options, ConversionHints $hints)
    {
        return '(SELECT COUNT(*) FROM '.$options['table_name'].' WHERE '.$options['table_column']." = $column)";
    }
}
