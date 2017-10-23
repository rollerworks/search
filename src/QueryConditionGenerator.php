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

namespace Rollerworks\Component\Search\Elasticsearch;

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesGroup;

final class QueryConditionGenerator
{
    private const PROPERTY_ID = '_id';

    // Elasticsearch general query elements
    public const QUERY = 'query';
    public const QUERY_BOOL = 'bool';
    public const QUERY_IDS = 'ids';
    public const QUERY_NESTED = 'nested';
    public const QUERY_MATCH = 'match';
    public const QUERY_PREFIX = 'prefix';
    public const QUERY_RANGE = 'range';
    public const QUERY_WILDCARD = 'wildcard';
    public const QUERY_TERM = 'term';
    public const QUERY_TERMS = 'terms';
    public const QUERY_VALUE = 'value';
    public const QUERY_VALUES = 'values';

    // Elasticsearch boolean operators
    public const CONDITION_NOT = 'must_not';
    public const CONDITION_AND = 'must';
    public const CONDITION_OR = 'should';

    private const COMPARE_OPR_TYPE = ['>=' => 'gte', '<=' => 'lte', '<' => 'lt', '>' => 'gt'];

    private $searchCondition;
    private $fieldSet;

    /** @var FieldMapping[] $mapping */
    private $mappings;

    public function __construct(SearchCondition $searchCondition)
    {
        $this->searchCondition = $searchCondition;
        $this->fieldSet = $searchCondition->getFieldSet();
        // $this->mappings = ['id' => $mapping, 'name' => $mapping2]; // TODO MultiMatch
    }

    public function registerField(string $fieldName, string $mapping)
    {
        $this->mappings[$fieldName] = new FieldMapping($fieldName, $mapping, $this->fieldSet->get($fieldName));
    }

    /**
     * This uses the `multi_match` instead of mapping the field multiple times,
     * and allows for more flexibility tailored to Elasticsearch.
     *
     * @param string $fieldName
     *
     * @return MultiFieldMapping
     */
    public function registerMultiField(string $fieldName)
    {
    }

    public function getQuery(): ?array
    {
        $rootGroupCondition = $this->processGroup($this->searchCondition->getValuesGroup());

        if ([] === $rootGroupCondition) {
            return null;
        }

        return [self::QUERY => $rootGroupCondition];
    }

    /**
     * @return FieldMapping[]
     */
    public function getMappings(): array
    {
        $mappings = [];

        $group = $this->searchCondition->getValuesGroup();
        foreach ($group->getFields() as $fieldName => $valuesBag) {
            if ($valuesBag->hasSimpleValues()) {
                $mappings[$fieldName] = $this->mappings[$fieldName];
            }

            if ($valuesBag->has(Range::class)) {
                $mappings[$fieldName] = $this->mappings[$fieldName];
            }

            if ($valuesBag->has(Compare::class)) {
                $mappings[$fieldName] = $this->mappings[$fieldName];
            }
        }

        return array_values($mappings);
    }

    /**
     * @param Range $range
     *
     * @return array
     */
    public static function generateRangeParams(Range $range): array
    {
        return [
            $range->isLowerInclusive() ? 'gte' : 'gt' => $range->getLower(),
            $range->isUpperInclusive() ? 'lte' : 'lt' => $range->getUpper(),
        ];
    }

    private function processGroup(ValuesGroup $group): array
    {
        // Note: Excludes are `must_not`, for includes `must` (AND) or `should` (OR) is used. Subgroups use `must`.
        $includingType = ValuesGroup::GROUP_LOGICAL_AND === $group->getGroupLogical()
            ? self::CONDITION_AND
            : self::CONDITION_OR;

        $bool = [];
        $hints = new QueryPreparationHints();
        foreach ($group->getFields() as $fieldName => $valuesBag) {
            // TODO: this looks fishy, what about nested fields?
            $mapping = $this->mappings[$fieldName];

            $propertyName = $mapping->propertyName;
            $valueConverter = $mapping->valueConversion;
            $queryConverter = $mapping->queryConversion;
            $nested = $mapping->nested;

            $hints->identifier = (self::PROPERTY_ID === $propertyName);
            $callback = function ($value) use ($valueConverter) {
                return $this->convertValue($value, $valueConverter);
            };

            // simple values
            if ($valuesBag->hasSimpleValues()) {
                $values = array_map($callback, array_values($valuesBag->getSimpleValues()), [$valueConverter]);
                $hints->context = QueryPreparationHints::CONTEXT_SIMPLE_VALUES;
                $bool[$includingType][] = $this->prepareQuery($propertyName, $values, $hints, $queryConverter, $nested);
            }
            if ($valuesBag->hasExcludedSimpleValues()) {
                $values = array_map($callback, array_values($valuesBag->getExcludedSimpleValues()), [$valueConverter]);
                $hints->context = QueryPreparationHints::CONTEXT_EXCLUDED_SIMPLE_VALUES;
                $bool[self::CONDITION_NOT][] = $this->prepareQuery($propertyName, $values, $hints, $queryConverter, $nested);
            }

            // ranges
            if ($valuesBag->has(Range::class)) {
                /** @var Range $range */
                foreach ($valuesBag->get(Range::class) as $range) {
                    $range = $this->convertRangeValues($range, $valueConverter);
                    $hints->context = QueryPreparationHints::CONTEXT_RANGE_VALUES;
                    $bool[$includingType][] = $this->prepareQuery($propertyName, $range, $hints, $queryConverter, $nested);
                }
            }
            if ($valuesBag->has(ExcludedRange::class)) {
                /** @var Range $range */
                foreach ($valuesBag->get(ExcludedRange::class) as $range) {
                    $range = $this->convertRangeValues($range, $valueConverter);
                    $hints->context = QueryPreparationHints::CONTEXT_EXCLUDED_RANGE_VALUES;
                    $bool[self::CONDITION_NOT][] = $this->prepareQuery($propertyName, $range, $hints, $queryConverter, $nested);
                }
            }

            /** @var Compare $compare */
            foreach ($valuesBag->get(Compare::class) as $compare) {
                if ('<>' === ($operator = $compare->getOperator())) {
                    $bool[self::CONDITION_NOT][][self::QUERY_TERM] = [$propertyName => [self::QUERY_VALUE => $compare->getValue()]];
                } else {
                    $bool[$includingType][] = [
                        $propertyName => [
                            self::COMPARE_OPR_TYPE[$operator] => $compare->getValue(),
                        ],
                    ];
                }
            }

            $this->processPatternMatchers($valuesBag->get(PatternMatch::class), $propertyName, $bool, $includingType);
        }

        foreach ($group->getGroups() as $subGroup) {
            $subGroupCondition = $this->processGroup($subGroup);

            if ([] !== $subGroupCondition) {
                $bool[self::CONDITION_AND][] = $subGroupCondition;
            }
        }

        if ([] === $bool) {
            return [];
        }

        return [self::QUERY_BOOL => $bool];
    }

