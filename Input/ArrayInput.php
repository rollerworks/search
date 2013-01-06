<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Input;

use Rollerworks\Bundle\RecordFilterBundle\Exception\ValidationException;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

/**
 * ArrayInput - accepts filtering preference as a PHP Array.
 *
 * The provided input must be structured.
 * The root is an array where each entry is as group with array('fieldname' => ( structure ))
 *
 * There structure can contain the following.
 *
 *  'single-values'   => array('value1', 'value2')
 *  'excluded-values' => array('my value1', 'my value2')
 *  'ranges'          => array(array('lower'=> 10, 'upper' => 20))
 *  'excluded-ranges' => array(array('lower'=> 25, 'upper' => 30))
 *  'comparisons'     => array(array('value'=> 50, 'operator' => '>'))
 *
 * "Value" must must be either an integer or string.
 * Note: Big integers must be strings.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
class ArrayInput extends AbstractInput
{
    /**
     * @var boolean
     */
    protected $parsed = false;

    /**
     * @var MessageBag
     */
    protected $messages;

    /**
     * @var array
     */
    protected $input;

    /**
     * @var string
     */
    protected $hash;

    /**
     * {@inheritdoc}
     */
    public function setInput($input)
    {
        if (!is_array($input)) {
            throw new \InvalidArgumentException('Provided in input must be an array.');
        }

        $this->messages = new MessageBag($this->translator);
        $this->hash = null;
        $this->parsed = false;
        $this->input = $input;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups()
    {
        if ($this->parsed) {
            return $this->groups;
        }

        if (!$this->input) {
            throw new \InvalidArgumentException('No filtering preference provided.');
        }

        try {
            if (count($this->input) > $this->limitGroups) {
                throw new ValidationException('record_filter.maximum_groups_exceeded', array('{{ limit }}' => $this->limitGroups));
            }

            foreach ($this->input as $i => $group) {
                $this->processGroup($group, $i + 1);
            }
        } catch (ValidationException $e) {
            $this->messages->addError($e->getMessage(), $e->getParams());

            return false;
        }

        return $this->groups;
    }

    /**
     * Returns the error message(s) of the last process.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages->get(MessageBag::MSG_ERROR);
    }

    /**
     * {@inheritdoc}
     */
    public function getHash()
    {
        if (!$this->hash) {
            $this->hash = md5(serialize($this->input));
        }

        return $this->hash;
    }

    /**
     * @param array   $properties
     * @param integer $groupId
     *
     * @throws ValidationException
     */
    protected function processGroup(array $properties, $groupId)
    {
        $filterPairs = array();
        foreach ($properties as $label => $value) {
            $name = $this->getFieldNameByLabel($label);
            if (!$this->fieldsSet->has($name)) {
                continue;
            }

            $filterConfig = $this->fieldsSet->get($name);
            if ($this->countValues($properties[$label]) > $this->limitValues) {
                throw new ValidationException('record_filter.maximum_values_exceeded', array('{{ limit }}' => $this->limitValues, '{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $groupId));
            }

            $filterPairs[$name] = $this->valuesToBag($filterConfig, $properties[$label], $groupId);
        }

        foreach ($this->fieldsSet->all() as $name => $filterConfig) {
            /** @var FilterField $filterConfig */
            if (empty($filterPairs[$name]) && true === $filterConfig->isRequired()) {
                throw new ValidationException('record_filter.required', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $groupId));
            }
        }

        $this->groups[] = $filterPairs;
    }

    /**
     * Converts the values list to an FilterValuesBag object.
     *
     * @param FilterField  $filterConfig
     * @param array|string $values
     * @param              $group
     *
     * @return FilterValuesBag
     *
     * @throws ValidationException
     */
    protected function valuesToBag(FilterField $filterConfig, array $values, $group)
    {
        if (!isset($values['single-values'])) {
            $values['single-values'] = array();
        }

        if (!isset($values['excluded-values'])) {
            $values['excluded-values'] = array();
        }

        if (!isset($values['comparisons'])) {
            $values['comparisons'] = array();
        }

        if (!isset($values['ranges'])) {
            $values['ranges'] = array();
        }

        if (!isset($values['excluded-ranges'])) {
            $values['excluded-ranges'] = array();
        }

        $ranges = $excludedRanges = $excludesValues = $compares = $singleValues = array();
        $hasValues = false;

        if (count($values['comparisons']) && !$filterConfig->acceptCompares()) {
            throw new ValidationException('record_filter.no_compare_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
        }

        if ((count($values['ranges']) || count($values['excluded-ranges'])) && !$filterConfig->acceptRanges()) {
            throw new ValidationException('record_filter.no_range_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
        }

        foreach ($values['single-values'] as $index => $value) {
            if (!is_scalar($value)) {
                throw new ValidationException(sprintf('Single value at index %s in group %s is not scalar.', $index, $group));
            }

            $singleValues[] = new SingleValue($value);
            $hasValues = true;
        }

        foreach ($values['excluded-values'] as $index => $value) {
            if (!is_scalar($value)) {
                throw new ValidationException(sprintf('excluded value at index %s in group %s is not scalar.', $index, $group));
            }

            $excludesValues[] = new SingleValue($value);
            $hasValues = true;
        }

        foreach ($values['comparisons'] as $index => $comparison) {
            if (!is_array($comparison) || !isset($comparison['value'], $comparison['operator']) ) {
                throw new ValidationException(sprintf('Comparison at index %s in group %s is either not an array or is missing [value] and/or [operator].', $index, $group));
            }

            if (!in_array($comparison['operator'], array('>=', '<=', '<>', '<', '>'))) {
                throw new ValidationException(sprintf('Unknown comparison operator at index %s in group %s.', $index, $group));
            }

            $compares[] = new Compare($comparison['value'], $comparison['operator']);
            $hasValues = true;
        }

        foreach ($values['ranges'] as $index => $range) {
            if (!is_array($range) || !isset($range['lower'], $range['upper']) ) {
                throw new ValidationException(sprintf('Range at index %s in group %s is either not an array or is missing [lower] and/or [upper].', $index, $group));
            }

            $ranges[] = new Range($range['lower'], $range['upper']);
            $hasValues = true;
        }

        foreach ($values['excluded-ranges'] as $index => $range) {
            if (!is_array($range) || !isset($range['lower'], $range['upper']) ) {
                throw new ValidationException(sprintf('Excluding-range at index %s in group %s is either not an array or is missing [lower] and/or [upper].', $index, $group));
            }

            $excludedRanges[] = new Range($range['lower'], $range['upper']);
            $hasValues = true;
        }

        if (!$hasValues && true === $filterConfig->isRequired()) {
            throw new ValidationException('record_filter.required', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
        }

        return new FilterValuesBag($filterConfig->getLabel(), '', $singleValues, $excludesValues, $ranges, $compares, $excludedRanges);
    }

    /**
     * Counts all the values in an array.
     *
     * @param array $values
     *
     * @return integer
     */
    protected function countValues(array $values)
    {
        $count = 0;

        if (isset($values['single-values'])) {
            $count += count($values['single-values']);
        }

        if (isset($values['excluded-values'])) {
            $count += count($values['excluded-values']);
        }

        if (isset($values['comparisons'])) {
            $count += count($values['comparisons']);
        }

        if (isset($values['ranges'])) {
            $count += count($values['ranges']);
        }

        if (isset($values['excluded-ranges'])) {
            $count += count($values['excluded-ranges']);
        }

        return $count;
    }
}
