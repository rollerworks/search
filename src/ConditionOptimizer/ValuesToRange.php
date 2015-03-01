<?php

/*
 * This file is part of the RollerworksSearch Component package.
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
use Rollerworks\Component\Search\ValueIncrementerInterface;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Converts incremented values to inclusive ranges.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesToRange implements SearchConditionOptimizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(SearchConditionInterface $condition)
    {
        $fieldSet = $condition->getFieldSet();
        $valuesGroup = $condition->getValuesGroup();
        $optimize = false;

        foreach ($fieldSet->all() as $field) {
            if ($field->getValueComparison() instanceof ValueIncrementerInterface &&
                $field->supportValueType(ValuesBag::VALUE_TYPE_RANGE)
            ) {
                $optimize = true;

                break;
            }
        }

        // None of the fields supports ranges or value-increments so don't optimize
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
            $config = $fieldSet->get($fieldName);

            if ($values->hasSingleValues() || $values->hasExcludedValues()) {
                $this->optimizeValuesInValuesBag($config, $values);
            }
        }

        // now traverse the subgroups
        foreach ($valuesGroup->getGroups() as $group) {
            $this->optimizeValuesInGroup($group, $fieldSet);
        }
    }

    /**
     * @param FieldConfigInterface $config
     * @param ValuesBag            $valuesBag
     * @param ValuesBag            $valuesBag
     */
    private function optimizeValuesInValuesBag(FieldConfigInterface $config, ValuesBag $valuesBag)
    {
        $comparison = $config->getValueComparison();
        $options = $config->getOptions();

        $comparisonFunc = function (SingleValue $first, SingleValue $second) use ($comparison, $options) {
            $a = $first->getValue();
            $b = $second->getValue();

            if ($comparison->isEqual($a, $b, $options)) {
                return 0;
            }

            return $comparison->isLower($a, $b, $options) ? -1 : 1;
        };

        if ($valuesBag->hasSingleValues()) {
            $values = $valuesBag->getSingleValues();
            uasort($values, $comparisonFunc);

            $this->listToRanges($values, $valuesBag, $config);
        }

        if ($valuesBag->hasExcludedValues()) {
            $excludes = $valuesBag->getExcludedValues();
            uasort($excludes, $comparisonFunc);

            $this->listToRanges($excludes, $valuesBag, $config, true);
        }
    }

    /**
     * Converts a list of values to ranges.
     *
     * @param SingleValue[]        $values
     * @param ValuesBag            $valuesBag
     * @param FieldConfigInterface $config
     * @param bool                 $exclude
     */
    private function listToRanges($values, ValuesBag $valuesBag, FieldConfigInterface $config, $exclude = false)
    {
        /** @var ValueIncrementerInterface $comparison */
        $comparison = $config->getValueComparison();
        $options = $config->getOptions();

        $prevIndex = null;
        /** @var SingleValue $prevValue */
        $prevValue = null;

        $rangeLower = null;
        $rangeUpper = null;

        $valuesCount = count($values);
        $curCount = 0;

        foreach ($values as $valIndex => $value) {
            $curCount++;

            if (null === $prevValue) {
                $prevIndex = $valIndex;
                $prevValue = $value;

                continue;
            }

            $unsetIndex = null;
            $increasedValue = $comparison->getIncrementedValue($prevValue->getValue(), $options);

            if ($comparison->isEqual($value->getValue(), $increasedValue, $options)) {
                if (null === $rangeLower) {
                    $rangeLower = $prevValue;
                }

                $rangeUpper = $value;
            }

            if (null !== $rangeUpper) {
                $unsetIndex = $prevIndex;

                if ($curCount === $valuesCount || !$comparison->isEqual($value->getValue(), $increasedValue, $options)) {
                    $range = new Range(
                        $rangeLower->getValue(),
                        $rangeUpper->getValue(),
                        true,
                        true,
                        $rangeLower->getViewValue(),
                        $rangeUpper->getViewValue()
                    );

                    if ($exclude) {
                        $valuesBag->addExcludedRange($range);
                    } else {
                        $valuesBag->addRange($range);
                    }

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
                    $valuesBag->removeExcludedValue($unsetIndex);
                } else {
                    $valuesBag->removeSingleValue($unsetIndex);
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
