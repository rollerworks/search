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
use Rollerworks\Component\Search\Elasticsearch\QueryPreparationHints;
use Rollerworks\Component\Search\Elasticsearch\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\Range;

/**
 * Class DateConversion.
 */
class DateConversion implements ValueConversion, QueryConversion
{
    /**
     * @var DateTimeToStringTransformer
     */
    protected $transformer;

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
     * @param string                $propertyName
     * @param mixed                 $value
     * @param QueryPreparationHints $hints
     *
     * @return array|null
     */
    public function convertQuery(string $propertyName, $value, QueryPreparationHints $hints): ?array
    {
        if (!is_array($value) && !$value instanceof Range && !$value instanceof Compare) {
            return $this->generateDateRange($propertyName, new Range($value, $value));
        }

        $query = [];
        switch ($hints->context) {
            case QueryPreparationHints::CONTEXT_RANGE_VALUES:
            case QueryPreparationHints::CONTEXT_EXCLUDED_RANGE_VALUES:
                // already a Range
                /** @var Range $value */
                $query = [Generator::QUERY_RANGE => [$propertyName => Generator::generateRangeParams($value)]];
                break;
            case QueryPreparationHints::CONTEXT_COMPARISON:
                /** @var Compare $value */
                $operator = Generator::translateComparison($value->getOperator());
                $query = [
                    Generator::QUERY_RANGE => [$propertyName => [$operator => $value->getValue()]]
                ];
                break;
            default:
            case QueryPreparationHints::CONTEXT_SIMPLE_VALUES:
            case QueryPreparationHints::CONTEXT_EXCLUDED_SIMPLE_VALUES:
                // dates as single values, need to convert them to a date range
                /** @var array $value */
                foreach ($value as $singleValue) {
                    $dateRange = $this->generateDateRange($propertyName, new Range($singleValue, $singleValue));
                    $query[Generator::QUERY_BOOL][Generator::CONDITION_OR][] = $dateRange;
                }
                break;
        }

        return $query;
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
