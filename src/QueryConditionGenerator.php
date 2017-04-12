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

namespace Rollerworks\Component\Search\ElasticSearch;

use Elastica\Param;
use Elastica\Query\{
    BoolQuery, MultiMatch, Range as ESRange, Term, Terms
};
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\{
    Compare, ExcludedRange, PatternMatch, Range, ValuesGroup
};

class QueryConditionGenerator
{
    private $searchCondition;
    private $fieldSet;
    /** @var FieldMapping[] $mapping */
    private $mappings;

    public function __construct(SearchCondition $searchCondition)
    {
        $this->searchCondition = $searchCondition;
        $this->fieldSet = $searchCondition->getFieldSet();
        $this->mappings = []; // TODO MultiMatch
    }

    public function registerField(string $fieldName): FieldMapping
    {
    }

    /**
     * This uses the `multi_match` instead of mapping the field multiple times,
     * and allows for more flexibility tailored to ElasticSearch.
     *
     * @param string $fieldName
     *
     * @return MultiFieldMapping
     */
    public function registerMultiField(string $fieldName): MultiFieldMapping
    {
    }

    public function getQuery(): ?Param
    {
        $root = $this->searchCondition->getValuesGroup();
        $rootGroupCondition = $this->processGroup($root);

        if ([] === $rootGroupCondition->getParams()) {
            return null;
        }

        return $rootGroupCondition;
    }

    private function processGroup(ValuesGroup $group): BoolQuery
    {
        $compareToKey = ['>=' => 'gte', '<=' => 'lte', '<' => 'lt', '>' => 'gt'];
        $bool = new BoolQuery();
        $includes = [];
        $excludes = [];

        // Note: Excludes are always must_not, for includes `must` (AND) or `should` (OR) is used.
        // Subgroups are always `must`.

        // FIXME Objects need for type formatter. `elastic_search_value_transformer` (use extensions to configure types)

        foreach ($group->getFields() as $fieldName => $valuesBag) {
            if ($valuesBag->hasSimpleValues()) {
                $includes[] = new Terms($this->mappings[$fieldName]->indexName, $valuesBag->getSimpleValues());
            }

            if ($valuesBag->hasExcludedSimpleValues()) {
                $excludes[] = new Terms($this->mappings[$fieldName]->indexName, $valuesBag->getExcludedSimpleValues());
            }

            /** @var Range $range */
            foreach ($valuesBag->get(Range::class) as $range) {
                $rangeParams = [
                    $range->isLowerInclusive() ? 'lte' : 'lt' => $range->getLower(),
                    $range->isUpperInclusive() ? 'gte' : 'gt' => $range->getUpper(),
                ];

                $includes[] = new ESRange($this->mappings[$fieldName]->indexName, $rangeParams);
            }

            foreach ($valuesBag->get(ExcludedRange::class) as $range) {
                $rangeParams = [
                    $range->isLowerInclusive() ? 'lte' : 'lt' => $range->getLower(),
                    $range->isUpperInclusive() ? 'gte' : 'gt' => $range->getUpper(),
                ];

                $excludes[] = new ESRange($this->mappings[$fieldName]->indexName, $rangeParams);
            }

            /** @var Compare $compare */
            foreach ($valuesBag->get(Compare::class) as $compare) {
                if ($operator = $compare->getOperator() === '<>') {
                    $excludes[] = new Term($this->mappings[$fieldName]->indexName, $compare->getValue());

                    continue;
                }

                $includes[] = new ESRange($this->mappings[$fieldName]->indexName, [$compareToKey[$operator] => $compare->getValue()]);
            }

            /** @var PatternMatch $patternMatch */
            foreach ($valuesBag->get(PatternMatch::class) as $patternMatch) {
                if ($patternMatch->isExclusive()) {

                } else {

                }

                // $includes[] = new ESRange($this->mappings[$fieldName]->indexName, [$compareToKey[$operator] => $compare->getValue()]);
            }
        }

        if (!empty($includes)) {
            if (ValuesGroup::GROUP_LOGICAL_AND === $group->getGroupLogical()) {
                $bool->addMust($includes);
            } else {
                $bool->addShould($includes);
            }
        }

        if (!empty($excludes)) {
            $bool->addMustNot($excludes);
        }

        $groupsConditions = [];

        foreach ($group->getGroups() as $subGroup) {
            $subGroupCondition = $this->processGroup($subGroup);

            if ([] !== $subGroupCondition->getParams()) {
                $groupsConditions = $subGroupCondition;
            }
        }

        if (!empty($groupsConditions)) {
            $bool->addMust($groupsConditions);
        }

        return $bool;
    }
}
