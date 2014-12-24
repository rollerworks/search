<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\ConditionOptimizer;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionOptimizerInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
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
        $valuesGroup = $condition->getValuesGroup();
        $supportsRanges = false;

        foreach ($fieldSet->all() as $field) {
            if ($field->acceptRanges()) {
                $supportsRanges = true;

                break;
            }
        }

        // None of the fields supports ranges so don't optimize
        if (!$supportsRanges) {
            return;
        }

        $this->normalizeRangesInGroup($valuesGroup, $fieldSet);
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     */
    private function normalizeRangesInGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            if (!$fieldSet->has($fieldName)) {
                continue;
            }

            $config = $fieldSet->get($fieldName);

            if ($config->acceptRanges() && ($values->hasRanges() || $values->hasExcludedRanges())) {
                $this->normalizeRangesInValuesBag($config, $values);
            }
        }

        // now traverse the subgroups
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

        // Optimize the ranges before single values, so we have less ranges to loop trough
        // Each operation is run separate to prevent to much complexity, this results in less performance
        // but better readability; Results should be cached anyway

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
     * a range is connected when the upper-bound is equal to the lower-bound of the second range
     * only when the bounds inclusiveness are equal they can be optimized.
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

                    // Remove original ranges and add new merged range
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
        $overlap = false;
        $isLower = false;

        // This has been made very verbose to not make a complete spaghetti mess of it

        if ($range->isLowerInclusive() &&
            ($comparison->isEqual($singeValue->getValue(), $range->getLower(), $options) ||
             $comparison->isHigher($singeValue->getValue(), $range->getLower(), $options))
        ) {
            $isLower = true;
        } elseif (!$range->isLowerInclusive() &&
            $comparison->isHigher($singeValue->getValue(), $range->getLower(), $options)
        ) {
            $isLower = true;
        }

        // value is higher (or equal) then lower bound, so now check the lower bound
        if ($isLower) {
            if ($range->isUpperInclusive() &&
                (
                    $comparison->isEqual($singeValue->getValue(), $range->getUpper(), $options) ||
                    $comparison->isLower($singeValue->getValue(), $range->getUpper(), $options)
                )
            ) {
                $overlap = true;
            } elseif (!$range->isUpperInclusive() &&
                $comparison->isLower($singeValue->getValue(), $range->getUpper(), $options)
            ) {
                $overlap = true;
            }
        }

        return $overlap;
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
        $overlap = false;
        $isLower = false;

        // This has been made very verbose to not make a complete spaghetti mess of it
        // Ranges are more difficult as each can be inclusive and exclusive

        if ($range->isLowerInclusive()) {
            if ($range1->isLowerInclusive() &&
                (
                    $comparison->isEqual($range1->getLower(), $range->getLower(), $options) ||
                    $comparison->isHigher($range1->getLower(), $range->getLower(), $options)
                )
            ) {
                $isLower = true;
            } elseif (!$range1->isLowerInclusive() &&
                $comparison->isHigher($range1->getLower(), $range->getLower(), $options)
            ) {
                $isLower = true;
            }
        } elseif ($comparison->isHigher($range1->getLower(), $range->getLower(), $options)) {
            // If the first range is exclusive it makes no sense to equal-check the second
            $isLower = true;
        }

        // value is higher (or equal) then lower bound, so now check the lower bound
        if ($isLower) {
            if ($range->isUpperInclusive()) {
                if ($range1->isUpperInclusive() &&
                    (
                        $comparison->isEqual($range1->getUpper(), $range->getUpper(), $options) ||
                        $comparison->isLower($range1->getUpper(), $range->getUpper(), $options)
                    )
                ) {
                    $overlap = true;
                } elseif (!$range1->isUpperInclusive() &&
                    $comparison->isLower($range->getUpper(), $range1->getUpper(), $options)
                ) {
                    // Because the second upper-bound is exclusive we check the
                    $overlap = true;
                }
            } elseif ($comparison->isLower($range1->getUpper(), $range->getUpper(), $options)) {
                $overlap = true;
            }
        }

        return $overlap;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return -5;
    }
}
