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

use Carbon\CarbonInterval;
use Doctrine\DBAL\Types\Types;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Orm\ValueConversion;

final class DateIntervalConversion implements ValueConversion
{
    /**
     * @param CarbonInterval|\DateTimeImmutable $value
     */
    public function convertValue($value, array $options, ConversionHints $hints): string
    {
        if ($value instanceof \DateTimeImmutable) {
            return $hints->createParamReferenceFor($value, Types::DATETIME_IMMUTABLE);
        }

        $value = clone $value;
        $value->locale('en');

        return \sprintf("SEARCH_CAST_INTERVAL('%s', %s)", $value->forHumans(), $value->invert === 1 ? 'true' : 'false');
    }
}
