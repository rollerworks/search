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

use Rollerworks\Component\Search\Elasticsearch\QueryConditionGenerator as Generator;
use Rollerworks\Component\Search\Elasticsearch\QueryConversion;
use Rollerworks\Component\Search\Elasticsearch\QueryConversionHints;
use Rollerworks\Component\Search\Elasticsearch\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Rollerworks\Component\Search\Value\Range;

/**
 * Class DateConversion.
 */
class DateConversion implements ValueConversion, QueryConversion
{
    /**
     * @var DateTimeToStringTransformer
     */
    private $transformer;

    public function __construct()
    {
        $this->transformer = new DateTimeToStringTransformer(null, 'UTC', 'Y-m-d');
    }

    /**
     * @inheritdoc
     *
     * @throws \Rollerworks\Component\Search\Exception\TransformationFailedException
     */
    public function convertValue($value): string
    {
        return $this->transformer->transform($value);
    }

    /**
     * @inheritdoc
     */
    public function convertQuery(string $propertyName, $value, QueryConversionHints $hints): ?array
    {
        if (!is_array($value)) {
            return $this->generateDateRange($propertyName, new Range($value, $value));
        }

        $range = [];

        /** @var array $value */
        foreach ($value as $singleValue) {
            $dateRange = $this->generateDateRange($propertyName, new Range($singleValue, $singleValue));
            $range[Generator::QUERY_BOOL][Generator::CONDITION_OR][] = $dateRange;
        }

        return $range;
    }

    /**
     * @param string $propertyName
     * @param Range  $range
     *
     * @return array
     */
    private function generateDateRange(string $propertyName, Range $range): array
    {
        return [
            Generator::QUERY_RANGE => [
                $propertyName => Generator::generateRangeParams($range),
            ],
        ];
    }
}
