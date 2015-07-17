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
 * Removes overlapping ranges/values and merges connected ranges.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RangeOptimizer implements SearchConditionOptimizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(SearchConditionInterface $condition)
    {
        $fieldSet = $condition->getFieldSet();
        $supportsRanges = false;

        foreach ($fieldSet->all() as $field) {
            if ($field->supportValueType(ValuesBag::VALUE_TYPE_RANGE)) {
                $supportsRanges = true;

                break;
            }
        }

        // None of the fields supports ranges so don't optimize.
        if (!$supportsRanges) {
            return;
        }

        $this->normalizeRangesInGroup($condition->getValuesGroup(), $fieldSet);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return -5;
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     */
    private function normalizeRangesInGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            $config = $fieldSet->get($fieldName);

            if ($values->hasRanges() || $values->hasExcludedRanges()) {
                $this->normalizeRangesInValuesBag($config, $values);
            }
        }

        // Traverse the subgroups.
        foreach ($valuesGroup->getGroups() as $group) {
            $this->normalizeRangesInGroup($group, $fieldSet);
        }
    }

    /**
     * @param FieldConfigInterface $config
     * @param ValuesBag            $valuesBag
     */
    private function normalizeRangesInValuesBag(FieldConfigInterface $config, ValuesBag $valuesBag)
    {
        $comparison = $config->getValueComparison();
        $options = $config->getOptions();

        // Optimize the ranges before single values, so we have less ranges to loop trough.
        // Each operation is run separate to reduce complexity and prevent hard to find bugs,
        // this results in less performance but better readability;
        // Results should be cached anyway.

        if ($valuesBag->hasRanges()) {
            $this->removeOverlappingRanges(
                $valuesBag->getRanges(),
                $valuesBag,
                $comparison,
                $options
            );

            $this->optimizeConnectedRanges(
                $valuesBag->getRanges(),
                $valuesBag,
                $comparison,
                $options
            );

            $this->removeOverlappingSingleValues(
                $valuesBag->getSingleValues(),
                $valuesBag->getRanges(),
                $valuesBag,
                $comparison,
                $options
            );
        }

        if ($valuesBag->hasExcludedRanges()) {
            $this->removeOverlappingRanges(
                $valuesBag->getExcludedRanges(),
                $valuesBag,
                $comparison,
                $options,
                true
            );

            $this->optimizeConnectedRanges(
                $valuesBag->getExcludedRanges(),
                $valuesBag,
                $comparison,
                $options,
                true
            );

            $this->removeOverlappingSingleValues(
                $valuesBag->getExcludedValues(),
                $valuesBag->getExcludedRanges(),
                $valuesBag,
                $comparison,
                $options,
                true
            );
        }
    }

    /**
     * Removes single values overlapping in ranges.
     *
     * For example: 5 overlaps in 1 - 10
     *
     * @param SingleValue[]            $singleValues
     * @param Range[]                  $ranges
     * @param ValuesBag                $valuesBag
     * @param ValueComparisonInterface $comparison
     * @param array                    $options
     * @param bool                     $exclude
     */
    private function removeOverlappingSingleValues(
        array $singleValues,
        array $ranges,
        ValuesBag $valuesBag,
        ValueComparisonInterface $comparison,
        array $options,
        $exclude = false
    ) {
        foreach ($ranges as $i => $range) {
            foreach ($singleValues as $c => $value) {
                if ($this->isValInRange($value, $range, $comparison, $options)) {
                    if ($exclude) {
                        $valuesBag->removeExcludedValue($c);
                    } else {
                        $valuesBag->removeSingleValue($c);
                    }
                }
            }
        }
    }

    /**
     * Removes ranges overlapping in other ranges.
     *
     * For example: 5 - 10 overlaps in 1 - 20
     *
     * @param Range[]                  $ranges
     * @param ValuesBag                $valuesBag
     * @param ValueComparisonInterface $comparison
     * @param array                    $options
     * @param bool                     $exclude
     */
    private function removeOverlappingRanges(
        array $ranges,
        ValuesBag $valuesBag,
        ValueComparisonInterface $comparison,
        array $options,
        $exclude = false
    ) {
        foreach ($ranges as $i => $range) {
            // If the range is already removed just ignore it.
            if (!isset($ranges[$i])) {
                continue;
            }

            foreach ($ranges as $c => $value) {
                if ($i === $c) {
                    continue;
                }

                if ($this->isRangeInRange($value, $range, $comparison, $options)) {
                    if ($exclude) {
                        $valuesBag->removeExcludedRange($c);
                    } else {
                        $valuesBag->removeRange($c);
                    }

                    unset($ranges[$c]);
                }
            }
        }
    }

    /**
     * Optimizes connected ranges.
     *
     * A range is connected when the upper-bound is equal to the lower-bound of the
     * second range, but only when the bounds inclusiveness are equal they can be optimized.
     *
     * @param Range[]                  $ranges
     * @param ValuesBag                $valuesBag
     * @param ValueComparisonInterface $comparison
     * @param array                    $options
     * @param bool                     $exclude
     */
    private function optimizeConnectedRanges(
        array $ranges,
        ValuesBag $valuesBag,
        ValueComparisonInterface $comparison,
        array $options,
        $exclude = false
    ) {
        foreach ($ranges as $i => $range) {
            // If the range is already removed just ignore it.
            if (!isset($ranges[$i])) {
                continue;
            }

            foreach ($ranges as $c => $value) {
                if ($i === $c) {
                    continue;
                }

                if ($range->isLowerInclusive() !== $value->isLowerInclusive() ||
                    $range->isUpperInclusive() !== $value->isUpperInclusive()
                ) {
                    continue;
                }

                if ($comparison->isEqual($range->getUpper(), $value->getLower(), $options)) {
                    $newRange = new Range(
                        $range->getLower(),
                        $value->getUpper(),
                        $range->isLowerInclusive(),
                        $range->isUpperInclusive(),
                        $range->getViewLower(),
                        $value->getViewUpper()
                    );

                    // Remove original ranges and add a new merged range.
                    if ($exclude) {
                        $valuesBag->removeExcludedRange($i);
                        $valuesBag->removeExcludedRange($c);
                        $valuesBag->addExcludedRange($newRange);
                    } else {
                        $valuesBag->removeRange($i);
                        $valuesBag->removeRange($c);
                        $valuesBag->addRange($newRange);
                    }

                    unset($ranges[$i], $ranges[$c]);
                }
            }
        }
    }

    /**
     * Returns whether $singeValue is overlapping in $range.
     *
     * @param SingleValue              $singeValue
     * @param Range                    $range
     * @param ValueComparisonInterface $comparison
     * @param array                    $options
     *
     * @return bool
     */
    private function isValInRange(SingleValue $singeValue, Range $range, ValueComparisonInterface $comparison, $options)
    {
        $value = $singeValue->getValue();

        // Test its not overlapping, when this fails then its save to assert there is an overlap.

        if (!$comparison->isHigher($value, $range->getLower(), $options) &&
            (!$range->isLowerInclusive() xor !$comparison->isEqual($value, $range->getLower(), $options))
        ) {
            return false;
        }

        return !(!$comparison->isLower($value, $range->getUpper(), $options) &&
            (!$range->isUpperInclusive() xor !$comparison->isEqual($value, $range->getUpper(), $options)));
    }

    /**
     * Returns whether $range1 is overlapping in $range.
     *
     * @param Range                    $range1
     * @param Range                    $range
     * @param ValueComparisonInterface $comparison
     * @param array                    $options
     *
     * @return bool
     */
    private function isRangeInRange(Range $range1, Range $range, ValueComparisonInterface $comparison, $options)
    {
        if (!$comparison->isHigher($range1->getLower(), $range->getLower(), $options) &&
            !$this->isBoundEqual(
                $comparison,
                $options,
                $range1->getLower(),
                $range->getLower(),
                $range->isLowerInclusive(),
                $range1->isLowerInclusive()
            )
        ) {
            return false;
        }

        return !(!$comparison->isLower($range1->getUpper(), $range->getUpper(), $options) &&
            !$this->isBoundEqual(
                $comparison,
                $options,
                $range1->getUpper(),
                $range->getUpper(),
                $range->isUpperInclusive(),
                $range1->isUpperInclusive()
            )
        );
    }

    private function isBoundEqual(
        ValueComparisonInterface $comparison,
        $options,
        $value1,
        $value2,
        $value1Inclusive,
        $value2Inclusive
    ) {
        if (!$comparison->isEqual($value1, $value2, $options)) {
            return false;
        }

        if ($value1Inclusive === $value2Inclusive) {
            return true;
        }

        return true === $value1Inclusive;
    }
}
