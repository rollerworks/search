<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\FieldRequiredException;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * ArrayInput processes input provided as a PHP Array.
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
     * Process the input and returns the result.
     *
     * @param array $input
     *
     * @return null|ValuesGroup Returns null on empty input
     *
     * @throws \InvalidArgumentException When no array is given.
     */
    public function process($input)
    {
        if (!is_array($input)) {
            throw new \InvalidArgumentException('Provided in input must be an array.');
        }

        $valuesGroup = new ValuesGroup();
        if (isset($input['logical-case']) && 'OR' === strtoupper($input['logical-case'])) {
            $valuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
        }

        $this->processGroup($input, $valuesGroup, 0, 0, true);

        return $valuesGroup;
    }

    /**
     * @param array       $values
     * @param ValuesGroup $valuesGroup
     * @param integer     $groupIdx
     * @param integer     $level
     * @param boolean     $isRoot
     *
     * @throws FieldRequiredException
     * @throws ValuesOverflowException
     * @throws InputProcessorException
     */
    private function processGroup(array $values, ValuesGroup $valuesGroup, $groupIdx = 0, $level = 0, $isRoot = false)
    {
        $this->validateGroupNesting($groupIdx, $level);

        $countedPairs = array();
        $allFields = $this->fieldSet->all();

        if (empty($values['fields']) && empty($values['groups'])) {
            throw new InputProcessorException(sprintf('Empty group found in group %d at nesting level %d', $groupIdx, $level));
        }

        if (!isset($values['fields'])) {
            $values['fields'] = array();
        }

        if (!isset($values['groups'])) {
            $values['groups'] = array();
        }

        foreach ($values['fields'] as $name => $value) {
            $fieldName = $this->getFieldName($name);

            if (isset($countedPairs[$fieldName])) {
                $countedPairs[$fieldName] += $this->countValues($values['fields'][$fieldName]);
            } else {
                $countedPairs[$fieldName] = $this->countValues($values['fields'][$fieldName]);
            }

            if ($countedPairs[$fieldName] > $this->maxValues) {
                throw new ValuesOverflowException($fieldName, $this->maxValues, $countedPairs[$fieldName], $groupIdx, $level);
            }

            $filterConfig = $this->fieldSet->get($fieldName);

            if ($valuesGroup->hasField($fieldName)) {
                $this->valuesToBag($filterConfig, $value, $fieldName, $groupIdx, $level, $valuesGroup->getField($fieldName));
            } else {
                $valuesGroup->addField($fieldName, $this->valuesToBag($filterConfig, $value, $fieldName, $groupIdx, $level));
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

        $this->validateGroupsCount($this->maxGroups, count($values['groups']), $level);

        foreach ($values['groups'] as $index => $group) {
            $subValuesGroup = new ValuesGroup();

            if (isset($group['logical-case']) && 'OR' === strtoupper($group['logical-case'])) {
                $subValuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
            }

            $this->processGroup($group, $subValuesGroup, $index, ($isRoot ? 0 : $level+1));
            $valuesGroup->addGroup($subValuesGroup);
        }
    }

    /**
     * Converts the values list to an FilterValuesBag object.
     *
     * @param FieldConfigInterface $fieldConfig
     * @param array|string         $values
     * @param string               $fieldName
     * @param integer              $groupIdx
     * @param integer              $level
     * @param ValuesBag|null       $valuesBag
     *
     * @return ValuesBag
     *
     * @throws FieldRequiredException
     * @throws InputProcessorException
     */
    private function valuesToBag(FieldConfigInterface $fieldConfig, array $values, $fieldName, $groupIdx, $level = 0, ValuesBag $valuesBag = null)
    {
        if (!isset($values['single-values'])) {
            $values['single-values'] = array();
        }

        if (!isset($values['excluded-values'])) {
            $values['excluded-values'] = array();
        }

        if (!isset($values['ranges'])) {
            $values['ranges'] = array();
        }

        if (!isset($values['excluded-ranges'])) {
            $values['excluded-ranges'] = array();
        }

        if (!isset($values['comparisons'])) {
            $values['comparisons'] = array();
        }

        if (!isset($values['pattern-matchers'])) {
            $values['pattern-matchers'] = array();
        }

        $hasValues = false;

        if (!$valuesBag) {
            $valuesBag = new ValuesBag();
        }

        if (count($values['comparisons'])) {
            $this->assertAcceptsType('comparison', $fieldName);
        }

        if ((count($values['ranges']) || count($values['excluded-ranges']))) {
            $this->assertAcceptsType('range', $fieldName);
        }

        if (count($values['pattern-matchers'])) {
            $this->assertAcceptsType('pattern-match', $fieldName);
        }

        foreach ($values['single-values'] as $index => $value) {
            if (!is_scalar($value)) {
                throw new InputProcessorException(sprintf('Single value at index %d in group %d at nesting level %d is not a scalar.', $index, $groupIdx, $level));
            }

            $valuesBag->addSingleValue(new SingleValue($value));
            $hasValues = true;
        }

        foreach ($values['excluded-values'] as $index => $value) {
            if (!is_scalar($value)) {
                throw new InputProcessorException(sprintf('Excluded value at index %d in group %d at nesting level %d is not scalar.', $index, $groupIdx, $level));
            }

            $valuesBag->addExcludedValue(new SingleValue($value));
            $hasValues = true;
        }

        foreach ($values['ranges'] as $index => $range) {
            if (!is_array($range) || !isset($range['lower'], $range['upper'])) {
                throw new InputProcessorException(sprintf('Range at index %d in group %d at nesting level %d is either not an array or is missing [lower] and/or [upper].', $index, $groupIdx, $level));
            }

            $valuesBag->addRange(
                new Range($range['lower'], $range['upper'],
                    (isset($range['inclusive-lower']) && false === (boolean) $range['inclusive-lower'] ? false : true),
                    (isset($range['inclusive-upper']) && false === (boolean) $range['inclusive-upper'] ? false : true)
                )
            );

            $hasValues = true;
        }

        foreach ($values['excluded-ranges'] as $index => $range) {
            if (!is_array($range) || !isset($range['lower'], $range['upper'])) {
                throw new InputProcessorException(sprintf('Excluding-range at index %d in group %d at nesting level %d is either not an array or is missing [lower] and/or [upper].', $index, $groupIdx, $level));
            }

            $valuesBag->addExcludedRange(
                new Range($range['lower'], $range['upper'],
                    (isset($range['inclusive-lower']) && false === (boolean) $range['inclusive-lower'] ? false : true),
                    (isset($range['inclusive-upper']) && false === (boolean) $range['inclusive-upper'] ? false : true)
                )
            );
            $hasValues = true;
        }

        foreach ($values['comparisons'] as $index => $comparison) {
            if (!is_array($comparison) || !isset($comparison['value'], $comparison['operator'])) {
                throw new InputProcessorException(sprintf('Comparison at index %d in group %d at nesting level %d is either not an array or is missing [value] and/or [operator].', $index, $groupIdx, $level));
            }

            $valuesBag->addComparison(new Compare($comparison['value'], $comparison['operator']));
            $hasValues = true;
        }

        foreach ($values['pattern-matchers'] as $index => $matcher) {
            if (!is_array($matcher) || !isset($matcher['value'], $matcher['type'])) {
                throw new InputProcessorException(sprintf('PatternMatcher at index %d in group %d at nesting level %d is either not an array or is missing [value] and/or [type].', $index, $groupIdx, $level));
            }

            $valuesBag->addPatternMatch(new PatternMatch($matcher['value'], $matcher['type'], isset($matcher['case-insensitive']) && true === (boolean) $matcher['case-insensitive']));
            $hasValues = true;
        }

        if (!$hasValues && $fieldConfig->isRequired()) {
            throw new FieldRequiredException($fieldName, $groupIdx, $level);
        }

        return $valuesBag;
    }

    /**
     * Counts all the values in an array.
     *
     * @param array $values
     *
     * @return integer
     */
    private function countValues(array $values)
    {
        $count = 0;

        if (isset($values['single-values'])) {
            $count += count($values['single-values']);
        }

        if (isset($values['excluded-values'])) {
            $count += count($values['excluded-values']);
        }

        if (isset($values['ranges'])) {
            $count += count($values['ranges']);
        }

        if (isset($values['excluded-ranges'])) {
            $count += count($values['excluded-ranges']);
        }

        if (isset($values['comparisons'])) {
            $count += count($values['comparisons']);
        }

        if (isset($values['pattern-matchers'])) {
            $count += count($values['pattern-matchers']);
        }

        return $count;
    }
}
