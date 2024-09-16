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

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\OrderField;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchOrder;
use Rollerworks\Component\Search\StructureBuilder;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * JsonInput processes input provided as an JSON object.
 *
 * The provided input must be structured as follow;
 *
 * Each entry must contain an array with 'fields' and/or 'groups' structures.
 * Optionally the array can contain 'logical-case' => 'OR' to make it OR-cased.
 *
 * The 'order' setting can only be applied at root level, and must NOT begin with the @-sign.
 *
 * The 'groups' array contains groups with the keys as described above ('fields' and/or 'groups').
 *
 * The fields array is an hash-map where each key is the field-name
 * and the value as follow; All the types are optional, but at least one must exists.
 *
 * ```
 * {
 *     "field-name": {
 *         "simple-values": ["value1", "value2"],
 *         "simple-excluded-values": ["my value1", "my value2"],
 *         "ranges": [{
 *             "lower": 10,
 *             "upper": 20
 *         }],
 *         "excluded-ranges": [{
 *             "lower": 25,
 *             "upper": 30,
 *             "inclusive-lower": true,
 *             "inclusive-upper": true
 *         }],
 *         "comparisons": [{
 *             "value": 50,
 *             "operator": ">"
 *         }],
 *         "pattern-matchers": [{
 *             "value": "foo",
 *             "type": "STARTS_WITH",
 *             "case-insensitive": false
 *         }]
 *     }
 * }
 * ```
 *
 * Note: 'inclusive-lower', 'inclusive-upper' and 'case-insensitive' are optional.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class JsonInput extends AbstractInput
{
    /**
     * @var StructureBuilder|null
     */
    private $structureBuilder;

    /**
     * @var StructureBuilder|null
     */
    private $orderStructureBuilder;

    public function process(ProcessorConfig $config, $input): SearchCondition
    {
        if (! \is_string($input)) {
            throw new UnexpectedTypeException($input, 'string');
        }

        $input = trim($input);

        $fieldSet = $config->getFieldSet();

        if (empty($input)) {
            return new SearchCondition($fieldSet, new ValuesGroup());
        }

        $array = json_decode($input, true, 512, \JSON_BIGINT_AS_STRING);

        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new InvalidSearchConditionException([
                ConditionErrorMessage::rawMessage(
                    $input,
                    'Input does not contain valid JSON: ' . "\n" . json_last_error_msg(),
                    $input
                ),
            ]);
        }

        $condition = null;
        $this->errors = new ErrorList();
        $this->config = $config;
        $this->level = 0;

        $this->structureBuilder = new ConditionStructureBuilder($this->config, $this->validator, $this->errors);
        $this->orderStructureBuilder = new OrderStructureBuilder($this->config, $this->validator, $this->errors);

        try {
            $valuesGroup = $this->structureBuilder->getRootGroup();
            $valuesGroup->setGroupLogical($array['logical-case'] ?? ValuesGroup::GROUP_LOGICAL_AND);

            $this->processGroup($array);

            $condition = new SearchCondition($fieldSet, $valuesGroup);

            $this->processOrder($condition, $array, $fieldSet);

            $this->assertLevel0();
        } catch (InputProcessorException $e) {
            $this->errors[] = $e->toErrorMessageObj();
        } finally {
            $this->structureBuilder = null;
        }

        if (\count($this->errors)) {
            $errors = $this->errors->getArrayCopy();

            throw new InvalidSearchConditionException($errors);
        }

        return $condition;
    }

    private function processGroup(array $group): void
    {
        $this->processFields($group['fields'] ?? []);

        foreach ($group['groups'] ?? [] as $index => $sub) {
            $this->structureBuilder->enterGroup($sub['logical-case'] ?? ValuesGroup::GROUP_LOGICAL_AND, '[groups][%d]');
            $this->processGroup($sub);
            $this->structureBuilder->leaveGroup();
        }
    }

    private function processFields(array $values): void
    {
        foreach ($values as $name => $value) {
            if ($this->config->getFieldSet()->isPrivate($name)) {
                throw new UnknownFieldException($name);
            }

            $this->structureBuilder->field($name, '[fields][%s]');

            foreach ($value['simple-values'] ?? [] as $index => $val) {
                $this->structureBuilder->simpleValue($val, '[simple-values][{idx}]');
            }

            foreach ($value['excluded-simple-values'] ?? [] as $index => $val) {
                $this->structureBuilder->excludedSimpleValue($val, '[excluded-simple-values][{idx}]');
            }

            foreach ($value['ranges'] ?? [] as $index => $range) {
                $this->assertValueArrayHasKeys($range, ['lower', 'upper'], "[ranges][{$index}]");

                $this->structureBuilder->rangeValue(
                    $range['lower'],
                    $range['upper'],
                    $range['inclusive-lower'] ?? true,
                    $range['inclusive-upper'] ?? true,
                    ['[ranges][{idx}]', '[lower]', '[upper]']
                );
            }

            foreach ($value['excluded-ranges'] ?? [] as $index => $range) {
                $this->assertValueArrayHasKeys($range, ['lower', 'upper'], "[excluded-ranges][{$index}]");

                $this->structureBuilder->excludedRangeValue(
                    $range['lower'],
                    $range['upper'],
                    $range['inclusive-lower'] ?? true,
                    $range['inclusive-upper'] ?? true,
                    ['[excluded-ranges][{idx}]', '[lower]', '[upper]']
                );
            }

            foreach ($value['comparisons'] ?? [] as $index => $comparison) {
                $this->assertValueArrayHasKeys($comparison, ['value', 'operator'], "[comparisons][{$index}]");
                $this->structureBuilder->comparisonValue(
                    $comparison['operator'],
                    $comparison['value'],
                    ["[comparisons][{$index}]", '[operator]', '[value]']
                );
            }

            foreach ($value['pattern-matchers'] ?? [] as $index => $matcher) {
                $this->assertValueArrayHasKeys($matcher, ['value', 'type'], "[pattern-matchers][{$index}]");
                $this->structureBuilder->patterMatchValue(
                    $matcher['type'],
                    $matcher['value'],
                    $matcher['case-insensitive'] ?? false,
                    ["[pattern-matchers][{$index}]", '[value]', '[type]']
                );
            }

            $this->structureBuilder->endValues();
        }
    }

    private function processOrder(SearchCondition $condition, array $array, FieldSet $fieldSet): void
    {
        $order = $array['order'] ?? [];

        if ($order === []) {
            /** @var FieldConfig $field */
            foreach ($fieldSet->all() as $name => $field) {
                if (OrderField::isOrder($name) && null !== $direction = $field->getOption('default')) {
                    $this->orderStructureBuilder->field($name, '[order][%s]');
                    $this->orderStructureBuilder->simpleValue($direction, '');
                    $this->orderStructureBuilder->endValues();
                }
            }

            $orderValuesGroup = $this->orderStructureBuilder->getRootGroup();

            if ($orderValuesGroup->countValues() > 0) {
                $condition->setOrder(new SearchOrder($orderValuesGroup));
            }

            return;
        }

        foreach ($order as $name => $direction) {
            $this->orderStructureBuilder->field('@' . $name, '[order][%s]');
            $this->orderStructureBuilder->simpleValue($direction, '');
            $this->orderStructureBuilder->endValues();
        }

        $orderCondition = new SearchOrder($this->orderStructureBuilder->getRootGroup());
        $condition->setOrder($orderCondition);
    }

    private function assertValueArrayHasKeys($array, array $requiredKeys, string $path): void
    {
        if (! \is_array($array)) {
            throw new InputProcessorException(implode('', $this->structureBuilder->getCurrentPath()) . $path,
                \sprintf('Expected value-structure to be an array, got %s instead.', \gettype($array))
            );
        }

        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (! \array_key_exists($key, $array)) {
                $missingKeys[] = $key;
            }
        }

        if ($missingKeys) {
            throw new InputProcessorException(implode('', $this->structureBuilder->getCurrentPath()) . $path,
                \sprintf(
                    'Expected value-structure to contain the following keys: %s. ' .
                    'But the following keys are missing: %s.',
                    implode(', ', $requiredKeys),
                    implode(', ', $missingKeys)
                )
            );
        }
    }
}
