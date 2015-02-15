<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\FieldRequiredException;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Util\XmlUtils;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * XmlInput processes input provided as an XML document.
 *
 * See the XSD in schema/dic/input/xml-input-1.0.xsd for more information
 * about the schema.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class XmlInput extends AbstractInput
{
    /**
     * {@inheritdoc}
     *
     * @param ProcessorConfig $config
     * @param string          $input
     */
    public function process(ProcessorConfig $config, $input)
    {
        if (!is_string($input)) {
            throw new UnexpectedTypeException($input, 'string');
        }

        $input = trim($input);

        if (empty($input)) {
            return;
        }

        $document = simplexml_import_dom(XmlUtils::parseXml($input, __DIR__.'/schema/dic/input/xml-input-1.0.xsd'));

        $this->config = $config;

        $valuesGroup = new ValuesGroup();
        if (isset($document['logical']) && 'OR' === strtoupper((string) $document['logical'])) {
            $valuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
        }

        $this->processGroup($document, $valuesGroup, 0, 0);

        $condition = new SearchCondition(
            $config->getFieldSet(),
            $valuesGroup
        );

        if ($condition->getValuesGroup()->hasErrors()) {
            throw new InvalidSearchConditionException($condition);
        }

        return $condition;
    }

    private function validateValuesCount($fieldName, $count, $groupIdx, $level)
    {
        if ($count > $this->config->getMaxValues()) {
            throw new ValuesOverflowException(
                $fieldName,
                $this->config->getMaxValues(),
                $count,
                $groupIdx,
                $level
            );
        }
    }

    private function processGroup(\SimpleXMLElement $values, ValuesGroup $valuesGroup, $groupIdx = 0, $level = 0)
    {
        $this->validateGroupNesting($groupIdx, $level);

        if (isset($values->fields)) {
            $this->processFields($values, $valuesGroup, $groupIdx, $level);
        }

        if (isset($values->groups)) {
            $this->processGroups($values, $valuesGroup, $groupIdx, $level);
        }
    }

    private function processFields(\SimpleXMLElement $values, ValuesGroup $valuesGroup, $groupIdx, $level)
    {
        $allFields = $this->config->getFieldSet()->all();

        foreach ($values->fields->children() as $element) {
            /** @var \SimpleXMLElement $element */
                $fieldName = $this->getFieldName((string) $element['name']);
            $fieldConfig = $this->config->getFieldSet()->get($fieldName);

            if ($valuesGroup->hasField($fieldName)) {
                $this->valuesToBag(
                        $fieldConfig,
                        $element,
                        $valuesGroup->getField($fieldName),
                        $groupIdx,
                        $level
                    );
            } else {
                $valuesGroup->addField(
                        $fieldName,
                        $this->valuesToBag($fieldConfig, $element, new ValuesBag(), $groupIdx, $level)
                    );
            }

            unset($allFields[$fieldName]);
        }

        // Now run trough all the remaining fields and look if there are required
        // Fields that were set without values have already been checked by valuesToBag()
        // This is only run when there are fields in the group as a group can also contain only groups
        if ($values->fields->children()->count()) {
            foreach ($allFields as $fieldName => $fieldConfig) {
                if ($fieldConfig->isRequired()) {
                    throw new FieldRequiredException($fieldName, $groupIdx, $level);
                }
            }
        }
    }

    private function processGroups(\SimpleXMLElement $values, ValuesGroup $valuesGroup, $groupIdx, $level)
    {
        $this->validateGroupsCount($groupIdx, $values->groups->children()->count(), $level);

        $index = 0;

        foreach ($values->groups->children() as $element) {
            $subValuesGroup = new ValuesGroup();

            if (isset($element['logical']) && 'OR' === strtoupper($element['logical'])) {
                $subValuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
            }

            $this->processGroup(
                    $element,
                    $subValuesGroup,
                    $index,
                    $level+1
                );

            $valuesGroup->addGroup($subValuesGroup);
            $index++;
        }
    }

    private function valuesToBag(
        FieldConfigInterface $fieldConfig,
        \SimpleXMLElement $values,
        ValuesBag $valuesBag,
        $groupIdx,
        $level = 0
    ) {
        if (isset($values->comparisons)) {
            $this->assertAcceptsType($fieldConfig, 'comparison');
        }

        if (isset($values->ranges) || isset($values->{'excluded-ranges'})) {
            $this->assertAcceptsType($fieldConfig, 'range');
        }

        if (isset($values->{'pattern-matchers'})) {
            $this->assertAcceptsType($fieldConfig, 'pattern-match');
        }

        $count = $valuesBag->count();
        $fieldName = $fieldConfig->getName();

        $factory = new FieldValuesFactory($fieldConfig, $valuesBag);

        if (isset($values->{'single-values'})) {
            foreach ($values->{'single-values'}->children() as $value) {
                $this->validateValuesCount($fieldName, $count, $groupIdx, $level);
                $count++;

                $factory->addSingleValue($value);
            }
        }

        if (isset($values->{'excluded-values'})) {
            foreach ($values->{'excluded-values'}->children() as $value) {
                $this->validateValuesCount($fieldName, $count, $groupIdx, $level);
                $count++;

                $factory->addExcludedValue($value);
            }
        }

        if (isset($values->comparisons)) {
            foreach ($values->comparisons->children() as $comparison) {
                $this->validateValuesCount($fieldName, $count, $groupIdx, $level);
                $count++;

                $factory->addComparisonValue((string) $comparison['operator'], (string) $comparison);
            }
        }

        if (isset($values->ranges)) {
            foreach ($values->ranges->children() as $range) {
                $this->validateValuesCount($fieldName, $count, $groupIdx, $level);
                $count++;

                $this->processRange($range, $factory);
            }
        }

        if (isset($values->{'excluded-ranges'})) {
            foreach ($values->{'excluded-ranges'}->children() as $range) {
                $this->validateValuesCount($fieldName, $count, $groupIdx, $level);
                $count++;

                $this->processRange($range, $factory, true);
            }
        }

        if (isset($values->{'pattern-matchers'})) {
            foreach ($values->{'pattern-matchers'}->children() as $patternMatch) {
                $this->validateValuesCount($fieldName, $count, $groupIdx, $level);
                $count++;

                $factory->addPatterMatch(
                    (string) $patternMatch['type'],
                    (string) $patternMatch,
                    'true' === strtolower($patternMatch['case-insensitive'])
                );
            }
        }

        if (0 === $count && $fieldConfig->isRequired()) {
            throw new FieldRequiredException($fieldConfig->getName(), $groupIdx, $level);
        }

        return $valuesBag;
    }

    private function processRange($range, FieldValuesFactory $factory, $negative = false)
    {
        $lowerInclusive = 'false' !== strtolower($range->lower['inclusive']);
        $upperInclusive = 'false' !== strtolower($range->upper['inclusive']);

        $lowerBound = (string) $range->lower;
        $upperBound = (string) $range->upper;

        if ($negative) {
            $factory->addExcludedRange($lowerBound, $upperBound, $lowerInclusive, $upperInclusive);
        } else {
            $factory->addRange($lowerBound, $upperBound, $lowerInclusive, $upperInclusive);
        }
    }
}
