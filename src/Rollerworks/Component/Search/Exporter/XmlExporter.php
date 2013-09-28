<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Exporter;

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
     * @param boolean                  $useFieldAlias Use the localized field-alias instead of the actual name (default false)
     * @param boolean                  $formatOutput  Set whether to format the output (default true)
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function exportCondition(SearchConditionInterface $condition, $useFieldAlias = false, $formatOutput = true)
    {
        if ($useFieldAlias && null === $this->labelResolver) {
            throw new \RuntimeException('Unable resolve field-name to alias because no labelResolver is configured.');
        }

        $this->document = new \DOMDocument('1.0', 'utf-8');
        $this->document->formatOutput = $formatOutput;

        $searchRoot = $this->document->createElementNS('http://rollerworks.github.io/search/schema/dic/search', 'search');
        $searchRoot->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $searchRoot->setAttribute('xsi:schemaLocation', 'http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd');
        $searchRoot->setAttribute('logical', $condition->getValuesGroup()->getGroupLogical());

        $this->exportGroupNode($searchRoot, $condition->getValuesGroup(), $condition->getFieldSet(), $useFieldAlias);

        $this->document->appendChild($searchRoot);
        $xml = $this->document->saveXML();
        $this->document = null;

        return $xml;
    }

    /**
     * @ignore
     */
    protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $useFieldAlias = false, $isRoot = false)
    {
        // noop
    }

    /**
     * @param \DOMNode    $parent
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     * @param boolean     $useFieldAlias
     */
    protected function exportGroupNode(\DOMNode $parent, ValuesGroup $valuesGroup, FieldSet $fieldSet, $useFieldAlias = false)
    {
        $fields = $valuesGroup->getFields();

        if (!empty($fields)) {
            $fieldsNode = $this->document->createElement('fields');

            foreach ($fields as $name => $values) {
                if (!$values->count()) {
                    continue;
                }

                $fieldLabel = ($useFieldAlias ? $this->labelResolver->resolveFieldLabel($fieldSet, $name) : $name);
                $fieldNode = $this->document->createElement('field');
                $fieldNode->setAttribute('name', $fieldLabel);
                $this->exportValuesToNode($fieldNode, $values);
                $fieldsNode->appendChild($fieldNode);
            }

            if ($fieldsNode->hasChildNodes()) {
                $parent->appendChild($fieldsNode);
            }
        }

        if ($valuesGroup->hasGroups()) {
            $groupsNode = $this->document->createElement('groups');

            foreach ($valuesGroup->getGroups() as $group) {
                $groupNode = $this->document->createElement('group');
                $groupNode->setAttribute('logical', $group->getGroupLogical());

                $this->exportGroupNode($groupNode, $group, $fieldSet, $useFieldAlias, false);

                if ($groupNode->hasChildNodes()) {
                    $groupsNode->appendChild($groupNode);
                }
            }

            if ($groupsNode->hasChildNodes()) {
                $parent->appendChild($groupsNode);
            }
        }
    }

    /**
     * @param \DOMNode    $parent
     * @param ValuesBag $valuesBag
     *
     * @return \DOMNode
     */
    protected function exportValuesToNode(\DOMNode $parent, ValuesBag $valuesBag)
    {
        if ($valuesBag->hasSingleValues()) {
            $valuesNode = $this->document->createElement('single-values');

            foreach ($valuesBag->getSingleValues() as $value) {
                $element = $this->document->createElement('value');
                $text = $this->document->createTextNode($value->getViewValue());
                $element->appendChild($text);

                $valuesNode->appendChild($element);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->hasExcludedValues()) {
            $valuesNode = $this->document->createElement('excluded-values');

            foreach ($valuesBag->getExcludedValues() as $value) {
                $element = $this->document->createElement('value');
                $text = $this->document->createTextNode($value->getViewValue());
                $element->appendChild($text);

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
                $text = $this->document->createTextNode($value->getViewValue());
                $element->appendChild($text);

                $valuesNode->appendChild($element);
            }

            $parent->appendChild($valuesNode);
        }

        if ($valuesBag->hasPatternMatchers()) {
            $valuesNode = $this->document->createElement('pattern-matchers');

            foreach ($valuesBag->getPatternMatchers() as $value) {
                $element = $this->document->createElement('pattern-matcher');
                $element->setAttribute('type', $this->getPatternMatchType($value));
                $element->setAttribute('case-insensitive', $value->isCaseInsensitive() ? 'true' : 'false');
                $text = $this->document->createTextNode($value->getViewValue());
                $element->appendChild($text);

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
    protected function exportRangeValueToNode(\DOMNode $parent, Range $range)
    {
        $rangeNode = $this->document->createElement('range');

        $element = $this->document->createElement('lower');
        $text = $this->document->createTextNode($range->getViewLower());
        $element->appendChild($text);

        if (!$range->isLowerInclusive()) {
            $element->setAttribute('inclusive', 'false');
        }

        $rangeNode->appendChild($element);

        $element = $this->document->createElement('upper');
        $text = $this->document->createTextNode($range->getViewUpper());
        $element->appendChild($text);

        if (!$range->isUpperInclusive()) {
            $element->setAttribute('inclusive', 'false');
        }

        $rangeNode->appendChild($element);

        // now add to parent
        $parent->appendChild($rangeNode);
    }
}
