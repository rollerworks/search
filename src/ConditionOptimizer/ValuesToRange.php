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

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionOptimizerInterface;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Rollerworks\Component\Search\ValueIncrementerInterface;

/**
 * Converts incremented values to inclusive ranges.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesToRange implements SearchConditionOptimizerInterface
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
            $comparison = $field->getValueComparison();

            if ($comparison instanceof ValueIncrementerInterface &&
                $field->supportValueType(ValuesBag::VALUE_TYPE_RANGE)
            ) {
                $this->comparators[$name] = new ValueSortCompare($comparison, $field->getOptions());

                $optimize = true;
            }
        }

        // None of the fields supports ranges or value-increments so don't optimize.
        if (!$optimize) {
            return;
        }

        $this->optimizeValuesInGroup($valuesGroup, $fieldSet);
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     */
    private function optimizeValuesInGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            if (!isset($this->comparators[$fieldName])) {
                continue;
            }

            $config = $fieldSet->get($fieldName);

            if ($values->hasSimpleValues() || $values->hasExcludedSimpleValues()) {
                $this->optimizeValuesInValuesBag($config, $this->comparators[$fieldName], $values);
            }
        }

        // Traverse the subgroups.
        foreach ($valuesGroup->getGroups() as $group) {
            $this->optimizeValuesInGroup($group, $fieldSet);
        }
    }

    /**
     * @param FieldConfigInterface $config
     * @param                      $comparisonFunc
     * @param ValuesBag            $valuesBag
     */
    private function optimizeValuesInValuesBag(FieldConfigInterface $config, $comparisonFunc, ValuesBag $valuesBag)
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

    /**
     * Converts a list of values to ranges.
     *
     * @param array                $values
     * @param ValuesBag            $valuesBag
     * @param FieldConfigInterface $config
     * @param bool                 $exclude
     */
    private function listToRanges($values, ValuesBag $valuesBag, FieldConfigInterface $config, $exclude = false)
    {
        $class = $exclude ? ExcludedRange::class : Range::class;
        /** @var ValueIncrementerInterface $comparison */
        $comparison = $config->getValueComparison();
        $options = $config->getOptions();

        $prevIndex = null;
        $prevValue = null;

        $rangeLower = null;
        $rangeUpper = null;

        $valuesCount = count($values);
        $curCount = 0;

        foreach ($values as $valIndex => $value) {
            ++$curCount;

            if (null === $prevValue) {
                $prevIndex = $valIndex;
                $prevValue = $value;

                continue;
            }

            $unsetIndex = null;
            $increasedValue = $comparison->getIncrementedValue($prevValue, $options);

            if ($comparison->isEqual($value, $increasedValue, $options)) {
                if (null === $rangeLower) {
                    $rangeLower = $prevValue;
                }

                $rangeUpper = $value;
            }

            if (null !== $rangeUpper) {
                $unsetIndex = $prevIndex;

                if ($curCount === $valuesCount || !$comparison->isEqual($value, $increasedValue, $options)) {
                    $range = new $class(
                        $rangeLower,
                        $rangeUpper,
                        true,
                        true
                    );

                    $valuesBag->add($range);

                    $unsetIndex = $prevIndex;

                    if ($curCount === $valuesCount) {
                        $unsetIndex = $valIndex;
                    }

                    $rangeLower = $rangeUpper = null;
                }

                $prevIndex = $valIndex;
                $prevValue = $value;
            }

            if (null !== $unsetIndex) {
                if ($exclude) {
                    $valuesBag->removeExcludedSimpleValue($unsetIndex);
                } else {
                    $valuesBag->removeSimpleValue($unsetIndex);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // run before range optimizer
        return 4;
    }
}
