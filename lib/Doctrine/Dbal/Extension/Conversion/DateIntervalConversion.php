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

use Carbon\CarbonInterval;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;

final class DateIntervalConversion implements ValueConversion
{
    /**
     * @param CarbonInterval|\DateTimeImmutable $value
     */
    public function convertValue($value, array $options, ConversionHints $hints): string
    {
        if ($value instanceof \DateTimeImmutable) {
            return $hints->createParamReferenceFor($value, Type::getType(Types::DATETIME_IMMUTABLE));
        }

        $platform = $hints->connection->getDatabasePlatform()->getName();

        $value = clone $value;
        $value->locale('en');

        if ($platform === 'postgresql' || $platform === 'mock') {
            $intervalString = 'CAST(' . $hints->createParamReferenceFor($value->forHumans()) . ' AS interval)';
        } elseif ($platform === 'mysql' || $platform === 'drizzle') {
            $intervalString = self::convertForMysql($value);
        } else {
            throw new \RuntimeException(
                \sprintf('Unsupported platform "%s" for DateIntervalConversion.', $platform)
            );
        }

        if ($value->invert === 1) {
            return 'NOW() - ' . $intervalString;
        }

        return 'NOW() + ' . $intervalString;
    }

    public static function convertForMysql(CarbonInterval $value): string
    {
        $negative = $value->invert === 1;

        $handler = static function (array $units) use ($negative) {
            foreach ($units as &$value) {
                // MySQL doesn't support plural names.
                $value = mb_strtoupper(rtrim($value, 's'));
            }

            return implode($negative ? ' - INTERVAL ' : ' + INTERVAL ', $units);
        };

        // Note. Don't use parameters here, values are already pre-formatted.
        return 'INTERVAL ' . $value->forHumans(['join' => $handler]);
    }
}
