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

    private const QUERY_BOOL = 'bool';
    private const QUERY_MATCH = 'match';
    private const QUERY_TERM = 'term';
    private const QUERY_TERMS = 'terms';

    // Elasticsearch boolean operators
    private const CONDITION_NOT = 'must_not';
    private const CONDITION_AND = 'must';
    private const CONDITION_OR = 'should';

    private $searchCondition;
    private $fieldSet;

    private const COMPARE_OPR_TYPE = ['>=' => 'gte', '<=' => 'lte', '<' => 'lt', '>' => 'gt'];

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

        return ['query' => $rootGroupCondition];
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

    private function processGroup(ValuesGroup $group): array
    {
        // Note: Excludes are `must_not`, for includes `must` (AND) or `should` (OR) is used. Subgroups use `must`.
        $includingType = ValuesGroup::GROUP_LOGICAL_AND === $group->getGroupLogical()
            ? self::CONDITION_AND
            : self::CONDITION_OR;

        $bool = [];
        foreach ($group->getFields() as $fieldName => $valuesBag) {
            // TODO: this looks fishy, what about nested fields?
            $propertyName = $this->mappings[$fieldName]->propertyName;

            $field = $this->fieldSet->get($fieldName);
            $convertToRange = $field->getOption('elasticsearch_convert_to_range');
            $converter = $field->getOption('elasticsearch_conversion');
            $callback = [$this, 'convertValue'];

            if ($valuesBag->hasSimpleValues()) {
                $values = array_map($callback, array_values($valuesBag->getSimpleValues()), [$converter]);
                if ($convertToRange) {
                    $bool[$includingType][] = $this->convertToRange($propertyName, $values);
                } else {
                    $bool[$includingType][] = ['terms' => [$propertyName => $values]];
                }
            }

            if ($valuesBag->hasExcludedSimpleValues()) {
                $values = array_map($callback, array_values($valuesBag->getExcludedSimpleValues()), [$converter]);
                if ($convertToRange) {
                    $bool[self::CONDITION_NOT][] = $this->convertToRange($propertyName, $values);
                } else {
                    $bool[self::CONDITION_NOT][] = ['terms' => [$propertyName => $values]];
                }
            }

            $isId = self::PROPERTY_ID === $propertyName;
            if (false === $isId) {
                /** @var Range $range */
                foreach ($valuesBag->get(Range::class) as $range) {
                    $bool[$includingType][]['range'][$propertyName] = $this->generateRangeParams($range);
                }

                foreach ($valuesBag->get(ExcludedRange::class) as $range) {
                    $bool[self::CONDITION_NOT][]['range'][$propertyName] = $this->generateRangeParams($range);
                }
            } else {
                // IDs cannot be queries by range in Elasticsearch, use ids query
                // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-ids-query.html
                $ids = [];
                foreach ($valuesBag->get(Range::class) as $range) {
                    $ids = array_merge($ids, range($range->getLower(), $range->getUpper()));
                }
                if (false === empty($ids)) {
                    $bool[$includingType][] = ['ids' => ['values' => $ids]];
                }

                $excludeIds = [];
                foreach ($valuesBag->get(ExcludedRange::class) as $range) {
                    $excludeIds = array_merge($excludeIds, range($range->getLower(), $range->getUpper()));
                }
                if (false === empty($excludeIds)) {
                    $bool[self::CONDITION_NOT][] = ['ids' => ['values' => $excludeIds]];
                }
            }

            /** @var Compare $compare */
            foreach ($valuesBag->get(Compare::class) as $compare) {
                if ('<>' === ($operator = $compare->getOperator())) {
                    $bool[self::CONDITION_NOT][]['term'] = [$propertyName => ['value' => $compare->getValue()]];
                } else {
                    $bool[$includingType][] = [
                        $propertyName => [
                            self::COMPARE_OPR_TYPE[$operator] => $compare->getValue(),
                        ],
                    ];
                }
            }

            $this->processPatternMatchers($valuesBag->get(PatternMatch::class), $fieldName, $bool, $includingType);
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

        return ['bool' => $bool];
    }

    private function processPatternMatchers(array $values, string $fieldName, array &$bool, string $includingType)
    {
        // Note. Elasticsearch supports case-insensitive only at index level.

        $propertyName = $this->mappings[$fieldName]->propertyName;

        /** @var PatternMatch $patternMatch */
        foreach ($values as $patternMatch) {
            $value = [];

            switch ($patternMatch->getType()) {
                // Faster then Wildcard but less accurate. XXX Allow to configure `fuzzy`, `operator`, `zero_terms_query` and `cutoff_frequency` (TextType).
                case PatternMatch::PATTERN_CONTAINS:
                case PatternMatch::PATTERN_NOT_CONTAINS:
                    $value['match'] = [$propertyName => ['query' => $patternMatch->getValue()]];
                    break;

                case PatternMatch::PATTERN_STARTS_WITH:
                case PatternMatch::PATTERN_NOT_STARTS_WITH:
                    $value['prefix'] = [$propertyName => ['value' => $patternMatch->getValue()]];
                    break;

                case PatternMatch::PATTERN_ENDS_WITH:
                case PatternMatch::PATTERN_NOT_ENDS_WITH:
                    $value['wildcard'] = [$propertyName => ['value' => '?'.addcslashes($patternMatch->getValue(), '?*')]];
                    break;

                case PatternMatch::PATTERN_EQUALS:
                case PatternMatch::PATTERN_NOT_EQUALS:
                    $value['term'] = [$propertyName => ['value' => $patternMatch->getValue()]];
                    break;

                default:
                    throw new BadMethodCallException(sprintf('Not supported PatternMatch type "%s"', $patternMatch->getType()));
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
     * @param Range $range
     *
     * @return array
     */
    private function generateRangeParams(Range $range): array
    {
        return [
            $range->isLowerInclusive() ? 'lte' : 'lt' => $range->getLower(),
            $range->isUpperInclusive() ? 'gte' : 'gt' => $range->getUpper(),
        ];
    }

    /**
     * @param string          $propertyName
     * @param array           $values
     * @return array
     */
    private function convertToRange(string $propertyName, array $values): array
    {
        $range = [];
        foreach ($values as $value) {
            $range['bool']['should'][]['range'][$propertyName] = $this->generateRangeParams(new Range($value, $value));
        }

        return $range;
    }
}
