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

use Doctrine\DBAL\Types\Type as DBALType;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Orm\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Orm\ValueConversion;

final class AgeDateConversion implements ColumnConversion, ValueConversion
{
    public function convertColumn(string $column, array $options, ConversionHints $hints): string
    {
        if ($hints->getProcessingValue() instanceof \DateTimeImmutable) {
            return "SEARCH_CONVERSION_CAST($column, 'DATE')";
        }

        return "SEARCH_CONVERSION_AGE($column)";
    }

    public function convertValue($value, array $options, ConversionHints $hints): string
    {
        if ($value instanceof \DateTimeImmutable) {
            return $hints->createParamReferenceFor($value, DBALType::getType('date'));
        }

        return $hints->createParamReferenceFor($value, DBALType::getType('integer'));
    }
}
