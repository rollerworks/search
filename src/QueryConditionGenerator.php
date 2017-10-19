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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\{
    Compare, ExcludedRange, PatternMatch, Range, ValuesGroup
};

// XXX Allow to mark Field as id https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-ids-query.html

final class QueryConditionGenerator
{
    private const PROPERTY_ID = '_id';

    private $searchCondition;
    // private $fieldSet;

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
        $this->mappings[$fieldName] = new FieldMapping($fieldName, $mapping);
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
        $bool = [];

        // Note: Excludes are `must_not`, for includes `must` (AND) or `should` (OR) is used. Subgroups use `must`.
        $includingType = ValuesGroup::GROUP_LOGICAL_AND === $group->getGroupLogical() ? 'must' : 'should';

        // FIXME Objects need type formatter. `elastic_search_value_transformer` (use extensions to configure types)

        foreach ($group->getFields() as $fieldName => $valuesBag) {
            $propertyName = $this->mappings[$fieldName]->propertyName;
            $field = $this->fieldSet->get($fieldName);

            // TODO: hack, review and fix
            $fieldType = $field->getType()->getInnerType();
            $dateTransformer = new DateTimeToStringTransformer(null, 'UTC', 'Y-m-d');

            if ($valuesBag->hasSimpleValues()) {
                if ($fieldType instanceof DateType) {
                    // date lookups behave like a range even for an exact value in Elasticsearch
                    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html#ranges-on-dates
                    $values = array_values($valuesBag->getSimpleValues());
                    foreach ($values as $idx => $value) {
                        $valuesBag->removeSimpleValue($idx);
                        $value = $dateTransformer->transform($value);
                        $range = new Range($value, $value);
                        $valuesBag->add($range);
                    }
                } else {
                    $bool[$includingType][]['terms'] = [$propertyName => array_values($valuesBag->getSimpleValues())];
                }
            }

            if ($valuesBag->hasExcludedSimpleValues()) {
                $bool['must_not'][]['terms'] = [$propertyName => array_values($valuesBag->getExcludedSimpleValues())];
            }

            $isId = self::PROPERTY_ID === $propertyName;

            if (false === $isId) {
                /** @var Range $range */
                foreach ($valuesBag->get(Range::class) as $range) {
                    $rangeParams = [
                        $range->isLowerInclusive() ? 'lte' : 'lt' => $range->getLower(),
                        $range->isUpperInclusive() ? 'gte' : 'gt' => $range->getUpper(),
                    ];

                    $bool[$includingType][]['range'][$propertyName] = $rangeParams;
                }

                foreach ($valuesBag->get(ExcludedRange::class) as $range) {
                    $rangeParams = [
                        $range->isLowerInclusive() ? 'lte' : 'lt' => $range->getLower(),
                        $range->isUpperInclusive() ? 'gte' : 'gt' => $range->getUpper(),
                    ];

                    $bool['must_not'][]['range'][$propertyName] = $rangeParams;
                }
            } else {
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
                    $bool['must_not'][] = ['ids' => ['values' => $excludeIds]];
                }
            }

            /** @var Compare $compare */
            foreach ($valuesBag->get(Compare::class) as $compare) {
                if ('<>' === ($operator = $compare->getOperator())) {
                    $bool['must_not'][]['term'] = [$propertyName => ['value' => $compare->getValue()]];
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
                $bool['must'][] = $subGroupCondition;
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
                $bool['must_not'][] = $value;
            } else {
                $bool[$includingType][] = $value;
            }
        }
    }
}
