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

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Exports the SearchCondition as StringQuery string.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class StringExporter extends AbstractExporter
{
    protected $fields = [];

    /**
     * Exports a search condition.
     *
     * @param SearchCondition $condition The search condition to export
     *
     * @return string
     */
    public function exportCondition(SearchCondition $condition): string
    {
        $this->fields = $this->resolveLabels($condition->getFieldSet());

        return $this->exportGroup($condition->getValuesGroup(), $condition->getFieldSet(), true);
    }

    abstract protected function resolveLabels(FieldSet $fieldSet): array;

    protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, bool $isRoot = false): string
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

            $result .= $this->getFieldLabel($name);
            $result .= ': '.$this->exportValues($values, $fieldSet->get($name)).'; ';
        }

        foreach ($valuesGroup->getGroups() as $group) {
            $exportedGroup = '( '.trim($this->exportGroup($group, $fieldSet), ' ;').' ); ';

            if ('(  ); ' !== $exportedGroup && ValuesGroup::GROUP_LOGICAL_OR === $group->getGroupLogical()) {
                $exportedGroups .= '*';
            }

            $exportedGroups .= $exportedGroup;
        }

        $result .= $exportedGroups;

        return trim($result);
    }

    protected function modelToExported($value, FieldConfig $field): string
    {
        return $this->modelToView($value, $field);
    }

    private function getFieldLabel(string $name): string
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        throw new UnknownFieldException($name);
    }

    private function exportValues(ValuesBag $valuesBag, FieldConfig $field): string
    {
        $exportedValues = '';

        foreach ($valuesBag->getSimpleValues() as $value) {
            $exportedValues .= $this->exportValuePart($this->modelToExported($value, $field)).', ';
        }

        foreach ($valuesBag->getExcludedSimpleValues() as $value) {
            $exportedValues .= '!'.$this->exportValuePart($this->modelToExported($value, $field)).', ';
        }

        foreach ($valuesBag->get(Range::class) as $value) {
            $exportedValues .= $this->exportRangeValue($value, $field).', ';
        }

        foreach ($valuesBag->get(ExcludedRange::class) as $value) {
            $exportedValues .= '!'.$this->exportRangeValue($value, $field).', ';
        }

        foreach ($valuesBag->get(Compare::class) as $value) {
            $exportedValues .= $value->getOperator().' '.$this->exportValuePart($this->modelToExported($value->getValue(), $field)).', ';
        }

        foreach ($valuesBag->get(PatternMatch::class) as $value) {
            $exportedValues .= $this->getPatternMatchOperator($value).' '.$this->exportValuePart($value->getValue()).', ';
        }

        return rtrim($exportedValues, ', ');
    }

    private function getPatternMatchOperator(PatternMatch $patternMatch): string
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

    private function exportRangeValue(Range $range, FieldConfig $field): string
    {
        $result = !$range->isLowerInclusive() ? ']' : '';
        $result .= $this->exportValuePart($this->modelToExported($range->getLower(), $field));
        $result .= ' ~ ';
        $result .= $this->exportValuePart($this->modelToExported($range->getUpper(), $field));
        $result .= !$range->isUpperInclusive() ? '[' : '';

        return $result;
    }

    private function exportValuePart($value): string
    {
        $value = (string) $value;

        if (preg_match('/[<>[\](),;~!*?=&*"\s]/u', $value)) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
