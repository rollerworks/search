<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\ConditionOptimizer;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\SearchConditionOptimizerInterface;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Removes duplicated values.
 *
 * Duplicated values are only scanned per ValuesBag, so if a subgroup
 * has a value also present at a higher level its not removed.
 *
 *  Doing so would require to keep track of all the previous values per type.
 *  Which can get very complicated very easily.
 *
 * Values are compared using the {@see \Rollerworks\Component\Search\ValueComparisonInterface}.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DuplicateRemover implements SearchConditionOptimizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(SearchConditionInterface $condition)
    {
        $this->removeDuplicatesInGroup($condition->getValuesGroup(), $condition->getFieldSet());
    }

    private function removeDuplicatesInGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            $this->removeDuplicatesInValuesBag($fieldSet->get($fieldName), $values);
        }

        // Traverse the subgroups.
        foreach ($valuesGroup->getGroups() as $group) {
            $this->removeDuplicatesInGroup($group, $fieldSet);
        }
    }

    private function removeDuplicatesInValuesBag(FieldConfigInterface $config, ValuesBag $valuesBag)
    {
        $comparison = $config->getValueComparison();
        $options = $config->getOptions();

        $this->removeDuplicateValues($valuesBag->getSingleValues(), $valuesBag, $comparison, $options);
        $this->removeDuplicateValues($valuesBag->getExcludedValues(), $valuesBag, $comparison, $options, true);

        $this->removeDuplicateRanges($valuesBag->getRanges(), $valuesBag, $comparison, $options);
        $this->removeDuplicateRanges($valuesBag->getExcludedRanges(), $valuesBag, $comparison, $options, true);

        $this->removeDuplicateComparisons($valuesBag, $comparison, $options);
        $this->removeDuplicateMatchers($valuesBag, $comparison, $options);
    }

    /**
     * @param SingleValue[]            $values
     * @param ValuesBag                $valuesBag
     * @param ValueComparisonInterface $comparison
     * @param array                    $options
     * @param bool                     $exclude
     */
    private function removeDuplicateValues(
        array $values,
        ValuesBag $valuesBag,
        ValueComparisonInterface $comparison,
        array $options,
        $exclude = false
    ) {
        foreach ($values as $i => $value) {
            foreach ($values as $c => $value2) {
                if ($i === $c || !$comparison->isEqual($value->getValue(), $value2->getValue(), $options)) {
                    continue;
                }

                if ($exclude) {
                    $valuesBag->removeExcludedValue($i);
                } else {
                    $valuesBag->removeSingleValue($i);
                }

                unset($values[$i]);
            }
        }
    }

    /**
     * @param Range[]                  $ranges
     * @param ValuesBag                $valuesBag
     * @param ValueComparisonInterface $comparison
     * @param array                    $options
     * @param bool                     $exclude
     */
    private function removeDuplicateRanges(
        array $ranges,
        ValuesBag $valuesBag,
        ValueComparisonInterface $comparison,
        array $options,
        $exclude = false
    ) {
        foreach ($ranges as $i => $value) {
            foreach ($ranges as $c => $value2) {
                if ($i === $c) {
                    continue;
                }

                // Only compare when both inclusive's are equal.
                if ($value->isLowerInclusive() !== $value2->isLowerInclusive() ||
                    $value->isUpperInclusive() !== $value2->isUpperInclusive()
                ) {
                    continue;
                }

                if (!$comparison->isEqual($value->getLower(), $value2->getLower(), $options) ||
                    !$comparison->isEqual($value->getUpper(), $value2->getUpper(), $options)
                ) {
                    continue;
                }

                if ($exclude) {
                    $valuesBag->removeExcludedRange($i);
                } else {
                    $valuesBag->removeRange($i);
                }

                unset($ranges[$i]);
            }
        }
    }

    /**
     * @param ValuesBag                $valuesBag
     * @param ValueComparisonInterface $comparison
     * @param array                    $options
     */
    private function removeDuplicateComparisons(ValuesBag $valuesBag, ValueComparisonInterface $comparison, array $options)
    {
        $comparisons = $valuesBag->getComparisons();

        foreach ($comparisons as $i => $value) {
            foreach ($comparisons as $c => $value2) {
                if ($i === $c) {
                    continue;
                }

                if ($value->getOperator() === $value2->getOperator() &&
                    $comparison->isEqual($value->getValue(), $value2->getValue(), $options)
                ) {
                    $valuesBag->removeComparison($i);
                    unset($comparisons[$i]);
                }
            }
        }
    }

    /**
     * @param ValuesBag                $valuesBag
     * @param ValueComparisonInterface $comparison
     * @param array                    $options
     */
    private function removeDuplicateMatchers(ValuesBag $valuesBag, ValueComparisonInterface $comparison, array $options)
    {
        $matchers = $valuesBag->getPatternMatchers();

        foreach ($matchers as $i => $value) {
            foreach ($matchers as $c => $value2) {
                if ($i === $c) {
                    continue;
                }

                if ($value->isCaseInsensitive() === $value2->isCaseInsensitive() &&
                    $value->getType() === $value2->getType() &&
                    $comparison->isEqual($value->getValue(), $value2->getValue(), $options)
                ) {
                    $valuesBag->removePatternMatch($i);
                    unset($matchers[$i]);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 5;
    }
}
