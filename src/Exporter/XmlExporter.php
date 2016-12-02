<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Exports the SearchCondition as XML Document.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class XmlExporter extends AbstractExporter
{
    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * Exports the SearchCondition.
     *
     * @param SearchConditionInterface $condition    The SearchCondition to export
     * @param bool                     $formatOutput Set whether to format the output (default true)
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function exportCondition(SearchConditionInterface $condition, $formatOutput = true)
    {
        $this->document = new \DOMDocument('1.0', 'utf-8');
        $this->document->formatOutput = $formatOutput;

        $searchRoot = $this->document->createElement('search');
        $searchRoot->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $searchRoot->setAttribute(
            'xsi:schemaLocation',
            'http://rollerworks.github.io/search/input/schema/search http://rollerworks.github.io/schema/search/xml-input-1.0.xsd'
        );

        $searchRoot->setAttribute('logical', $condition->getValuesGroup()->getGroupLogical());

        $this->exportGroupNode($searchRoot, $condition->getValuesGroup(), $condition->getFieldSet());
        $this->document->appendChild($searchRoot);

        $xml = $this->document->saveXML();
        $this->document = null;

        return $xml;
    }

    /**
     * {@inheritdoc}
     *
     * @ignore
     */
    protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $isRoot = false)
    {
        // no-op
    }

    /**
     * @param \DOMNode    $parent
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     */
    private function exportGroupNode(\DOMNode $parent, ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        $fields = $valuesGroup->getFields();

        if ($valuesGroup->countValues() > 0) {
            $fieldsNode = $this->document->createElement('fields');

            foreach ($fields as $name => $values) {
                if (!$values->count()) {
                    continue;
                }

                $fieldNode = $this->document->createElement('field');
                $fieldNode->setAttribute('name', $name);

                $this->exportValuesToNode($values, $fieldNode, $fieldSet->get($name));
                $fieldsNode->appendChild($fieldNode);
            }

            $parent->appendChild($fieldsNode);
        }

        if ($valuesGroup->hasGroups()) {
            $groupsNode = $this->document->createElement('groups');

            foreach ($valuesGroup->getGroups() as $group) {
                $groupNode = $this->document->createElement('group');
                $groupNode->setAttribute('logical', $group->getGroupLogical());

                $this->exportGroupNode($groupNode, $group, $fieldSet);
                $groupsNode->appendChild($groupNode);
            }

            $parent->appendChild($groupsNode);
        }
    }

    /**
     * @param ValuesBag $valuesBag
     * @param \DOMNode  $parent
     *
     * @return \DOMNode
     */
    private function exportValuesToNode(ValuesBag $valuesBag, \DOMNode $parent, FieldConfigInterface $field)
    {
        if ($valuesBag->hasSimpleValues()) {
            $valuesNode = $this->document->createElement('single-values');

            foreach ($valuesBag->getSimpleValues() as $value) {
                $element = $this->document->createElement('value');
                $element->appendChild(
                    $this->document->createTextNode(
                        $this->normToView($value, $field)
                    )
                );

                $valuesNode->appendChild($element);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->hasExcludedSimpleValues()) {
            $valuesNode = $this->document->createElement('excluded-values');

            foreach ($valuesBag->getExcludedSimpleValues() as $value) {
                $element = $this->document->createElement('value');
                $element->appendChild(
                    $this->document->createTextNode(
                        $this->normToView($value, $field)
                    )
                );

                $valuesNode->appendChild($element);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->has(Range::class)) {
            $valuesNode = $this->document->createElement('ranges');

            foreach ($valuesBag->get(Range::class) as $value) {
                $this->exportRangeValueToNode($valuesNode, $value, $field);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->has(ExcludedRange::class)) {
            $valuesNode = $this->document->createElement('excluded-ranges');

            foreach ($valuesBag->get(ExcludedRange::class) as $value) {
                $this->exportRangeValueToNode($valuesNode, $value, $field);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->has(Compare::class)) {
            $valuesNode = $this->document->createElement('comparisons');

            foreach ($valuesBag->get(Compare::class) as $value) {
                $element = $this->document->createElement('compare');
                $element->setAttribute('operator', $value->getOperator());
                $element->appendChild(
                    $this->document->createTextNode($this->normToView($value->getValue(), $field))
                );

                $valuesNode->appendChild($element);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->has(PatternMatch::class)) {
            $valuesNode = $this->document->createElement('pattern-matchers');

            foreach ($valuesBag->get(PatternMatch::class) as $value) {
                $element = $this->document->createElement('pattern-matcher');
                $element->setAttribute('type', strtolower($this->getPatternMatchType($value)));
                $element->setAttribute('case-insensitive', $value->isCaseInsensitive() ? 'true' : 'false');
                $element->appendChild(
                    $this->document->createTextNode($value->getValue())
                );

                $valuesNode->appendChild($element);
            }

            $parent->appendChild($valuesNode);
        }
    }

    /**
     * @param \DOMNode $parent
     * @param Range    $range
     *
     * @return array
     */
    private function exportRangeValueToNode(\DOMNode $parent, Range $range, FieldConfigInterface $field)
    {
        $rangeNode = $this->document->createElement('range');

        $element = $this->document->createElement('lower');
        $element->appendChild(
            $this->document->createTextNode($this->normToView($range->getLower(), $field))
        );

        if (!$range->isLowerInclusive()) {
            $element->setAttribute('inclusive', 'false');
        }

        $rangeNode->appendChild($element);

        $element = $this->document->createElement('upper');
        $element->appendChild(
            $this->document->createTextNode($this->normToView($range->getUpper(), $field))
        );

        if (!$range->isUpperInclusive()) {
            $element->setAttribute('inclusive', 'false');
        }

        $rangeNode->appendChild($element);

        // Add to parent (<ranges> Node).
        $parent->appendChild($rangeNode);
    }
}
