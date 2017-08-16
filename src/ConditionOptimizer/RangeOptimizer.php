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

namespace Rollerworks\Component\Search\ConditionOptimizer;

use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionOptimizer;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Rollerworks\Component\Search\ValueComparator;

/**
 * Removes overlapping ranges/values and merges connected ranges.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class RangeOptimizer implements SearchConditionOptimizer
{
    public function process(SearchCondition $condition): void
    {
        $fieldSet = $condition->getFieldSet();
        $supportsRanges = false;

        foreach ($fieldSet->all() as $field) {
            if ($field->supportValueType(Range::class)) {
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

    public function getPriority(): int
    {
        return -5;
    }

    private function normalizeRangesInGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet): void
    {
        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            $config = $fieldSet->get($fieldName);

            if ($values->has(Range::class) || $values->has(ExcludedRange::class)) {
                $this->normalizeRangesInValuesBag($config, $values);
            }
        }

        foreach ($valuesGroup->getGroups() as $group) {
            $this->normalizeRangesInGroup($group, $fieldSet);
        }
    }

    private function normalizeRangesInValuesBag(FieldConfig $config, ValuesBag $valuesBag): void
    {
        $comparator = $config->getValueComparator();
        $options = $config->getOptions();

        // Optimize the ranges before simple values, so we have less ranges to loop trough.
        // Each operation is run separate to reduce complexity and prevent hard to find bugs,
        // this results in less performance but better readability;
        // Results should be cached anyway.

        if ($valuesBag->has(Range::class)) {
            $this->removeOverlappingRanges(
                $valuesBag->get(Range::class),
                $valuesBag,
                $comparator,
                $options
            );

            $this->optimizeConnectedRanges(
                $valuesBag->get(Range::class),
                $valuesBag,
                $comparator,
                $options
            );

            $this->removeOverlappingSingleValues(
                $valuesBag->getSimpleValues(),
                $valuesBag->get(Range::class),
                $valuesBag,
                $comparator,
                $options
            );
        }

        if ($valuesBag->has(ExcludedRange::class)) {
            $this->removeOverlappingRanges(
                $valuesBag->get(ExcludedRange::class),
                $valuesBag,
                $comparator,
                $options
            );

            $this->optimizeConnectedRanges(
                $valuesBag->get(ExcludedRange::class),
                $valuesBag,
                $comparator,
                $options,
                true
            );

            $this->removeOverlappingSingleValues(
                $valuesBag->getExcludedSimpleValues(),
                $valuesBag->get(ExcludedRange::class),
                $valuesBag,
                $comparator,
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
     * @param array           $singleValues
     * @param Range[]         $ranges
     * @param ValuesBag       $valuesBag
     * @param ValueComparator $comparator
     * @param array           $options
     * @param bool            $exclude
     */
    private function removeOverlappingSingleValues(
        array $singleValues,
        array $ranges,
        ValuesBag $valuesBag,
        ValueComparator $comparator,
        array $options,
        bool $exclude = false
    ): void {
        foreach ($ranges as $i => $range) {
            foreach ($singleValues as $c => $value) {
                if ($this->isValInRange($value, $range, $comparator, $options)) {
                    if ($exclude) {
                        $valuesBag->removeExcludedSimpleValue($c);
                    } else {
                        $valuesBag->removeSimpleValue($c);
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
     * @param Range[]         $ranges
     * @param ValuesBag       $valuesBag
     * @param ValueComparator $comparator
     * @param array           $options
     */
    private function removeOverlappingRanges(
        array $ranges,
        ValuesBag $valuesBag,
        ValueComparator $comparator,
        array $options
    ): void {
        foreach ($ranges as $i => $range) {
            // If the range is already removed just ignore it.
            if (!isset($ranges[$i])) {
                continue;
            }

            foreach ($ranges as $c => $value) {
                if ($i === $c) {
                    continue;
                }

                if ($this->isRangeInRange($value, $range, $comparator, $options)) {
                    $valuesBag->remove(get_class($range), $c);

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
     * @param Range[]         $ranges
     * @param ValuesBag       $valuesBag
     * @param ValueComparator $comparator
     * @param array           $options
     * @param bool            $exclude
     */
    private function optimizeConnectedRanges(
        array $ranges,
        ValuesBag $valuesBag,
        ValueComparator $comparator,
        array $options,
        bool $exclude = false
    ): void {
        $class = $exclude ? ExcludedRange::class : Range::class;

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

                if ($comparator->isEqual($range->getUpper(), $value->getLower(), $options)) {
                    $newRange = new $class(
                        $range->getLower(),
                        $value->getUpper(),
                        $range->isLowerInclusive(),
                        $range->isUpperInclusive()
                    );

                    $valuesBag->remove($class, $i);
                    $valuesBag->remove($class, $c);
                    $valuesBag->add($newRange);

                    unset($ranges[$i], $ranges[$c]);
                }
            }
        }
    }

    private function isValInRange($value, Range $range, ValueComparator $comparator, array $options): bool
    {
        // Test it's not overlapping, when this fails then its save to assert there is an overlap.

        if (!$comparator->isHigher($value, $range->getLower(), $options) &&
            (!$range->isLowerInclusive() xor !$comparator->isEqual($value, $range->getLower(), $options))
        ) {
            return false;
        }

        return !(!$comparator->isLower($value, $range->getUpper(), $options) &&
            (!$range->isUpperInclusive() xor !$comparator->isEqual($value, $range->getUpper(), $options)));
    }

    private function isRangeInRange(Range $range1, Range $range, ValueComparator $comparator, array $options): bool
    {
        if (!$comparator->isHigher($range1->getLower(), $range->getLower(), $options) &&
            !$this->isBoundEqual(
                $comparator,
                $options,
                $range1->getLower(),
                $range->getLower(),
                $range->isLowerInclusive(),
                $range1->isLowerInclusive()
            )
        ) {
            return false;
        }

        return !(!$comparator->isLower($range1->getUpper(), $range->getUpper(), $options) &&
            !$this->isBoundEqual(
                $comparator,
                $options,
                $range1->getUpper(),
                $range->getUpper(),
                $range->isUpperInclusive(),
                $range1->isUpperInclusive()
            )
        );
    }

    private function isBoundEqual(
        ValueComparator $comparator,
        array $options,
        $value1,
        $value2,
        bool $value1Inclusive,
        bool $value2Inclusive
    ): bool {
        if (!$comparator->isEqual($value1, $value2, $options)) {
            return false;
        }

        if ($value1Inclusive === $value2Inclusive) {
            return true;
        }

        return true === $value1Inclusive;
    }
}
