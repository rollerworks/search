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

namespace Rollerworks\Component\Search\Elasticsearch\Extension\Conversion;

use Carbon\CarbonInterval;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToStringTransformer;

class DateTimeConversion extends DateConversion
{
    public function __construct()
    {
        $this->transformer = new DateTimeToStringTransformer(null, 'UTC', 'Y-m-d\TH:i:s');
    }

    /**
     * @param CarbonInterval|\DateTimeImmutable|null $value
     */
    public function convertValue($value): string
    {
        if ($value === null) {
            return '';
        }

        // https://www.elastic.co/guide/en/elasticsearch/reference/current/common-options.html#date-math
        if ($value instanceof CarbonInterval) {
            $value = clone $value;
            $value->locale('en');

            if ($value->invert === 1) {
                return 'now-' . implode('-', $this->getIntervalUnits($value));
            }

            return 'now+' . implode('+', $this->getIntervalUnits($value));
        }

        return parent::convertValue($value);
    }

    private function getIntervalUnits(CarbonInterval $value): array
    {
        static $formats = [
            'years' => 'y',
            'months' => 'M',
            'weeks' => 'w',
            'days' => 'd',
            'hours' => 'h',
            'minutes' => 'm',
            'seconds' => 's',
        ];

        $intervalUnits = $value->toArray();
        $units = [];

        foreach ($formats as $long => $short) {
            if ($intervalUnits[$long] > 0) {
                $units[] = $intervalUnits[$long] . $short;
            }
        }

        return $units;
    }
}
