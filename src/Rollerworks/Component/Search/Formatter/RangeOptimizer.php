<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Formatter;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FormatterInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Removes overlapping ranges/values and merges connected ranges.
 *
 * This should be run after validation and transformation.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RangeOptimizer implements FormatterInterface
{
    /**
     * {@inheritDoc}
     */
    public function format(SearchConditionInterface $condition)
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

    private function normalizeRangesInValuesBag(FieldConfigInterface $config, ValuesBag $valuesBag)
    {
        $comparison = $config->getValueComparison();
        $options = $config->getOptions();

        if ($valuesBag->hasRanges()) {
            $singleValues = $valuesBag->getSingleValues();
            $ranges = $valuesBag->getRanges();

            foreach ($ranges as $i => $range) {
                if (!isset($ranges[$i])) {
                    continue;
                }

                foreach ($singleValues as $c => $value) {
                    if ($this->isValInRange($value, $range, $comparison, $options)) {
                        $valuesBag->removeSingleValue($c);
                    }
                }

                foreach ($ranges as $c => $value) {
                    if ($i === $c) {
                        continue;
                    }

                    if ($this->isRangeInRange($value, $range, $comparison, $options)) {
                        $valuesBag->removeRange($c);
                        unset($ranges[$c]);

                        continue;
                    }

                    // check if the range is connected
                    // connected is when the upper-bound is equal to the lower-bound of the second range
                    // only when the bounds inclusiveness are equal they can be optimized

                    if ($range->isLowerInclusive() === $value->isLowerInclusive() && $range->isUpperInclusive() === $value->isUpperInclusive() && $comparison->isEqual($range->getUpper(), $value->getLower(), $options)) {
                        $range->setUpper($value->getUpper());

                        // remove the second range as its merged now
                        $valuesBag->removeRange($c);
                        unset($ranges[$c]);
                    }
                }
            }

            unset($singleValues);
        }

        if ($valuesBag->hasExcludedRanges()) {
            $excludedValues = $valuesBag->getExcludedValues();
            $excludedRanges = $valuesBag->getExcludedRanges();

            foreach ($excludedRanges as $i => $range) {
                if (!isset($excludedRanges[$i])) {
                    continue;
                }

                foreach ($excludedValues as $c => $value) {
                    if ($this->isValInRange($value, $range, $comparison, $options)) {
                        $valuesBag->removeExcludedValue($c);
                    }
                }

                foreach ($excludedRanges as $c => $value) {
                    if ($i === $c) {
                        continue;
                    }

                    if ($this->isRangeInRange($value, $range, $comparison, $options)) {
                        $valuesBag->removeExcludedRange($c);
                        unset($excludedRanges[$c]);

                        continue;
                    }

                    // check if the range is connected
                    // connected is when the upper-bound is equal to the lower-bound of the second range
                    // only when the bounds inclusiveness are equal they can be optimized

                    if ($range->isLowerInclusive() === $value->isLowerInclusive() && $range->isUpperInclusive() === $value->isUpperInclusive() && $comparison->isEqual($range->getUpper(), $value->getLower(), $options)) {
                        $range->setUpper($value->getUpper());

                        // remove the second range as its merged now
                        $valuesBag->removeExcludedRange($c);
                        unset($excludedRanges[$c]);
                    }
                }
            }

            unset($singleValues);
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
     * @return boolean
     */
    private function isValInRange(SingleValue $singeValue, Range $range, ValueComparisonInterface $comparison, $options)
    {
        $overlap = false;
        $isLower = false;

        // This has been made very verbose to not make a complete spaghetti mess of it

        if ($range->isLowerInclusive() && ($comparison->isEqual($singeValue->getValue(), $range->getLower(), $options) || $comparison->isHigher($singeValue->getValue(), $range->getLower(), $options))) {
            $isLower = true;
        } elseif (!$range->isLowerInclusive() && $comparison->isHigher($singeValue->getValue(), $range->getLower(), $options)) {
            $isLower = true;
        }

        // value is higher (or equal) then lower bound, so now check the lower bound
        if ($isLower) {
            if ($range->isUpperInclusive() && ($comparison->isEqual($singeValue->getValue(), $range->getUpper(), $options) || $comparison->isLower($singeValue->getValue(), $range->getUpper(), $options))) {
                $overlap = true;
            } elseif (!$range->isUpperInclusive() && $comparison->isLower($singeValue->getValue(), $range->getUpper(), $options)) {
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
     * @return boolean
     */
    private function isRangeInRange(Range $range1, Range $range, ValueComparisonInterface $comparison, $options)
    {
        $overlap = false;
        $isLower = false;

        // This has been made very verbose to not make a complete spaghetti mess of it
        // Ranges are more difficult as each can be inclusive and exclusive

        if ($range->isLowerInclusive()) {
            if ($range1->isLowerInclusive() && ($comparison->isEqual($range1->getLower(), $range->getLower(), $options) || $comparison->isHigher($range1->getLower(), $range->getLower(), $options))) {
                $isLower = true;
            } elseif (!$range1->isLowerInclusive() && $comparison->isHigher($range1->getLower(), $range->getLower(), $options)) {
                $isLower = true;
            }
        } elseif ($comparison->isHigher($range1->getLower(), $range->getLower(), $options)) {
            // If the first range is exclusive it makes no sense to equal-check the second
            $isLower = true;
        }

        // value is higher (or equal) then lower bound, so now check the lower bound
        if ($isLower) {
            if ($range->isUpperInclusive()) {
                if ($range1->isUpperInclusive() && ($comparison->isEqual($range1->getUpper(), $range->getUpper(), $options) || $comparison->isLower($range1->getUpper(), $range->getUpper(), $options))) {
                    $overlap = true;
                } elseif (!$range1->isUpperInclusive() && $comparison->isLower($range->getUpper(), $range1->getUpper(), $options)) {
                    // Because the second upper-bound is exclusive we check the
                    $overlap = true;
                }
            } elseif ($comparison->isLower($range1->getUpper(), $range->getUpper(), $options)) {
                $overlap = true;
            }
        }

        return $overlap;
    }
}
