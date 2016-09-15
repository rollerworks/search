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
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Exports the SearchCondition as FilterQuery string.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterQueryExporter extends AbstractExporter
{
    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     * @param bool        $isRoot
     *
     * @return string
     */
    protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $isRoot = false)
    {
        $result = '';
        $exportedGroups = '';

        if ($isRoot &&
            $valuesGroup->countValues() > 0 &&
            ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical()
        ) {
            $result .= '*';
        }

        foreach ($valuesGroup->getFields() as $name => $values) {
            if (0 === $values->count()) {
                continue;
            }

            $result .= $this->labelResolver->resolveFieldLabel($fieldSet, $name);
            $result .= ': '.$this->exportValues($values).'; ';
        }

        foreach ($valuesGroup->getGroups() as $group) {
            $exportedGroup = '('.trim($this->exportGroup($group, $fieldSet), ' ;').'); ';

            if ('(); ' !== $exportedGroup && ValuesGroup::GROUP_LOGICAL_OR === $group->getGroupLogical()) {
                $exportedGroups .= '*';
            }

            $exportedGroups .= $exportedGroup;
        }

        $result .= $exportedGroups;

        return trim($result);
    }

    /**
     * @param ValuesBag $valuesBag
     *
     * @return string
     */
    private function exportValues(ValuesBag $valuesBag)
    {
        $exportedValues = '';

        foreach ($valuesBag->getSingleValues() as $value) {
            $exportedValues .= $this->exportValuePart($value->getViewValue()).', ';
        }

        foreach ($valuesBag->getExcludedValues() as $value) {
            $exportedValues .= '!'.$this->exportValuePart($value->getViewValue()).', ';
        }

        foreach ($valuesBag->getRanges() as $value) {
            $exportedValues .= $this->exportRangeValue($value).', ';
        }

        foreach ($valuesBag->getExcludedRanges() as $value) {
            $exportedValues .= '!'.$this->exportRangeValue($value).', ';
        }

        foreach ($valuesBag->getComparisons() as $value) {
            $exportedValues .= $value->getOperator().$this->exportValuePart($value->getViewValue()).', ';
        }

        foreach ($valuesBag->getPatternMatchers() as $value) {
            $exportedValues .= $this->getPatternMatchOperator($value).$this->exportValuePart($value->getValue()).', ';
        }

        return rtrim($exportedValues, ', ');
    }

    /**
     * @param PatternMatch $patternMatch
     *
     * @throws \RuntimeException When an unsupported pattern-match type is found
     *
     * @return string
     */
    private function getPatternMatchOperator(PatternMatch $patternMatch)
    {
        $operator = $patternMatch->isCaseInsensitive() ? '~i' : '~';

        if ($patternMatch->isExclusive()) {
            $operator .= '!';
        }

        switch ($patternMatch->getType()) {
            case PatternMatch::PATTERN_CONTAINS:
            case PatternMatch::PATTERN_NOT_CONTAINS:
                $operator .= '*';
                break;

            case PatternMatch::PATTERN_STARTS_WITH:
            case PatternMatch::PATTERN_NOT_STARTS_WITH:
                $operator .= '>';
                break;

            case PatternMatch::PATTERN_ENDS_WITH:
            case PatternMatch::PATTERN_NOT_ENDS_WITH:
                $operator .= '<';
                break;

            case PatternMatch::PATTERN_REGEX:
            case PatternMatch::PATTERN_NOT_REGEX:
                $operator .= '?';
                break;

            case PatternMatch::PATTERN_EQUALS:
            case PatternMatch::PATTERN_NOT_EQUALS:
                $operator .= '=';
                break;

            default:
                throw new \RuntimeException(
                    sprintf(
                        'Unsupported pattern-match type "%s" found. Please report this bug.',
                        $patternMatch->getType()
                    )
                );
        }

        return $operator;
    }

    /**
     * @param Range $range
     *
     * @return string
     */
    private function exportRangeValue(Range $range)
    {
        $result = !$range->isLowerInclusive() ? ']' : '';
        $result .= $this->exportValuePart($range->getViewLower());
        $result .= '-';
        $result .= $this->exportValuePart($range->getViewUpper());
        $result .= !$range->isUpperInclusive() ? '[' : '';

        return $result;
    }

    /**
     * Exports the value-part.
     *
     * If the value needs escaping/quotation this is performed.
     *
     * @param string $value
     *
     * @throws \InvalidArgumentException When the passed value is null or none scalar
     *
     * @return string
     */
    private function exportValuePart($value)
    {
        if ('' === $value) {
            throw new \InvalidArgumentException(
                'Unable to export empty view-value. Please make sure there is a view-value set.'
            );
        }

        if (!preg_match('/^([\p{L}\p{N}]+)$/siu', $value)) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
