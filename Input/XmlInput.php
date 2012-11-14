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
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

/**
 * XmlInput - accepts filtering preference as an XML document.
 *
 * See the XSD in schema/dic/input/xml-input-1.0.xsd for more information
 * about the schema.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
class XmlInput extends AbstractInput
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

        $document = $this->parseXml($this->input);

        try {
            foreach ($document->groups->children() as $i => $group) {
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
            $this->hash = md5($this->input);
        }

        return $this->hash;
    }

    /**
     * @param \SimpleXMLElement $properties
     * @param integer           $groupId
     *
     * @throws ValidationException
     */
    protected function processGroup(\SimpleXMLElement $properties, $groupId)
    {
        $filterPairs = array();
        foreach ($this->fieldsSet->all() as $name => $filterConfig) {
            $name = (function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name));
            $values = $properties->xpath("field[@name='$name']");

            if (empty($values)) {
                if (true === $filterConfig->isRequired()) {
                    throw new ValidationException('required', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $groupId));
                }

                continue;
            }

            $filterPairs[$name] = $this->valuesToBag($filterConfig, $values[0], $groupId);
        }

        $this->groups[] = $filterPairs;
    }

    /**
     * Converts the values list to an FilterValuesBag object.
     *
     * @param FilterField $filterConfig
     * @param mixed       $values
     * @param             $group
     *
     * @return FilterValuesBag
     *
     * @throws ValidationException
     */
    protected function valuesToBag(FilterField $filterConfig, $values, $group)
    {
        $ranges = $excludedRanges = $excludesValues = $compares = $singleValues = array();
        $hasValues = false;

        if (isset($values->compares) && $values->compares->count() && !$filterConfig->acceptCompares()) {
            throw new ValidationException('no_compare_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
        }

        if (isset($values->ranges) && $values->ranges->count() && !$filterConfig->acceptRanges()) {
            throw new ValidationException('no_range_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
        }

        if (isset($values->{'excluded-ranges'}) && $values->{'excluded-ranges'}->count() && !$filterConfig->acceptRanges()) {
            throw new ValidationException('no_range_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
        }

        if (isset($values->{'single-values'})) {
            foreach ($values->{'single-values'}->children() as $value) {
                $singleValues[] = new SingleValue((string) $value);
                $hasValues = true;
            }
        }

        if (isset($values->{'excluded-values'})) {
            foreach ($values->{'excluded-values'}->children() as $value) {
                $excludesValues[] = new SingleValue((string) $value);
                $hasValues = true;
            }
        }

        if (isset($values->compares)) {
            foreach ($values->compares->children() as $comparison) {
                $compares[] = new Compare((string) $comparison, (string) $comparison['opr']);
                $hasValues = true;
            }
        }

        if (isset($values->ranges)) {
            foreach ($values->ranges->children() as $range) {
                $ranges[] = new Range((string) $range->lower, (string) $range->higher);
                $hasValues = true;
            }
        }

        if (isset($values->{'excluded-ranges'})) {
            foreach ($values->{'excluded-ranges'}->children() as $range) {
                $excludedRanges[] = new Range((string) $range->lower, (string) $range->higher);
                $hasValues = true;
            }
        }

        if (!$hasValues && true === $filterConfig->isRequired()) {
            throw new ValidationException('required', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group));
        }

        return new FilterValuesBag($filterConfig->getLabel(), '', $singleValues, $excludesValues, $ranges, $compares, $excludedRanges);
    }

    /**
     * Parse an XML Document.
     *
     * @author Fabien Potencier <fabien@symfony.com>
     *
     * @param string $content
     *
     * @return \SimpleXMLElement
     *
     * @throws \InvalidArgumentException
     */
    protected function parseXml($content)
    {
        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->validateOnParse = true;
        if (!$dom->loadXML($content, LIBXML_NONET | (defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
            libxml_disable_entity_loader($disableEntities);

            throw new \InvalidArgumentException(implode("\n", $this->getXmlErrors($internalErrors)));
        }

        libxml_disable_entity_loader($disableEntities);

        if (!$dom->schemaValidate(__DIR__.'/schema/dic/input/xml-input-1.0.xsd')) {
            throw new \InvalidArgumentException(implode("\n", $this->getXmlErrors($internalErrors)));
        }

        $dom->normalizeDocument();

        libxml_use_internal_errors($internalErrors);

        foreach ($dom->childNodes as $child) {
            if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                throw new \InvalidArgumentException('Document types are not allowed.');
            }
        }

        return simplexml_import_dom($dom);
    }

    /**
     * @param boolean $internalErrors
     *
     * @return array
     */
    protected function getXmlErrors($internalErrors)
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
