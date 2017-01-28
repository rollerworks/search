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
use Rollerworks\Component\Search\ValueIncrementer;

/**
 * Converts incremented values to inclusive ranges.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesToRange implements SearchConditionOptimizer
{
    private $comparators = [];

    /**
     * {@inheritdoc}
     */
    public function process(SearchCondition $condition)
    {
        $fieldSet = $condition->getFieldSet();
        $valuesGroup = $condition->getValuesGroup();
        $optimize = false;

        // Check if the optimization should be performed.
        // And builds the comparators.

        foreach ($fieldSet->all() as $name => $field) {
            if ($field->supportValueType(Range::class)) {
                $this->comparators[$name] = new ValueSortCompare($field->getValueComparison(), $field->getOptions());

                $optimize = true;
                break;
            }
        }

        // None of the fields supports ranges or value-increments so don't optimize.
        if (!$optimize) {
            return;
        }

        $this->optimizeValuesInGroup($valuesGroup, $fieldSet);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        // run before range optimizer
        return 4;
    }

    private function optimizeValuesInGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            if (!isset($this->comparators[$fieldName])) {
                continue;
            }

            $this->optimizeValuesInValuesBag($fieldSet->get($fieldName), $this->comparators[$fieldName], $values);
        }

        foreach ($valuesGroup->getGroups() as $group) {
            $this->optimizeValuesInGroup($group, $fieldSet);
        }
    }

    private function optimizeValuesInValuesBag(FieldConfig $config, ValueSortCompare $comparisonFunc, ValuesBag $valuesBag)
    {
        if ($valuesBag->hasSimpleValues()) {
            $values = $valuesBag->getSimpleValues();
            uasort($values, $comparisonFunc);

            $this->listToRanges($values, $valuesBag, $config);
        }

        if ($valuesBag->hasExcludedSimpleValues()) {
            $excludes = $valuesBag->getExcludedSimpleValues();
            uasort($excludes, $comparisonFunc);

            $this->listToRanges($excludes, $valuesBag, $config, true);
        }
    }

    private function listToRanges(array $values, ValuesBag $valuesBag, FieldConfig $config, bool $exclude = false)
    {
        $class = $exclude ? ExcludedRange::class : Range::class;
        /** @var ValueIncrementer $comparison */
        $comparison = $config->getValueComparison();
        $options = $config->getOptions();

        $prevIndex = null;
        $prevValue = null;

        $rangeLower = null;
        $rangeUpper = null;
        $valuesBetween = 0;

        $valuesCount = count($values);
        $curCount = 0;

        $allRemoveIndexes = [];
        $removeIndexes = [];

        foreach ($values as $valIndex => $value) {
            ++$curCount;

            // No previous value exists, this is the initial phase of a search.
            if (null === $prevValue) {
                $prevIndex = $valIndex;
                $prevValue = $value;

                continue;
            }

            $increasedValue = $comparison->getIncrementedValue($prevValue, $options);

            if ($comparison->isEqual($value, $increasedValue, $options)) {
                if (null === $rangeLower) {
                    $rangeLower = $prevValue;
                }

                $removeIndexes[] = $prevIndex;

                $rangeUpper = $value;
                $prevValue = $value;
                $prevIndex = $valIndex;
                ++$valuesBetween;

                // If this is not the last simple continue (looking for increments).
                // If it is the last, the logic below will finish the range (instead
                // of requiring a check after the loop).
                if ($curCount < $valuesCount) {
                    continue;
                }
            }

            // Value is no (longer) an increment or is the last one.

            // if there are values, use the last matching value as upper bound.
            // If not, ignore indexes that were previously marked for removal.
            if ($valuesBetween > 1) {
                $valuesBag->add(new $class($rangeLower, $rangeUpper, true, true));

                $removeIndexes[] = $prevIndex;
                $allRemoveIndexes = array_merge($allRemoveIndexes, $removeIndexes);
            }

            // Reset for a another search.
            $valuesBetween = 0;
            $rangeLower = null;
            $rangeUpper = null;
            $removeIndexes = [];

            $prevIndex = $valIndex;
            $prevValue = $value;
        }

        foreach ($allRemoveIndexes as $index) {
            if ($exclude) {
                $valuesBag->removeExcludedSimpleValue($index);
            } else {
                $valuesBag->removeSimpleValue($index);
            }
        }
    }
}
