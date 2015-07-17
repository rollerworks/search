<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Util\XmlUtil;
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

        $document = simplexml_import_dom(XmlUtil::parseXml($input, __DIR__.'/schema/dic/input/xml-input-1.0.xsd'));

        $this->config = $config;

        $valuesGroup = new ValuesGroup(
            isset($document['logical']) ? (string) $document['logical'] : ValuesGroup::GROUP_LOGICAL_AND
        );

        $this->processGroup($document, $valuesGroup, 0, 0);

        $condition = new SearchCondition(
            $config->getFieldSet(),
            $valuesGroup
        );

        if ($condition->getValuesGroup()->hasErrors(true)) {
            throw new InvalidSearchConditionException($condition);
        }

        return $condition;
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
        /** @var \SimpleXMLElement $element */
        foreach ($values->fields->children() as $element) {
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
        }
    }

    private function processGroups(\SimpleXMLElement $values, ValuesGroup $valuesGroup, $groupIdx, $level)
    {
        $this->validateGroupsCount($groupIdx, $values->groups->children()->count(), $level);

        $index = 0;

        foreach ($values->groups->children() as $element) {
            $subValuesGroup = new ValuesGroup(
                isset($element['logical']) ? (string) $element['logical'] : ValuesGroup::GROUP_LOGICAL_AND
            );

            $this->processGroup($element, $subValuesGroup, $index, $level + 1);

            $valuesGroup->addGroup($subValuesGroup);
            ++$index;
        }
    }

    private function valuesToBag(
        FieldConfigInterface $fieldConfig,
        \SimpleXMLElement $values,
        ValuesBag $valuesBag,
        $groupIdx,
        $level = 0
    ) {
        $factory = new FieldValuesFactory($fieldConfig, $valuesBag, $this->config->getMaxValues(), $groupIdx, $level);

        if (isset($values->{'single-values'})) {
            foreach ($values->{'single-values'}->children() as $value) {
                $factory->addSingleValue((string) $value);
            }
        }

        if (isset($values->{'excluded-values'})) {
            foreach ($values->{'excluded-values'}->children() as $value) {
                $factory->addExcludedValue((string) $value);
            }
        }

        if (isset($values->comparisons)) {
            foreach ($values->comparisons->children() as $comparison) {
                $factory->addComparisonValue((string) $comparison['operator'], (string) $comparison);
            }
        }

        if (isset($values->ranges)) {
            foreach ($values->ranges->children() as $range) {
                $this->processRange($range, $factory);
            }
        }

        if (isset($values->{'excluded-ranges'})) {
            foreach ($values->{'excluded-ranges'}->children() as $range) {
                $this->processRange($range, $factory, true);
            }
        }

        if (isset($values->{'pattern-matchers'})) {
            foreach ($values->{'pattern-matchers'}->children() as $patternMatch) {
                $factory->addPatterMatch(
                    (string) $patternMatch['type'],
                    (string) $patternMatch,
                    'true' === strtolower($patternMatch['case-insensitive'])
                );
            }
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
