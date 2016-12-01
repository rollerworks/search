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

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
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
            $result .= ': '.$this->exportValues($values, $fieldSet->get($name)).'; ';
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
    private function exportValues(ValuesBag $valuesBag, FieldConfigInterface $field)
    {
        $exportedValues = '';

        foreach ($valuesBag->getSimpleValues() as $value) {
            $exportedValues .= $this->exportValuePart($this->normToView($value, $field)).', ';
        }

        foreach ($valuesBag->getExcludedSimpleValues() as $value) {
            $exportedValues .= '!'.$this->exportValuePart($this->normToView($value, $field)).', ';
        }

        foreach ($valuesBag->get(Range::class) as $value) {
            $exportedValues .= $this->exportRangeValue($value, $field).', ';
        }

        foreach ($valuesBag->get(ExcludedRange::class) as $value) {
            $exportedValues .= '!'.$this->exportRangeValue($value, $field).', ';
        }

        foreach ($valuesBag->get(Compare::class) as $value) {
            $exportedValues .= $value->getOperator().$this->exportValuePart($this->normToView($value->getValue(), $field)).', ';
        }

        foreach ($valuesBag->get(PatternMatch::class) as $value) {
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
     * @param Range                $range
     * @param FieldConfigInterface $field
     *
     * @return string
     */
    private function exportRangeValue(Range $range, FieldConfigInterface $field)
    {
        $result = !$range->isLowerInclusive() ? ']' : '';
        $result .= $this->exportValuePart($this->normToView($range->getLower(), $field));
        $result .= '-';
        $result .= $this->exportValuePart($this->normToView($range->getUpper(), $field));
        $result .= !$range->isUpperInclusive() ? '[' : '';

        return $result;
    }

    /**
     * Exports the value-part.
     *
     * If the value needs escaping/quotation this is performed.
     *
     * @param mixed $value
     *
     * @return string When the passed value is null or none scalar
     */
    private function exportValuePart($value)
    {
        if (!preg_match('/^([\p{L}\p{N}]+)$/siu', $value)) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
