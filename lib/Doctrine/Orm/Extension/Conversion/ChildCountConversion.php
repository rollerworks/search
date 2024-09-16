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

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm\Conversion;

use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Orm\ColumnConversion;

final class ChildCountConversion implements ColumnConversion
{
    public function convertColumn(string $column, array $options, ConversionHints $hints): string
    {
        return \sprintf('SEARCH_COUNT_CHILDREN(%s, %s, %s)', $options['table_name'], $options['table_column'], $column);
    }
}
