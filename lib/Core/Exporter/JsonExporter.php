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
 * Exports the SearchCondition as a JSON object.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class JsonExporter extends AbstractExporter
{
    public function exportCondition(SearchCondition $condition): string
    {
        $fieldSet = $condition->getFieldSet();

        return (string) json_encode(
            array_merge(
                $this->exportOrder($condition, $fieldSet),
                $this->exportGroup($condition->getValuesGroup(), $fieldSet, true)
            ),
            \JSON_THROW_ON_ERROR
        );
    }

    protected function exportOrder(SearchCondition $condition, FieldSet $fieldSet): array
    {
        $order = $condition->getOrder();

        if ($order === null) {
            return [];
        }

        $result = [];

        foreach ($order->getFields() as $name => $direction) {
            $result[mb_substr($name, 1)] = $this->modelToNorm($direction, $fieldSet->get($name));
        }

        return $result ? ['order' => $result] : [];
    }

    protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, bool $isRoot = false): array
    {
        $result = [];
        $fields = $valuesGroup->getFields();

        foreach ($fields as $name => $values) {
            if ($fieldSet->isPrivate($name) || $values->count() === 0) {
                continue;
            }

            $exportedValue = $this->exportValues($values, $fieldSet->get($name));

            // Only export fields with actual values.
            if (\count($exportedValue) > 0) {
                $result['fields'][$name] = $exportedValue;
            }
        }

        foreach ($valuesGroup->getGroups() as $group) {
            $result['groups'][] = $this->exportGroup($group, $fieldSet, false);
        }

        if (isset($result['fields']) && $valuesGroup->getGroupLogical() === ValuesGroup::GROUP_LOGICAL_OR) {
            $result['logical-case'] = 'OR';
        }

        return $result;
    }

    private function exportValues(ValuesBag $valuesBag, FieldConfig $field): array
    {
        $exportedValues = [];

        foreach ($valuesBag->getSimpleValues() as $value) {
            $exportedValues['simple-values'][] = $this->modelToNorm($value, $field);
        }

        foreach ($valuesBag->getExcludedSimpleValues() as $value) {
            $exportedValues['excluded-simple-values'][] = $this->modelToNorm($value, $field);
        }

        foreach ($valuesBag->get(Range::class) as $value) {
            /** @var Range $value */
            $exportedValues['ranges'][] = $this->exportRangeValue($value, $field);
        }

        foreach ($valuesBag->get(ExcludedRange::class) as $value) {
            /** @var ExcludedRange $value */
            $exportedValues['excluded-ranges'][] = $this->exportRangeValue($value, $field);
        }

        foreach ($valuesBag->get(Compare::class) as $value) {
            /** @var Compare $value */
            $exportedValues['comparisons'][] = [
                'operator' => $value->getOperator(),
                'value' => $this->modelToNorm($value->getValue(), $field),
            ];
        }

        foreach ($valuesBag->get(PatternMatch::class) as $value) {
            /** @var PatternMatch $value */
            $exportedValues['pattern-matchers'][] = [
                'type' => $value->getType(),
                'value' => $value->getValue(),
                'case-insensitive' => $value->isCaseInsensitive(),
            ];
        }

        return $exportedValues;
    }

    private function exportRangeValue(Range $range, FieldConfig $field): array
    {
        $result = [
            'lower' => $this->modelToNorm($range->getLower(), $field),
            'upper' => $this->modelToNorm($range->getUpper(), $field),
        ];

        if (! $range->isLowerInclusive()) {
            $result['inclusive-lower'] = false;
        }

        if (! $range->isUpperInclusive()) {
            $result['inclusive-upper'] = false;
        }

        return $result;
    }
}
