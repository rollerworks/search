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

use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * ArrayInput processes input provided as a PHP Array.
 *
 * Note: The values must in the normalize-format, transforming is done later.
 * Normalized values are created using the Field's norm DataTransformer.
 *
 * The provided input must be structured as follow;
 *
 * Each entry must contain an array with 'fields' and/or 'groups' structures.
 * Optionally the array can contain 'logical-case' => 'OR' to make it OR-cased.
 *
 * The 'groups' array contains groups with the keys as described above ('fields' and/or 'groups').
 *
 * The fields array is an associative array where each key is the field-name
 * and the value as follow; All the types are optional, but at least one must exists.
 *
 * ```
 * 'field-name' => [
 *   'simple-values' => ['value1', 'value2'],
 *   'simple-excluded-values' => ['my value1', 'my value2'],
 *   'ranges' => [['lower'=> 10, 'upper' => 20]],
 *   'excluded-ranges' => [['lower'=> 25, 'upper' => 30, 'inclusive-lower' => true, 'inclusive-upper' => true]],
 *   'comparisons' => [['value'=> 50, 'operator' => '>']],
 *   'pattern-matchers' => [['value'=> 'foo', 'type' => 'STARTS_WITH', 'case-insensitive' => false]],
 * ]
 * ```
 *
 * Note: 'inclusive-lower', 'inclusive-upper' and 'case-insensitive' are optional.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ArrayInput extends AbstractInput
{
    /**
     * @var FieldValuesFactory|null
     */
    private $valuesFactory;

    /**
     * {@inheritdoc}
     *
     * @param ProcessorConfig $config
     * @param array           $input
     *
     * @throws UnexpectedTypeException When provided input is not an array
     */
    public function process(ProcessorConfig $config, $input): SearchCondition
    {
        if (!is_array($input)) {
            throw new UnexpectedTypeException($input, 'array');
        }

        if (0 === count($input)) {
            return new SearchCondition($config->getFieldSet(), new ValuesGroup());
        }

        $condition = null;
        $this->errors = new ErrorList();
        $this->config = $config;
        $this->level = 0;

        $this->valuesFactory = new FieldValuesFactory($this->errors, $this->validator, $this->config->getMaxValues());

        try {
            $valuesGroup = new ValuesGroup($input['logical-case'] ?? ValuesGroup::GROUP_LOGICAL_AND);
            $this->processGroup($input, $valuesGroup);

            $condition = new SearchCondition($config->getFieldSet(), $valuesGroup);

            $this->assertLevel0();
        } catch (InputProcessorException $e) {
            $this->errors[] = $e->toErrorMessageObj();
        } finally {
            $this->valuesFactory = null;
        }

        if (count($this->errors)) {
            $errors = $this->errors->getArrayCopy();

            throw new InvalidSearchConditionException($errors);
        }

        return $condition;
    }

    private function processGroup(array $values, ValuesGroup $valuesGroup, string $path = '')
    {
        $this->validateGroupNesting($path);
        $this->processFields($values['fields'] ?? [], $valuesGroup, $path.'[fields]');

        if (isset($values['groups'])) {
            $c = count($values['groups']);

            $this->validateGroupsCount($c, $path);

            ++$this->level;
            $this->processGroups($values['groups'], $valuesGroup, $path);
            --$this->level;
        }
    }

    private function processFields(array $values, ValuesGroup $valuesGroup, string $path)
    {
        foreach ($values as $name => $value) {
            $value = array_merge(
                [
                    'simple-values' => [],
                    'excluded-simple-values' => [],
                    'ranges' => [],
                    'excluded-ranges' => [],
                    'comparisons' => [],
                    'pattern-matchers' => [],
                ],
                $value
            );

            $valuesGroup->addField(
                $name,
                $this->valuesToBag($this->config->getFieldSet()->get($name), $value, new ValuesBag(), "{$path}[$name]")
            );
        }
    }

    private function processGroups(array $groups, ValuesGroup $valuesGroup, string $path)
    {
        foreach ($groups as $index => $values) {
            $subValuesGroup = new ValuesGroup($values['logical-case'] ?? ValuesGroup::GROUP_LOGICAL_AND);

            $this->processGroup($values, $subValuesGroup, "{$path}[groups][{$index}]");
            $valuesGroup->addGroup($subValuesGroup);
        }
    }

    private function valuesToBag(FieldConfig $field, array $values, ValuesBag $valuesBag, string $path)
    {
        $this->valuesFactory->initContext($field, $valuesBag, $path);

        foreach ($values['simple-values'] as $index => $value) {
            $this->valuesFactory->addSimpleValue($value, "[simple-values][$index]");
        }

        foreach ($values['excluded-simple-values'] as $index => $value) {
            $this->valuesFactory->addExcludedSimpleValue($value, "[excluded-simple-values][$index]");
        }

        foreach ($values['ranges'] as $index => $range) {
            $this->assertArrayKeysExists($range, ['lower', 'upper'], "{$path}[ranges][$index]");
            $this->processRange($range, false, $index);
        }

        foreach ($values['excluded-ranges'] as $index => $range) {
            $this->assertArrayKeysExists($range, ['lower', 'upper'], "{$path}[excluded-ranges][$index]");
            $this->processRange($range, true, $index);
        }

        foreach ($values['comparisons'] as $index => $comparison) {
            $this->assertArrayKeysExists($comparison, ['value', 'operator'], "{$path}[comparisons][$index]");
            $this->valuesFactory->addComparisonValue($comparison['operator'], $comparison['value'], ["[comparisons][$index]", '[operator]', '[value]']);
        }

        foreach ($values['pattern-matchers'] as $index => $matcher) {
            $this->assertArrayKeysExists($matcher, ['value', 'type'], "{$path}[pattern-matchers][$index]");
            $this->valuesFactory->addPatterMatch(
                $matcher['type'],
                $matcher['value'],
                true === (bool) ($matcher['case-insensitive'] ?? false),
                ["[pattern-matchers][$index]", '[value]', '[type]']
            );
        }

        return $valuesBag;
    }

    private function assertArrayKeysExists($array, array $requiredKeys, string $path)
    {
        if (!is_array($array)) {
            throw new InputProcessorException($path,
                sprintf('Expected value-structure to be an array, got %s instead.', gettype($array))
            );
        }

        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $array)) {
                $missingKeys[] = $key;
            }
        }

        if ($missingKeys) {
            throw new InputProcessorException($path,
                sprintf(
                    'Expected value-structure to contain the following keys: %s. '.
                    'But the following keys are missing: %s.',
                    implode(', ', $requiredKeys),
                    implode(', ', $missingKeys)
                )
            );
        }
    }

    private function processRange(array $range, bool $negative, int $index)
    {
        $lowerInclusive = (bool) ($range['inclusive-lower'] ?? true);
        $upperInclusive = (bool) ($range['inclusive-upper'] ?? true);

        if ($negative) {
            $this->valuesFactory->addExcludedRange($range['lower'], $range['upper'], $lowerInclusive, $upperInclusive, ["[excluded-ranges][$index]", '[lower]', '[upper]']);
        } else {
            $this->valuesFactory->addRange($range['lower'], $range['upper'], $lowerInclusive, $upperInclusive, ["[ranges][$index]", '[lower]', '[upper]']);
        }
    }
}
