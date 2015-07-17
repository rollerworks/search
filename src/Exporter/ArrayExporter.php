<?php

/*
 * This file is part of the RollerworksSearch package.
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
     * @param bool        $isRoot
     *
     * @return array
     */
    protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $isRoot = false)
    {
        $result = [];
        $fields = $valuesGroup->getFields();

        foreach ($fields as $name => $values) {
            if (0 === $values->count()) {
                continue;
            }

            $exportedValue = $this->exportValues($values);

            // Only export fields with actual values.
            if (count($exportedValue) > 0) {
                $fieldLabel = $this->labelResolver->resolveFieldLabel($fieldSet, $name);
                $result['fields'][$fieldLabel] = $exportedValue;
            }
        }

        foreach ($valuesGroup->getGroups() as $group) {
            $result['groups'][] = $this->exportGroup($group, $fieldSet, false);
        }

        if (isset($result['fields']) && ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical()) {
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
        $exportedValues = [];

        foreach ($valuesBag->getSingleValues() as $value) {
            $exportedValues['single-values'][] = $value->getViewValue();
        }

        foreach ($valuesBag->getExcludedValues() as $value) {
            $exportedValues['excluded-values'][] = $value->getViewValue();
        }

        foreach ($valuesBag->getRanges() as $value) {
            $exportedValues['ranges'][] = $this->exportRangeValue($value);
        }

        foreach ($valuesBag->getExcludedRanges() as $value) {
            $exportedValues['excluded-ranges'][] = $this->exportRangeValue($value);
        }

        foreach ($valuesBag->getComparisons() as $value) {
            $exportedValues['comparisons'][] = [
                'operator' => $value->getOperator(),
                'value' => $value->getViewValue(),
            ];
        }

        foreach ($valuesBag->getPatternMatchers() as $value) {
            $exportedValues['pattern-matchers'][] = [
                'type' => $this->getPatternMatchType($value),
                'value' => $value->getValue(),
                'case-insensitive' => $value->isCaseInsensitive(),
            ];
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
        $result = [
            'lower' => $range->getViewLower(),
            'upper' => $range->getViewUpper(),
        ];

        if (!$range->isLowerInclusive()) {
            $result['inclusive-lower'] = false;
        }

        if (!$range->isUpperInclusive()) {
            $result['inclusive-upper'] = false;
        }

        return $result;
    }
}