    private function processPatternMatchers(array $values, string $propertyName, array &$bool, string $includingType)
    {
        // Note. Elasticsearch supports case-insensitive only at index level.

        /** @var PatternMatch $patternMatch */
        foreach ($values as $patternMatch) {
            $value = [];

            switch ($patternMatch->getType()) {
                // Faster then Wildcard but less accurate.
                // XXX Allow to configure `fuzzy`, `operator`, `zero_terms_query` and `cutoff_frequency` (TextType).
                case PatternMatch::PATTERN_CONTAINS:
                case PatternMatch::PATTERN_NOT_CONTAINS:
                    $value[self::QUERY_MATCH] = [$propertyName => [self::QUERY => $patternMatch->getValue()]];
                    break;

                case PatternMatch::PATTERN_STARTS_WITH:
                case PatternMatch::PATTERN_NOT_STARTS_WITH:
                    $value[self::QUERY_PREFIX] = [$propertyName => [self::QUERY_VALUE => $patternMatch->getValue()]];
                    break;

                case PatternMatch::PATTERN_ENDS_WITH:
                case PatternMatch::PATTERN_NOT_ENDS_WITH:
                    $value[self::QUERY_WILDCARD] = [
                        $propertyName => [self::QUERY_VALUE => '?'.addcslashes($patternMatch->getValue(), '?*')],
                    ];
                    break;

                case PatternMatch::PATTERN_EQUALS:
                case PatternMatch::PATTERN_NOT_EQUALS:
                    $value[self::QUERY_TERM] = [$propertyName => [self::QUERY_VALUE => $patternMatch->getValue()]];
                    break;

                default:
                    $message = sprintf('Not supported PatternMatch type "%s"', $patternMatch->getType());
                    throw new BadMethodCallException($message);
            }

            if ($patternMatch->isExclusive()) {
                $bool[self::CONDITION_NOT][] = $value;
            } else {
                $bool[$includingType][] = $value;
            }
        }
    }

    /**
     * @param mixed                $value
     * @param null|ValueConversion $converter
     *
     * @return mixed
     */
    private function convertValue($value, ?ValueConversion $converter)
    {
        if (null === $converter) {
            return $value;
        }

        return $converter->convertValue($value);
    }

    /**
     * @param Range           $range
     * @param ValueConversion $converter
     *
     * @return Range
     */
    private function convertRangeValues(Range $range, ?ValueConversion $converter): Range
    {
        return new Range(
            $this->convertValue($range->getLower(), $converter),
            $this->convertValue($range->getUpper(), $converter),
            $range->isLowerInclusive(),
            $range->isUpperInclusive()
        );
    }

    /**
     * @param string                $propertyName
     * @param mixed                 $value
     * @param QueryPreparationHints $hints
     * @param null|QueryConversion  $converter
     * @param array|bool            $nested
     *
     * @return array
     */
    private function prepareQuery(string $propertyName, $value, QueryPreparationHints $hints, ?QueryConversion $converter, $nested): array
    {
        if (null === $converter || null === ($query = $converter->convertQuery($propertyName, $value, $hints))) {
            switch ($hints->context) {
                case QueryPreparationHints::CONTEXT_RANGE_VALUES:
                case QueryPreparationHints::CONTEXT_EXCLUDED_RANGE_VALUES:
                    $query = [self::QUERY_RANGE => [$propertyName => static::generateRangeParams($value)]];
                    if ($hints->identifier) {
                        // IDs cannot be queries by range in Elasticsearch, use ids query
                        // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-ids-query.html
                        /** @var Range $value */
                        $query = [
                            self::QUERY_IDS => [
                                self::QUERY_VALUES => range($value->getLower(), $value->getUpper()),
                            ],
                        ];
                    }
                    break;
                default:
                case QueryPreparationHints::CONTEXT_SIMPLE_VALUES:
                case QueryPreparationHints::CONTEXT_EXCLUDED_SIMPLE_VALUES:
                    // simple values
                    $query = [self::QUERY_TERMS => [$propertyName => $value]];
                    if ($hints->identifier) {
                        $query = [self::QUERY_IDS => [self::QUERY_VALUES => $value]];
                    }
                    break;
            }
        }

        if ($nested) {
            while (false !== $nested) {
                $query = [
                    self::QUERY_NESTED => [
                        'path' => $nested['path'],
                        'query' => $query,
                    ],
                ];
                $nested = $nested['nested'];
            }
        }

        return $query;
    }
}
