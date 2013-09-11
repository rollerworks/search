<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Value\PatternMatch;
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
     * @param boolean     $useFieldAlias
     * @param boolean     $isRoot
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
                $exportedComparisons[] = array('operator' => $value->getOperator(), 'value' => $value->getViewValue());
            }

            if (count($exportedComparisons)) {
                $exportedValues['comparisons'] = $exportedComparisons;
            }
        }

        if ($valuesBag->hasPatternMatchers()) {
            $exportedPatternMatchers = array();
            foreach ($valuesBag->getPatternMatchers() as $value) {
                $exportedPatternMatchers[] = array('type' => $this->getPatternMatchType($value), 'value' => $value->getViewValue(), 'case-insensitive' => $value->isCaseInsenstive());
            }

            if (count($exportedPatternMatchers)) {
                $exportedValues['pattern-matchers'] = $exportedPatternMatchers;
            }
        }

        return $exportedValues;
    }

    /**
     * @param PatternMatch $patternMatch
     *
     * @return string
     *
     * @throws \RuntimeException When an unsupported pattern-match type is found.
     */
    protected function getPatternMatchType(PatternMatch $patternMatch)
    {
        $type = '';

        if (in_array($patternMatch->getType(), array(PatternMatch::PATTERN_NOT_CONTAINS, PatternMatch::PATTERN_NOT_STARTS_WITH, PatternMatch::PATTERN_NOT_ENDS_WITH, PatternMatch::PATTERN_NOT_REGEX))) {
           $type .= 'NOT_';
        }

        switch ($patternMatch->getType()) {
            case PatternMatch::PATTERN_CONTAINS:
            case PatternMatch::PATTERN_NOT_CONTAINS:
                $type .= 'CONTAINS';
                break;

            case PatternMatch::PATTERN_STARTS_WITH:
            case PatternMatch::PATTERN_NOT_STARTS_WITH:
                $type .= 'STARTS_WITH';
                break;

            case PatternMatch::PATTERN_ENDS_WITH:
            case PatternMatch::PATTERN_NOT_ENDS_WITH:
                $type .= 'ENDS_WITH';
                break;

            case PatternMatch::PATTERN_REGEX:
            case PatternMatch::PATTERN_NOT_REGEX:
                $type .= 'REGEX';
                break;

            default:
                throw new \RuntimeException(sprintf('Unsupported pattern-match type "%s" found. Please report this bug.', $patternMatch->getType()));
        }

        return $type;
    }

    /**
     * @param Range $range
     *
     * @return array
     */
    protected function exportRangeValue(Range $range)
    {
        $result = array('lower' => $range->getViewLower(), 'upper' => $range->getViewUpper());

        if (!$range->isLowerInclusive()) {
            $result['inclusive-lower'] = false;
        }

        if (!$range->isUpperInclusive()) {
            $result['inclusive-upper'] = false;
        }

        return $result;
    }
}
