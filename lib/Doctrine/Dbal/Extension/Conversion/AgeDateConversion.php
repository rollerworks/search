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

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion;

use Doctrine\DBAL\Types\Types;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;

final class AgeDateConversion implements ColumnConversion, ValueConversion
{
    public function convertColumn(string $column, array $options, ConversionHints $hints): string
    {
        if ($hints->getProcessingValue() instanceof \DateTimeInterface) {
            return "CAST({$column} AS DATE)";
        }

        $platform = $hints->getPlatformName();

        $convertMap = [];
        $convertMap['pgsql'] = "to_char(age(%1\$s), 'YYYY'::text)::integer";
        $convertMap['mysql'] = "(DATE_FORMAT(NOW(), '%%Y') - DATE_FORMAT(%1\$s, '%%Y') - (DATE_FORMAT(NOW(), '00-%%m-%%d') < DATE_FORMAT(%1\$s, '00-%%m-%%d')))";
        $convertMap['mssql'] = 'DATEDIFF(hour, %1$s, GETDATE())/8766';
        $convertMap['oci'] = 'trunc((months_between(sysdate, (sysdate - %1$s)))/12)';
        $convertMap['mock'] = 'search_conversion_age(%1$s)';

        if (isset($convertMap[$platform])) {
            return \sprintf($convertMap[$platform], $column);
        }

        throw new \RuntimeException(
            \sprintf('Unsupported platform "%s" for AgeDateConversion.', $platform)
        );
    }

    public function convertValue($value, array $options, ConversionHints $hints): string
    {
        if ($value instanceof \DateTimeImmutable) {
            return $hints->createParamReferenceFor($value, Types::DATETIME_IMMUTABLE);
        }

        if ($value instanceof \DateTime) {
            return $hints->createParamReferenceFor($value, Types::DATETIME_MUTABLE);
        }

        return $hints->createParamReferenceFor($value, 'integer');
    }
}
