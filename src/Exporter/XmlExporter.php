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

use Rollerworks\Component\Search\FieldLabelResolver\NoopLabelResolver;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

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
     * @param SearchConditionInterface $condition     The SearchCondition to export
     * @param bool                     $useFieldAlias Use the localized field-alias instead
     *                                                of the actual name (default false)
     * @param bool                     $formatOutput  Set whether to format the output (default true)
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function exportCondition(SearchConditionInterface $condition, $useFieldAlias = false, $formatOutput = true)
    {
        $labelResolver = $this->labelResolver;

        if (!$useFieldAlias && $this->labelResolver instanceof NoopLabelResolver) {
            $this->labelResolver = new NoopLabelResolver();
        }

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

        // Restore original resolver.
        $this->labelResolver = $labelResolver;

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

                $fieldLabel = $this->labelResolver->resolveFieldLabel($fieldSet, $name);
                $fieldNode = $this->document->createElement('field');
                $fieldNode->setAttribute('name', $fieldLabel);

                $this->exportValuesToNode($values, $fieldNode);
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
    private function exportValuesToNode(ValuesBag $valuesBag, \DOMNode $parent)
    {
        if ($valuesBag->hasSingleValues()) {
            $valuesNode = $this->document->createElement('single-values');

            foreach ($valuesBag->getSingleValues() as $value) {
                $element = $this->document->createElement('value');
                $element->appendChild(
                    $this->document->createTextNode(
                        $value->getViewValue()
                    )
                );

                $valuesNode->appendChild($element);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->hasExcludedValues()) {
            $valuesNode = $this->document->createElement('excluded-values');

            foreach ($valuesBag->getExcludedValues() as $value) {
                $element = $this->document->createElement('value');
                $element->appendChild(
                    $this->document->createTextNode(
                        $value->getViewValue()
                    )
                );

                $valuesNode->appendChild($element);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->hasRanges()) {
            $valuesNode = $this->document->createElement('ranges');

            foreach ($valuesBag->getRanges() as $value) {
                $this->exportRangeValueToNode($valuesNode, $value);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->hasExcludedRanges()) {
            $valuesNode = $this->document->createElement('excluded-ranges');

            foreach ($valuesBag->getExcludedRanges() as $value) {
                $this->exportRangeValueToNode($valuesNode, $value);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->hasComparisons()) {
            $valuesNode = $this->document->createElement('comparisons');

            foreach ($valuesBag->getComparisons() as $value) {
                $element = $this->document->createElement('compare');
                $element->setAttribute('operator', $value->getOperator());
                $element->appendChild(
                    $this->document->createTextNode($value->getViewValue())
                );

                $valuesNode->appendChild($element);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->hasPatternMatchers()) {
            $valuesNode = $this->document->createElement('pattern-matchers');

            foreach ($valuesBag->getPatternMatchers() as $value) {
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
    private function exportRangeValueToNode(\DOMNode $parent, Range $range)
    {
        $rangeNode = $this->document->createElement('range');

        $element = $this->document->createElement('lower');
        $element->appendChild(
            $this->document->createTextNode($range->getViewLower())
        );

        if (!$range->isLowerInclusive()) {
            $element->setAttribute('inclusive', 'false');
        }

        $rangeNode->appendChild($element);

        $element = $this->document->createElement('upper');
        $element->appendChild(
            $this->document->createTextNode($range->getViewUpper())
        );

        if (!$range->isUpperInclusive()) {
            $element->setAttribute('inclusive', 'false');
        }

        $rangeNode->appendChild($element);

        // Add to parent (<ranges> Node).
        $parent->appendChild($rangeNode);
    }
}
