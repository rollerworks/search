<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Exports the SearchCondition as a structured PHP Array.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ArrayExporter extends AbstractExporter
{
    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     * @param bool        $useFieldAlias
     * @param bool        $isRoot
     *
     * @return array
     */
    protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $useFieldAlias = false, $isRoot = false)
    {
        $result = array();
        $fields = $valuesGroup->getFields();

        if (!empty($fields)) {
            $result['fields'] = array();

            foreach ($fields as $name => $values) {
                $exportedValue = $this->exportValues($values, $fieldSet->get($name));

                // Only export fields with actual values
                if (!empty($exportedValue)) {
                    $fieldLabel = ($useFieldAlias ? $this->labelResolver->resolveFieldLabel($fieldSet, $name) : $name);
                    $result['fields'][$fieldLabel] = $exportedValue;
                }
            }
        }

        if ($valuesGroup->hasGroups()) {
            $result['groups'] = array();

            foreach ($valuesGroup->getGroups() as $group) {
                $result['groups'][] = $this->exportGroup($group, $fieldSet, $useFieldAlias, false);
            }
        }

        if (ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical() &&
            (isset($result['groups']) || isset($result['fields']))
        ) {
            $result['logical-case'] = 'OR';
        }

        return $result;
    }

    /**
     * @param ValuesBag $valuesBag
     *
     * @return string
     */
    protected function exportValues(ValuesBag $valuesBag)
    {
        $exportedValues = array();

        if ($valuesBag->hasSingleValues()) {
            $singleValues = array();

            foreach ($valuesBag->getSingleValues() as $value) {
                $singleValues[] = $value->getViewValue();
            }

            if (count($singleValues)) {
                $exportedValues['single-values'] = $singleValues;
            }
        }

        if ($valuesBag->hasExcludedValues()) {
            $excludedValues = array();

            foreach ($valuesBag->getExcludedValues() as $value) {
                $excludedValues[] = $value->getViewValue();
            }

            if (count($excludedValues)) {
                $exportedValues['excluded-values'] = $excludedValues;
            }
        }

        if ($valuesBag->hasRanges()) {
            $exportedRanges = array();

            foreach ($valuesBag->getRanges() as $value) {
                $exportedRanges[] = $this->exportRangeValue($value);
            }

            if (count($exportedRanges)) {
                $exportedValues['ranges'] = $exportedRanges;
            }
        }

        if ($valuesBag->hasExcludedRanges()) {
            $exportedExcludedRanges = array();

            foreach ($valuesBag->getExcludedRanges() as $value) {
                $exportedExcludedRanges[] = $this->exportRangeValue($value);
            }

            if (count($exportedExcludedRanges)) {
                $exportedValues['excluded-ranges'] = $exportedExcludedRanges;
            }
        }

        if ($valuesBag->hasComparisons()) {
            $exportedComparisons = array();

            foreach ($valuesBag->getComparisons() as $value) {
                $exportedComparisons[] = array(
                    'operator' => $value->getOperator(),
                    'value' => $value->getViewValue(),
                );
            }

            if (count($exportedComparisons)) {
                $exportedValues['comparisons'] = $exportedComparisons;
            }
        }

        if ($valuesBag->hasPatternMatchers()) {
            $exportedPatternMatchers = array();

            foreach ($valuesBag->getPatternMatchers() as $value) {
                $matcher = array(
                    'type' => $this->getPatternMatchType($value),
                    'value' => $value->getValue(),
                );

                if ($value->isCaseInsensitive()) {
                    $matcher['case-insensitive'] = $value->isCaseInsensitive();
                }

                $exportedPatternMatchers[] = $matcher;
            }

            if (count($exportedPatternMatchers)) {
                $exportedValues['pattern-matchers'] = $exportedPatternMatchers;
            }
        }

        return $exportedValues;
    }

    /**
     * @param Range $range
     *
     * @return array
     */
    protected function exportRangeValue(Range $range)
    {
        $result = array(
            'lower' => $range->getViewLower(),
            'upper' => $range->getViewUpper(),
        );

        if (!$range->isLowerInclusive()) {
            $result['inclusive-lower'] = false;
        }

        if (!$range->isUpperInclusive()) {
            $result['inclusive-upper'] = false;
        }

        return $result;
    }
}
