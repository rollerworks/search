<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\FieldRequiredException;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * ArrayInput processes input provided as a PHP Array.
 *
 * Note: The values must in the view-format, transforming is done later.
 * Normalized values are created using the Field's DataTransformers.
 *
 * The provided input must be structured as follow.
 *
 * Each entry must contain an array with either 'fields' and/or groups.
 * Optionally the array can contain logical-case => 'AND' to make it AND-cased.
 *
 * The groups array contains numeric groups with and the value as described above (fields and/or groups).
 *
 * The fields array is an associative array where each key is the field-name and the value as follow.
 * All the keys are optional, but at least one must exists.
 *
 *  'single-values'   => array('value1', 'value2')
 *  'excluded-values' => array('my value1', 'my value2')
 *  'ranges'          => array(array('lower'=> 10, 'upper' => 20))
 *  'excluded-ranges' => array(array('lower'=> 25, 'upper' => 30))
 *  'comparisons'     => array(array('value'=> 50, 'operator' => '>'))
 *  'pattern-matchers' => array(array('value'=> 'foo', 'type' => 'STARTS_WITH'))
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ArrayInput extends AbstractInput
{
    /**
     * {@inheritdoc}
     *
     * @param ProcessorConfig $config
     * @param array           $input
     */
    public function process(ProcessorConfig $config, $input)
    {
        if (!is_array($input)) {
            throw new \InvalidArgumentException('Provided in input must be an array.');
        }

        if (empty($input)) {
            return;
        }

        $this->config = $config;

        $valuesGroup = new ValuesGroup();

        if (isset($input['logical-case']) && 'OR' === strtoupper($input['logical-case'])) {
            $valuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
        }

        $this->processGroup($input, $valuesGroup, 0, 0);

        $condition = new SearchCondition(
            $config->getFieldSet(), $valuesGroup
        );

        if ($condition->getValuesGroup()->hasErrors()) {
            throw new InvalidSearchConditionException($condition);
        }

        return $condition;
    }

    /**
     * @param array       $values
     * @param ValuesGroup $valuesGroup
     * @param int         $groupIdx
     * @param int         $level
     *
     * @throws FieldRequiredException
     * @throws ValuesOverflowException
     * @throws InputProcessorException
     */
    private function processGroup(
        array $values,
        ValuesGroup $valuesGroup,
        $groupIdx = 0,
        $level = 0
    ) {
        $this->validateGroupNesting($groupIdx, $level);

        if (empty($values['fields']) && empty($values['groups'])) {
            throw new InputProcessorException(
                sprintf('Empty group found in group %d at nesting level %d', $groupIdx, $level)
            );
        }

        if (isset($values['fields'])) {
            $this->processFields($values['fields'], $valuesGroup, $groupIdx, $level);
        }

        if (isset($values['groups'])) {
            $this->validateGroupsCount($groupIdx, count($values['groups']), $level);
            $this->processGroups($values['groups'], $valuesGroup, $level);
        }
    }

    private function processFields(array $values, ValuesGroup $valuesGroup, $groupIdx, $level)
    {
        $countedPairs = array();
        $allFields = $this->config->getFieldSet()->all();

        foreach ($values as $name => $value) {
            $fieldName = $this->getFieldName($name);
            $fieldConfig = $this->config->getFieldSet()->get($fieldName);

            $value = array_merge(
                array(
                    'single-values' => array(),
                    'excluded-values' => array(),
                    'ranges' => array(),
                    'excluded-ranges' => array(),
                    'comparisons' => array(),
                    'pattern-matchers' => array(),
                ),
                $value
            );

            if (isset($countedPairs[$fieldName])) {
                $countedPairs[$fieldName] += $this->countValues($value);
            } else {
                $countedPairs[$fieldName] = $this->countValues($value);
            }

            if ($countedPairs[$fieldName] > $this->config->getMaxValues()) {
                throw new ValuesOverflowException(
                    $fieldName, $this->config->getMaxValues(), $countedPairs[$fieldName], $groupIdx, $level
                );
            }

            if ($valuesGroup->hasField($fieldName)) {
                $this->valuesToBag(
                    $fieldConfig,
                    $value,
                    $valuesGroup->getField($fieldName),
                    $groupIdx,
                    $level
                );
            } else {
                $valuesGroup->addField(
                    $fieldName,
                    $this->valuesToBag($fieldConfig, $value, new ValuesBag(), $groupIdx, $level)
                );
            }

            unset($allFields[$fieldName]);
        }

        // Now run trough all the remaining fields and look if there are required
        // Fields that were set without values have already been checked by valuesToBag()
        foreach ($allFields as $fieldName => $filterConfig) {
            if ($filterConfig->isRequired()) {
                throw new FieldRequiredException($fieldName, $groupIdx, $level);
            }
        }
    }

    private function processGroups(array $groups, ValuesGroup $valuesGroup, $level)
    {
        foreach ($groups as $index => $values) {
            $subValuesGroup = new ValuesGroup();

            if (isset($values['logical-case']) && 'OR' === strtoupper($values['logical-case'])) {
                $subValuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
            }

            $this->processGroup($values, $subValuesGroup, $index, $level + 1);
            $valuesGroup->addGroup($subValuesGroup);
        }
    }

    private function valuesToBag(
        FieldConfigInterface $fieldConfig,
        array $values,
        ValuesBag $valuesBag,
        $groupIdx,
        $level = 0
    ) {
        if (count($values['comparisons'])) {
            $this->assertAcceptsType($fieldConfig, 'comparison');
        }

        if ((count($values['ranges']) || count($values['excluded-ranges']))) {
            $this->assertAcceptsType($fieldConfig, 'range');
        }

        if (count($values['pattern-matchers'])) {
            $this->assertAcceptsType($fieldConfig, 'pattern-match');
        }

        if (!$this->countValues($values) && $fieldConfig->isRequired()) {
            throw new FieldRequiredException($fieldConfig->getName(), $groupIdx, $level);
        }

        $factory = new FieldValuesFactory($fieldConfig, $valuesBag);

        foreach ($values['single-values'] as $index => $value) {
            if (!is_scalar($value)) {
                throw new InputProcessorException(
                    sprintf(
                        'Single value at index %d in group %d at nesting level %d is not a scalar with field "%s".',
                        $index,
                        $groupIdx,
                        $level,
                        $fieldConfig->getName()
                    )
                );
            }

            $factory->addSingleValue($value);
        }

        foreach ($values['excluded-values'] as $index => $value) {
            if (!is_scalar($value)) {
                throw new InputProcessorException(
                    sprintf(
                        'Excluded value at index %d in group %d at nesting level %d is not scalar with field "%s".',
                        $index,
                        $groupIdx,
                        $level,
                        $fieldConfig->getName()
                    )
                );
            }

            $factory->addExcludedValue($value);
        }

        foreach ($values['ranges'] as $index => $range) {
            $this->assertArrayKeysExists($range, array('lower', 'upper'), $index, $groupIdx, $level);
            $this->processRange($range, $factory);
        }

        foreach ($values['excluded-ranges'] as $index => $range) {
            $this->assertArrayKeysExists($range, array('lower', 'upper'), $index, $groupIdx, $level);
            $this->processRange($range, $factory, true);
        }

        foreach ($values['comparisons'] as $index => $comparison) {
            $this->assertArrayKeysExists($comparison, array('value', 'operator'), $index, $groupIdx, $level);
            $factory->addComparisonValue($comparison['operator'], $comparison['value']);
        }

        foreach ($values['pattern-matchers'] as $index => $matcher) {
            $this->assertArrayKeysExists($matcher, array('value', 'type'), $index, $groupIdx, $level);

            $factory->addPatterMatch(
                $matcher['type'],
                $matcher['value'],
                isset($matcher['case-insensitive']) && true === (bool) $matcher['case-insensitive']
            );
        }

        return $valuesBag;
    }

    private function assertArrayKeysExists($array, $requiredKeys, $index, $groupIdx, $level = 0)
    {
        if (!is_array($array)) {
            throw new InputProcessorException(
                sprintf(
                    'Expected value-structure at index %d in group %d at nesting level %d '.
                    'to be an array, got an %s instead.',
                    $index,
                    $groupIdx,
                    $level,
                    gettype($array)
                )
            );
        }

        $missingKeys = array();

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $array)) {
                $missingKeys[] = $key;
            }
        }

        if ($missingKeys) {
            throw new InputProcessorException(
                sprintf(
                    'Expected value-structure at path %s to contain the following keys: %s. '.
                    'But the following keys are missing: %s.',
                    implode(', ', $requiredKeys),
                    implode(', ', $missingKeys)
                )
            );
        }
    }

    private function processRange($range, FieldValuesFactory $factory, $negative = false)
    {
        $lowerInclusive = isset($range['inclusive-lower']) ? (bool) $range['inclusive-lower'] : true;
        $upperInclusive = isset($range['inclusive-upper']) ? (bool) $range['inclusive-upper'] : true;

        $lowerBound = (string) $range['lower'];
        $upperBound = (string) $range['upper'];

        if ($negative) {
            $factory->addExcludedRange($lowerBound, $upperBound, $lowerInclusive, $upperInclusive);
        } else {
            $factory->addRange($lowerBound, $upperBound, $lowerInclusive, $upperInclusive);
        }
    }

    private function countValues(array $values)
    {
        $count =
            count($values['single-values']) +
            count($values['excluded-values']) +
            count($values['ranges']) +
            count($values['excluded-ranges']) +
            count($values['comparisons']) +
            count($values['pattern-matchers'])
        ;

        return $count;
    }
}
