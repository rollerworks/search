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
 * Exports the SearchCondition as FilterQuery string.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterQueryExporter extends AbstractExporter
{
    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     * @param boolean     $useFieldAlias
     * @param boolean     $isRoot
     *
     * @return string
     */
    protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $useFieldAlias = false, $isRoot = false)
    {
        $result = '';
        $exportedFields = '';
        $exportedGroups = '';

        foreach ($valuesGroup->getFields() as $name => $values) {
            $exportedValue = $this->exportValues($values, $fieldSet->get($name));

            // Only export fields with actual values
            if (!empty($exportedValue)) {
                $exportedFields .= ($useFieldAlias ? $this->labelResolver->resolveFieldLabel($fieldSet, $name) : $name);
                $exportedFields .= ': ' . $exportedValue . '; ';
            }
        }

        foreach ($valuesGroup->getGroups() as $group) {
            $exportedGroups .= $this->exportGroup($group, $fieldSet, $useFieldAlias, false);
        }

        if (!empty($exportedFields) || !empty($exportedGroups)) {
            // When the head group is OR-cased force to wrap it inside a group
            if (ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical()) {
                $isRoot = false;
                $result = '*';
            }

            $result .= (!$isRoot ? '(' : '') . $exportedFields . $exportedGroups . (!$isRoot ? ');' : '');
        }

        return trim($result);
    }

    /**
     * @param ValuesBag $valuesBag
     *
     * @return string
     */
    protected function exportValues(ValuesBag $valuesBag)
    {
        $exportedValues = '';

        foreach ($valuesBag->getSingleValues() as $value) {
            $exportedValues .= $this->exportValuePart($value->getViewValue()) . ', ';
        }

        foreach ($valuesBag->getExcludedValues() as $value) {
            $exportedValues .= '!' . $this->exportValuePart($value->getViewValue()) . ', ';
        }

        foreach ($valuesBag->getRanges() as $value) {
            $exportedValues .= $this->exportRangeValue($value) . ', ';
        }

        foreach ($valuesBag->getExcludedRanges() as $value) {
            $exportedValues .= '!' . $this->exportRangeValue($value) . ', ';
        }

        foreach ($valuesBag->getComparisons() as $value) {
            $exportedValues .= $value->getOperator() . $this->exportValuePart($value->getViewValue()) . ', ';
        }

        foreach ($valuesBag->getPatternMatchers() as $value) {
            $exportedValues .= $this->getPatternMatchOperator($value) . $this->exportValuePart($value->getViewValue()) . ', ';
        }

        return rtrim($exportedValues, ', ');
    }

    /**
     * @param PatternMatch $patternMatch
     *
     * @return string
     *
     * @throws \RuntimeException When an unsupported pattern-match type is found.
     */
    protected function getPatternMatchOperator(PatternMatch $patternMatch)
    {
        $operator = $patternMatch->isCaseInsensitive() ? '~i' : '~';

        if (in_array($patternMatch->getType(), array(PatternMatch::PATTERN_NOT_CONTAINS, PatternMatch::PATTERN_NOT_STARTS_WITH, PatternMatch::PATTERN_NOT_ENDS_WITH, PatternMatch::PATTERN_NOT_REGEX))) {
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

            default:
                throw new \RuntimeException(sprintf('Unsupported pattern-match type "%s" found. Please report this bug.', $patternMatch->getType()));
        }

        return $operator;
    }

    /**
     * @param Range $range
     *
     * @return string
     */
    protected function exportRangeValue(Range $range)
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
     * @return string
     *
     * @throws \InvalidArgumentException When the passed value is null or none scalar.
     */
    protected function exportValuePart($value)
    {
        if (null === $value) {
            throw new \InvalidArgumentException('Unable to export empty view-value. Please make sure there is a view-value set.');
        }

        if (!is_scalar($value)) {
            throw new \InvalidArgumentException('Unable to export none-scalar view-value. Please use a formatter to transform the value before exporting.');
        }

        if (!is_numeric($value) && !preg_match('/^(?:(?:[\p{L}+\p{N}]+)|(?:\p{N}+(?:[.]\p{N}+)*))$/siu', $value)) {
            return '"' . str_replace('"', '""', $value) . '"' ;
        }

        return $value;
    }
}
