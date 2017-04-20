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

use Elastica\Param;
use Elastica\Query\{
    BoolQuery, Match, MultiMatch, Prefix, Range as ESRange, Regexp, Term, Terms, Wildcard
};
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\{
    Compare, ExcludedRange, PatternMatch, Range, ValuesGroup
};

// Allow to mark Field as id https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-ids-query.html

class QueryConditionGenerator
{
    private $searchCondition;
    private $fieldSet;

    private const COMPARE_OPR_TYPE = ['>=' => 'gte', '<=' => 'lte', '<' => 'lt', '>' => 'gt'];

    /** @var FieldMapping[] $mapping */
    private $mappings;

    public function __construct(SearchCondition $searchCondition)
    {
        $mapping = new FieldMapping();
        $mapping->indexName = 'id';

        $mapping2 = new FieldMapping();
        $mapping2->indexName = 'name';

        $this->searchCondition = $searchCondition;
        $this->fieldSet = $searchCondition->getFieldSet();
        $this->mappings = ['id' => $mapping, 'name' => $mapping2]; // TODO MultiMatch
    }

    public function registerField(string $fieldName): FieldMapping
    {
    }

    /**
     * This uses the `multi_match` instead of mapping the field multiple times,
     * and allows for more flexibility tailored to Elasticsearch.
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
        $bool = new BoolQuery();

        // Note: Excludes are `must_not`, for includes `must` (AND) or `should` (OR) is used. Subgroups use `must`.
        $includingMethod = ValuesGroup::GROUP_LOGICAL_AND === $group->getGroupLogical() ? 'addMust' : 'addShould';

        // FIXME Objects need type formatter. `elastic_search_value_transformer` (use extensions to configure types)

        foreach ($group->getFields() as $fieldName => $valuesBag) {
            if ($valuesBag->hasSimpleValues()) {
                $bool->{$includingMethod}(new Terms($this->mappings[$fieldName]->indexName, $valuesBag->getSimpleValues()));
            }

            if ($valuesBag->hasExcludedSimpleValues()) {
                $bool->addMustNot(new Terms($this->mappings[$fieldName]->indexName, $valuesBag->getExcludedSimpleValues()));
            }

            /** @var Range $range */
            foreach ($valuesBag->get(Range::class) as $range) {
                $rangeParams = [
                    $range->isLowerInclusive() ? 'lte' : 'lt' => $range->getLower(),
                    $range->isUpperInclusive() ? 'gte' : 'gt' => $range->getUpper(),
                ];

                $bool->{$includingMethod}(new ESRange($this->mappings[$fieldName]->indexName, $rangeParams));
            }

            foreach ($valuesBag->get(ExcludedRange::class) as $range) {
                $rangeParams = [
                    $range->isLowerInclusive() ? 'lte' : 'lt' => $range->getLower(),
                    $range->isUpperInclusive() ? 'gte' : 'gt' => $range->getUpper(),
                ];

                $bool->addMustNot(new ESRange($this->mappings[$fieldName]->indexName, $rangeParams));
            }

            /** @var Compare $compare */
            foreach ($valuesBag->get(Compare::class) as $compare) {
                if ($operator = $compare->getOperator() === '<>') {
                    $bool->addMustNot(new Term($this->mappings[$fieldName]->indexName, $compare->getValue()));
                } else {
                    $bool->{$includingMethod}(new ESRange($this->mappings[$fieldName]->indexName, [self::COMPARE_OPR_TYPE[$operator] => $compare->getValue()]));
                }
            }

            $this->processPatternMatchers($valuesBag->get(PatternMatch::class), $fieldName, $bool, $includingMethod);
        }

        foreach ($group->getGroups() as $subGroup) {
            $subGroupCondition = $this->processGroup($subGroup);

            if ([] !== $subGroupCondition->getParams()) {
                $bool->addMust($subGroupCondition);
            }
        }

        return $bool;
    }

    private function processPatternMatchers(array $values, string $fieldName, BoolQuery $bool, string $includingMethod)
    {
        // Note. Elasticsearch supports case-insensitive only at index level.

        /** @var PatternMatch $patternMatch */
        foreach ($values as $patternMatch) {
            switch ($patternMatch->getType()) {
                // Faster then Wildcard but less accurate. Allow to configure `fuzzy`, `operator`, `zero_terms_query` and `cutoff_frequency` (TextType).
                case PatternMatch::PATTERN_CONTAINS:
                case PatternMatch::PATTERN_NOT_CONTAINS:
                    $value = new Match($this->mappings[$fieldName]->indexName, $patternMatch->getValue());
                    break;

                case PatternMatch::PATTERN_STARTS_WITH:
                case PatternMatch::PATTERN_NOT_STARTS_WITH:
                    $value = (new Prefix())->setPrefix(
                        $this->mappings[$fieldName]->indexName,
                        $patternMatch->getValue()
                    );
                    break;

                case PatternMatch::PATTERN_ENDS_WITH:
                case PatternMatch::PATTERN_NOT_ENDS_WITH:
                    $value = new Wildcard(
                        $this->mappings[$fieldName]->indexName,
                        '?'.addcslashes($patternMatch->getValue(), '?*')
                    );
                    break;

                case PatternMatch::PATTERN_REGEX:
                case PatternMatch::PATTERN_NOT_REGEX:
                    $value = new Regexp($this->mappings[$fieldName]->indexName, $patternMatch->getValue());
                    break;

                default:
                    throw new InvalidSearchConditionException(
                        sprintf('PatternMatch type "%s"', $patternMatch->getType())
                    );
            }

            if ($patternMatch->isExclusive()) {
                $bool->addMustNot($value);
            } else {
                $bool->{$includingMethod}($value);
            }
        }
    }
}
