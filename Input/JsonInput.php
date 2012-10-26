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

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Rollerworks\Bundle\RecordFilterBundle\Exception\ValidationException;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

/**
 * JsonInput - accepts filtering preference in JSON format.
 *
 * The provided input must be structured.
 * The root is an array where each entry is group with { "fieldname": { structure } }
 *
 * There structure can contain the following.
 *
 *  "single-values":    [ "value1", "value2" ]
 *  "excluded-values":  [ "my value1", "my value2" ]
 *  "ranges":           [ { "lower": 10, "upper": 20 } ]
 *  "excluded-ranges":  [ { "lower": 25, "upper": 30 } ]
 *  "comparisons":      [ { "value": 50,"operator": ">" } ]
 *
 * "Value" must must be either an integer or string.
 * Note: Big integers will be converted to strings.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class JsonInput extends AbstractInput
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
     * @var string
     */
    protected $input;

    /**
     * Set the filtering preference.
     *
     * @param string $input
     *
     * @return self
     */
    public function setInput($input)
    {
        $this->messages = new MessageBag($this->translator);
        $this->parsed = false;
        $this->input = trim($input);

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
            $this->validateSyntax($this->input);
            $groups = json_decode($this->input, true);

            foreach ($groups as $i => $group) {
                $this->processGroup($group, $i + 1);
            }
        } catch (ValidationException $e) {
            $this->messages->addError($e->getMessage(), $e->getParams());

            return false;
        }

        return $this->groups;
    }

    /**
     * Returns the error message(s) of the failure.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages->get(MessageBag::MSG_ERROR);
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
        foreach ($this->fieldsSet->all() as $name => $filterConfig) {
            $name = (function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name));

            /** @var FilterField $filterConfig */
            if (empty($properties[$name])) {
                if (true === $filterConfig->isRequired()) {
                    throw new ValidationException('required', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $groupId));
                }

                continue;
            }

            $filterPairs[$name] = $this->valuesToBag($filterConfig, $properties[$name], $groupId);
        }

        $this->groups[] = $filterPairs;
    }

    /**
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
            throw new ValidationException('no_compare_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
        }

        if ((count($values['ranges']) || count($values['excluded-ranges'])) && !$filterConfig->acceptRanges()) {
            throw new ValidationException('no_range_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
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
            throw new ValidationException('required', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
        }

        return new FilterValuesBag($filterConfig->getLabel(), '', $singleValues, $excludesValues, $ranges, $compares, $excludedRanges);
    }

    /**
     * @param string $json
     *
     * @return boolean true on success
     *
     * @throws ParsingException
     * @throws \UnexpectedValueException
     */
    protected static function validateSyntax($json)
    {
        $parser = new JsonParser();
        $result = $parser->lint($json);
        if (null === $result) {
            if (defined('JSON_ERROR_UTF8') && JSON_ERROR_UTF8 === json_last_error()) {
                throw new \UnexpectedValueException('Input is not UTF-8, could not parse as JSON');
            }

            return true;
        }

        throw new ParsingException('Input does not contain valid JSON'."\n".$result->getMessage(), $result->getDetails());
    }
}
