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
     * Process the input and returns the result.
     *
     * @param string $input
     *
     * @return null|ValuesGroup Returns null on empty input
     */
    public function process($input)
    {
        $document = $this->parseXml($input, __DIR__ . '/schema/dic/input/xml-input-1.0.xsd');

        $valuesGroup = new ValuesGroup();
        if (isset($document['logical']) && 'OR' === strtoupper((string) $document['logical'])) {
            $valuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
        }

        $this->processGroup($document, $valuesGroup, 0, 0, true);

        return $valuesGroup;
    }

    /**
     * @param \SimpleXMLElement $values
     * @param ValuesGroup       $valuesGroup
     * @param integer           $groupIdx
     * @param integer           $level
     * @param boolean           $isRoot
     *
     * @throws FieldRequiredException
     * @throws InputProcessorException
     */
    private function processGroup(\SimpleXMLElement $values, ValuesGroup $valuesGroup, $groupIdx = 0, $level = 0, $isRoot = false)
    {
        $this->validateGroupNesting($groupIdx, $level);
        $allFields = $this->fieldSet->all();

        if (!isset($values->fields) && !isset($values->groups)) {
            throw new InputProcessorException(sprintf('Empty group found in group %d at nesting level %d', $groupIdx, $level));
        }

        if (isset($values->fields)) {
            foreach ($values->fields->children() as $element) {
                /** @var \SimpleXMLElement $element */
                $fieldName = $this->getFieldName((string) $element['name']);
                $filterConfig = $this->fieldSet->get($fieldName);

                if ($valuesGroup->hasField($fieldName)) {
                    $this->valuesToBag($filterConfig, $element, $fieldName, $groupIdx, $level, $valuesGroup->getField($fieldName));
                } else {
                    $valuesGroup->addField($fieldName, $this->valuesToBag($filterConfig, $element, $fieldName, $groupIdx, $level));
                }

                unset($allFields[$fieldName]);
            }
        }

        // Now run trough all the remaining fields and look if there are required
        // Fields that were set without values have already been checked by valuesToBag()
        foreach ($allFields as $fieldName => $filterConfig) {
            if ($filterConfig->isRequired()) {
                throw new FieldRequiredException($fieldName, $groupIdx, $level);
            }
        }

        if (isset($values->groups)) {
            $this->validateGroupsCount($this->maxGroups, $values->groups->children()->count(), $level);

            $index = 0;
            foreach ($values->groups->children() as $element) {
                $subValuesGroup = new ValuesGroup();

                if (isset($element['logical']) && 'OR' === strtoupper($element['logical'])) {
                    $subValuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
                }

                $this->processGroup($element, $subValuesGroup, $index, ($isRoot ? 0 : $level+1));
                $valuesGroup->addGroup($subValuesGroup);
                $index++;
            }
        }
    }

    /**
     * Converts the values list to an FilterValuesBag object.
     *
     * @param FieldConfigInterface $fieldConfig
     * @param \SimpleXMLElement    $values
     * @param string               $fieldName
     * @param integer              $groupIdx
     * @param integer              $level
     * @param ValuesBag|null       $valuesBag
     *
     * @return ValuesBag
     *
     * @throws FieldRequiredException
     * @throws ValuesOverflowException
     */
    private function valuesToBag(FieldConfigInterface $fieldConfig, \SimpleXMLElement $values, $fieldName, $groupIdx, $level = 0, ValuesBag $valuesBag = null)
    {
        if (isset($values->comparisons)) {
            $this->assertAcceptsType('comparison', $fieldName);
        }

        if (isset($values->ranges) || isset($values->{'excluded-ranges'})) {
            $this->assertAcceptsType('range', $fieldName);
        }

        if (isset($values->{'pattern-matchers'})) {
            $this->assertAcceptsType('pattern-match', $fieldName);
        }

        if (!$valuesBag) {
            $valuesBag = new ValuesBag();
        }

        $count = $valuesBag->count();

        if (isset($values->{'single-values'})) {
            foreach ($values->{'single-values'}->children() as $value) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addSingleValue(new SingleValue((string) $value));
            }
        }

        if (isset($values->{'excluded-values'})) {
            foreach ($values->{'excluded-values'}->children() as $value) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addExcludedValue(new SingleValue((string) $value));
            }
        }

        if (isset($values->comparisons)) {
            foreach ($values->comparisons->children() as $comparison) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addComparison(new Compare((string) $comparison, (string) $comparison['operator']));
            }
        }

        if (isset($values->ranges)) {
            foreach ($values->ranges->children() as $range) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addRange(
                    new Range((string) $range->lower, (string) $range->upper, 'false' !== strtolower($range->lower['inclusive']), 'false' !== strtolower($range->upper['inclusive']))
                );
            }
        }

        if (isset($values->{'excluded-ranges'})) {
            foreach ($values->{'excluded-ranges'}->children() as $range) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addExcludedRange(
                    new Range((string) $range->lower, (string) $range->upper, 'false' !== strtolower($range->lower['inclusive']), 'false' !== strtolower($range->upper['inclusive']))
                );
            }
        }

        if (isset($values->{'pattern-matchers'})) {
            $this->assertAcceptsType('pattern-match', $fieldName);

            foreach ($values->{'pattern-matchers'}->children() as $patternMatch) {
                if ($count > $this->maxValues) {
                    throw new ValuesOverflowException($fieldName, $this->maxValues, $count, $groupIdx, $level);
                }
                $count++;

                $valuesBag->addPatternMatch(
                    new PatternMatch((string) $patternMatch, (string) $patternMatch['type'], 'true' === strtolower($patternMatch['case-insensitive']))
                );
            }
        }

        if (0 === $count && $fieldConfig->isRequired()) {
            throw new FieldRequiredException($fieldName, $groupIdx, $level);
        }

        return $valuesBag;
    }

    /**
     * Loads an XML file.
     *
     * @param string $content                      An XML file path
     * @param string|callable $schemaOrCallable An XSD schema file path or callable
     *
     * @author Martin Hasoň <martin.hason@gmail.com>
     *
     * @return \SimpleXMLElement
     *
     * @throws \InvalidArgumentException When loading of XML file returns error
     */
    private static function parseXml($content, $schemaOrCallable = null)
    {
        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->validateOnParse = true;
        if (!$dom->loadXML($content, LIBXML_NONET | (defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
            libxml_disable_entity_loader($disableEntities);

            throw new \InvalidArgumentException(implode("\n", static::getXmlErrors($internalErrors)));
        }

        $dom->normalizeDocument();

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        foreach ($dom->childNodes as $child) {
            if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                throw new \InvalidArgumentException('Document types are not allowed.');
            }
        }

        if (null !== $schemaOrCallable) {
            $internalErrors = libxml_use_internal_errors(true);
            libxml_clear_errors();

            $e = null;
            if (is_callable($schemaOrCallable)) {
                try {
                    $valid = call_user_func($schemaOrCallable, $dom, $internalErrors);
                } catch (\Exception $e) {
                    $valid = false;
                }
            } elseif (!is_array($schemaOrCallable) && is_file((string) $schemaOrCallable)) {
                $valid = @$dom->schemaValidate(str_replace('\\', '/', $schemaOrCallable));
            } else {
                libxml_use_internal_errors($internalErrors);

                throw new \InvalidArgumentException('The schemaOrCallable argument has to be a valid path to XSD file or callable.');
            }

            if (!$valid) {
                $messages = static::getXmlErrors($internalErrors);
                if (empty($messages)) {
                    $messages = array('The XML file is not valid.');
                }
                throw new \InvalidArgumentException(implode("\n", $messages), 0, $e);
            }

            libxml_use_internal_errors($internalErrors);
        }

        return simplexml_import_dom($dom);
    }

    /**
     * @param boolean $internalErrors
     *
     * @return array
     */
    private static function getXmlErrors($internalErrors)
    {
        $errors = array();
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf('[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ? $error->file : 'n/a',
                $error->line,
                $error->column
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $errors;
    }
}
