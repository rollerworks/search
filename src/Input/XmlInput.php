<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Util\XmlUtil;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * XmlInput processes input provided as an XML document.
 *
 * See the XSD in schema/dic/input/xml-input-1.0.xsd for more information
 * about the schema.
 *
 * Caution: Duplicate field names overwrite (they are not merged).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class XmlInput extends AbstractInput
{
    /**
     * @var FieldValuesFactory|null
     */
    private $valuesFactory;

    /**
     * {@inheritdoc}
     */
    public function process(ProcessorConfig $config, $input): SearchCondition
    {
        if (!is_string($input)) {
            throw new UnexpectedTypeException($input, 'string');
        }

        $input = trim($input);

        if (empty($input)) {
            return new SearchCondition($config->getFieldSet(), new ValuesGroup());
        }

        $condition = null;
        $this->errors = new ErrorList();
        $this->config = $config;
        $this->level = 0;

        $this->valuesFactory = new FieldValuesFactory($this->errors, $this->validator, $this->config->getMaxValues());

        try {
            $document = simplexml_import_dom(XmlUtil::parseXml($input, __DIR__.'/schema/dic/input/xml-input-2.0.xsd'));

            $valuesGroup = new ValuesGroup((string) ($document['logical'] ?? ValuesGroup::GROUP_LOGICAL_AND));
            $this->processGroup($document, $valuesGroup, '/search');

            $condition = new SearchCondition($config->getFieldSet(), $valuesGroup);

            $this->assertLevel0();
        } catch (InputProcessorException $e) {
            $this->errors[] = $e->toErrorMessageObj();
        } catch (\InvalidArgumentException $e) {
            $this->errors[] = ConditionErrorMessage::rawMessage('', $e->getMessage(), $e);
        } finally {
            $this->valuesFactory = null;
        }

        if (count($this->errors)) {
            $errors = $this->errors->getArrayCopy();

            throw new InvalidSearchConditionException($errors);
        }

        return $condition;
    }

    private function processGroup(\SimpleXMLElement $values, ValuesGroup $valuesGroup, string $path)
    {
        $this->validateGroupNesting($path);

        if (isset($values->fields)) {
            $this->processFields($values, $valuesGroup, "$path/fields");
        }

        if (isset($values->groups)) {
            ++$this->level;
            $this->processGroups($values, $valuesGroup, "$path/groups");
            --$this->level;
        }
    }

    private function processGroups(\SimpleXMLElement $values, ValuesGroup $valuesGroup, string $path)
    {
        $this->validateGroupsCount($values->groups->children()->count(), $path);

        $index = 1;

        foreach ($values->groups->children() as $element) {
            $subValuesGroup = new ValuesGroup((string) ($element['logical'] ?? ValuesGroup::GROUP_LOGICAL_AND));
            $this->processGroup($element, $subValuesGroup, "$path/group[$index]");
            $valuesGroup->addGroup($subValuesGroup);

            ++$index;
        }
    }

    private function processFields(\SimpleXMLElement $values, ValuesGroup $valuesGroup, string $path)
    {
        // Though merging is not supported it's not illegal to overwrite an already defined field.
        // But that will point to a later element position.
        $namePos = [];

        /** @var \SimpleXMLElement $element */
        foreach ($values->fields->children() as $element) {
            $name = (string) $element['name'];
            $namePos[$name] = ($namePos[$name] ?? 0) + 1;

            $fieldConfig = $this->config->getFieldSet()->get($name);

            $valuesGroup->addField(
                $name,
                $this->valuesToBag($fieldConfig, $element, new ValuesBag(), "$path/field[@name='$name'][{$namePos[$name]}]")
            );
        }
    }

    private function valuesToBag(FieldConfig $field, \SimpleXMLElement $values, ValuesBag $valuesBag, string $path)
    {
        $this->valuesFactory->initContext($field, $valuesBag, $path.'/');

        if (isset($values->{'simple-values'})) {
            $index = 1;

            foreach ($values->{'simple-values'}->children() as $value) {
                $this->valuesFactory->addSimpleValue((string) $value, "simple-values/value[$index]");
                ++$index;
            }
        }

        if (isset($values->{'excluded-simple-values'})) {
            $index = 1;

            foreach ($values->{'excluded-simple-values'}->children() as $value) {
                $this->valuesFactory->addExcludedSimpleValue((string) $value, "excluded-simple-values/value[$index]");
                ++$index;
            }
        }

        if (isset($values->comparisons)) {
            $index = 1;

            foreach ($values->comparisons->children() as $comparison) {
                $this->valuesFactory->addComparisonValue(
                    (string) $comparison['operator'],
                    (string) $comparison,
                    ["comparisons/compare[$index]", '[@operator]', '']
                );
                ++$index;
            }
        }

        if (isset($values->ranges)) {
            $index = 1;

            foreach ($values->ranges->children() as $range) {
                $this->processRange($range, "ranges/range[$index]");
                ++$index;
            }
        }

        if (isset($values->{'excluded-ranges'})) {
            $index = 1;

            foreach ($values->{'excluded-ranges'}->children() as $range) {
                $this->processRange($range, "excluded-ranges/range[$index]", true);
                ++$index;
            }
        }

        if (isset($values->{'pattern-matchers'})) {
            $index = 1;

            foreach ($values->{'pattern-matchers'}->children() as $patternMatch) {
                $this->valuesFactory->addPatterMatch(
                    (string) $patternMatch['type'],
                    (string) $patternMatch,
                    'true' === strtolower((string) $patternMatch['case-insensitive']),
                    ["pattern-matchers/pattern-matcher[$index]", '', '[@type]']
                );
                ++$index;
            }
        }

        return $valuesBag;
    }

    private function processRange($range, string $path, bool $negative = false)
    {
        $lowerInclusive = 'false' !== strtolower((string) $range->lower['inclusive']);
        $upperInclusive = 'false' !== strtolower((string) $range->upper['inclusive']);

        $lowerBound = (string) $range->lower;
        $upperBound = (string) $range->upper;

        if ($negative) {
            $this->valuesFactory->addExcludedRange($lowerBound, $upperBound, $lowerInclusive, $upperInclusive, [$path, '/lower', '/upper']);
        } else {
            $this->valuesFactory->addRange($lowerBound, $upperBound, $lowerInclusive, $upperInclusive, [$path, '/lower', '/upper']);
        }
    }
}
