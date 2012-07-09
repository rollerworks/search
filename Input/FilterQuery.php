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
use Rollerworks\Bundle\RecordFilterBundle\Type\ValueMatcherInterface;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

/**
 * FilterQuery.
 *
 * Accept input in an FilterQuery format.
 *
 * Every filter is an: name=values;
 *
 * The field name must follow this regex convention: [a-z][a-z_0-9]*.
 * Unicode characters and numbers are accepted.
 *
 * If the value contains an ';' or '()', the whole value must be quoted (with double quotes).
 * If the value contains an special character, like the range symbol 'that' value-part must be quoted.
 * Like: "value-1"-value2
 *
 * Single values containing no special characters, can be quoted. But this is not required.
 *
 * If you want to use OR-groups place the name=value; between round-bars '()'
 * and separate them by one comma ','.
 *
 * Important: the field=value pairs must 'always end' with an ';', especially when in an OR-group.
 * The parser will not accept an input like: (field=value),(field2=value)
 *
 * Comma at the end is always ignored.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterQuery extends AbstractInput
{
    /**
     * State of the parser.
     *
     * @var boolean
     */
    protected $isParsed = false;

    /**
     * Filter-input (as-is).
     *
     * @var string
     */
    protected $query = null;

    /**
     * Section where the filter-input can be used.
     *
     * @var string
     */
    protected $sections = array();

    /**
     * @var MessageBag
     */
    protected $messages;

    /**
     * Set the resolving of an field label to name, using the translator beginning with prefix.
     *
     * Example: product.labels.[label]
     *
     * For this to work properly a Translator must be registered with setTranslator()
     *
     * @param string $pathPrefix This prefix is added before every search, like filters.labels.
     * @param string $domain     Default is filter
     *
     * @return FilterQuery
     *
     * @throws \InvalidArgumentException
     */
    public function setLabelToFieldByTranslator($pathPrefix, $domain = 'filter')
    {
        if (!is_string($pathPrefix) || empty($pathPrefix)) {
            throw new \InvalidArgumentException('Prefix must be an string and can not be empty.');
        }

        if (!is_string($domain) || empty($domain)) {
            throw new \InvalidArgumentException('Domain must be an string and can not be empty.');
        }

        $this->aliasTranslatorPrefix = $pathPrefix;
        $this->aliasTranslatorDomain = $domain;

        return $this;
    }

    /**
     * Set the resolving of an field label to name.
     *
     * Existing ones are overwritten.
     *
     * @param string       $fieldName Original field-name
     * @param string|array $label
     *
     * @return self
     */
    public function setLabelToField($fieldName, $label)
    {
        if (is_array($label)) {
            foreach ($label as $fieldLabel) {
                $this->labelsResolve[$fieldLabel] = $fieldName;
            }
        } elseif (is_string($label)) {
            $this->labelsResolve[$label] = $fieldName;
        }

        return $this;
    }

    /**
     * Set the filter input.
     *
     * @param string $input
     *
     * @return self
     */
    public function setInput($input)
    {
        $this->isParsed = false;
        $this->query = trim($input);

        $this->messages = new MessageBag($this->translator);

        return $this;
    }

    /**
     * Get the filter-input.
     *
     * @return string
     */
    public function getQueryString()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups()
    {
        if (false === $this->isParsed) {
            try {
                $this->parseQuery();
            } catch (ValidationException $e) {
                $this->messages->addError($e->getMessage(), $e->getParams());

                return false;
            }
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
     * Parse the input-filter that is set.
     */
    protected function parseQuery()
    {
        // Look for the usage of OR-group(s)
        // There is 'minor problem', the field-value pairs must end with an ;, or else the parsing is ignored.
        // Various solutions have been tried, but did not work...
        // But its still better, then to 'always' need to escape the grouping parentheses to use them as literals.
        if ('(' === mb_substr($this->query, 0, 1)) {
            if (preg_match_all('/\(((?:\s*(?:\p{L}[\p{L}\p{N}_]*)\s*=(?:(?:\s*(?:"(?:(?:[^"]+|"")+)"|[^;,]+)\s*,*)*);?\s*)*)\),?/us', $this->query, $groups)) {
                $groupsCount = count($groups[0]);

                for ($i = 0; $i < $groupsCount; $i++) {
                    $this->groups[$i] = $this->parseFilterPairs($groups[1][$i], $i);
                }
            }
        } else {
            $this->groups[0] = $this->parseFilterPairs($this->query, 0);
        }

        $this->isParsed = true;
    }

    /**
     * Parse the field=value pairs from the input.
     *
     * @param string  $input
     * @param integer $group
     *
     * @return array
     *
     * @throws ValidationException
     */
    protected function parseFilterPairs($input, $group)
    {
        $filterPairs = array();

        if (preg_match_all('/(\p{L}[\p{L}\p{N}_-]*)\s*=((?:\s*(?:"(?:(?:[^"]+|"")+)"|[^;,]+)\s*,*)*);?/us', $input, $filterPairMatches)) {
            $filtersCount = count($filterPairMatches[0]);

            for ($i = 0; $i < $filtersCount; $i++) {
                $label = mb_strtolower($filterPairMatches[1][$i]);
                $name  = $this->getFieldNameByLabel($label);
                $value = trim($filterPairMatches[2][$i]);

                if (!$this->fieldsSet->has($name) || strlen($value) < 1) {
                    continue;
                }

                if (isset($filterPairs[$name])) {
                    $filterPairs[$name] .= ',' . $value;
                } else {
                    $filterPairs[$name] = $value;
                }
            }
        }

        foreach ($this->fieldsSet->all() as $name => $filterConfig) {
            /** @var FilterField $filterConfig */

            if (empty($filterPairs[$name])) {
                if (true === $filterConfig->isRequired()) {
                    throw new ValidationException('required', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group+1));
                }

                continue;
            }

            $type = null;

            if ($filterConfig->getType() instanceof ValueMatcherInterface) {
                $type = $filterConfig->getType();
            }

            $filterPairs[$name] = $this->valuesToBag($filterPairs[$name], $filterConfig, $this->parseValuesList($filterPairs[$name], $type), $group);
        }

        return $filterPairs;
    }

    /**
     * Parses the values list and returns them as an array.
     *
     * @param string                     $values
     * @param ValueMatcherInterface|null $valueMatcher
     *
     * @return array
     */
    protected function parseValuesList($values, ValueMatcherInterface $valueMatcher = null)
    {
        $valueMatcherRegex = '';

        if (null !== $valueMatcher) {
            $regex = $valueMatcher->getMatcherRegex();
            $valueMatcherRegex = '|' . $regex . '-' . $regex . '|(?:>=|<=|<>|[<>!])?' . $regex;
        }

        if (preg_match_all('#\s*("(?:(?:[^"]+|"")+)"' . $valueMatcherRegex . '|[^,]+)\s*(,\s*|$)#ius', $values, $filterValues)) {
            return $filterValues[1];
        } else {
            return array();
        }
    }

    /**
     * Perform the formatting of the given values (per group).
     *
     * @param string       $originalInput
     * @param FilterField  $filterConfig
     * @param array|string $values
     * @param              $group
     *
     * @return FilterValuesBag
     *
     * @throws \Rollerworks\Bundle\RecordFilterBundle\Exception\ValidationException
     */
    protected function valuesToBag($originalInput, FilterField $filterConfig, array $values, $group)
    {
        $ranges = $excludedRanges = $excludesValues = $compares = $singleValues = array();
        $valueMatcherRegex = '';

        if ($filterConfig->hasType() && ($filterConfig->getType() instanceof ValueMatcherInterface)) {
            $valueMatcherRegex = '|' . $filterConfig->getType()->getMatcherRegex();
        }

        $valueIndex = -1;

        foreach ($values as $valueIndex => $currentValue) {
            $value = null;

            // Comparison
            if (preg_match('#^(>=|<=|<>|[<>])("(?:(?:[^"]+|"")+)"'.$valueMatcherRegex.'|[^\h]+)$#us', $currentValue, $comparisonValue)) {
                if (!$filterConfig->acceptCompares()) {
                    throw new ValidationException('no_compare_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group+1));
                }

                $compares[ $valueIndex ] = new Compare(self::fixQuotes($comparisonValue[2]), $comparisonValue[1]);
            }
            // Ranges and single (exclude)
            else {
                $isExclude = false;

                if ('!' === mb_substr(trim($currentValue), 0, 1)) {
                    $isExclude    = true;
                    $currentValue = mb_substr(ltrim($currentValue), 1);
                }

                if (false !== strpos($currentValue, '-' )) {
                    // Value starts with an quote, check if its range and not an quoted value with a '-' in it.
                    if ('"' === mb_substr(trim($currentValue), 0, 1)) {
                        // Both quoted
                        if (preg_match('#^("(?:(?:[^"]+|"")+)")-("(?:(?:[^"]+|"")+)")$#s', $currentValue, $rangeValue)) {
                            $value = new Range(self::fixQuotes($rangeValue[1]), self::fixQuotes($rangeValue[2]));
                        }
                        // Only first quoted
                        elseif (preg_match('#^("(?:(?:[^"]+|"")+)")-([^\s]+)$#s', $currentValue, $rangeValue)) {
                            $value = new Range(self::fixQuotes($rangeValue[1]), $rangeValue[2]);
                        }
                    }
                    // By value matcher
                    elseif (!empty($valueMatcherRegex)) {
                        if (preg_match('#^('.substr($valueMatcherRegex, 1).')-('.substr($valueMatcherRegex, 1).')$#uis', $currentValue, $rangeValue)) {
                            $value = new Range($rangeValue[1], $rangeValue[2]);
                        }
                        // Remember to check for an positive singe-value match
                        elseif (!preg_match('#^('.substr($valueMatcherRegex, 1).')$#uis', $currentValue, $rangeValue) && preg_match('#^([^-]+)-([^\s]+)$#s', $currentValue, $rangeValue)) {
                            $value = new Range($rangeValue[1], $rangeValue[2]);
                        }
                    }
                    // None quoted/only right quoted
                    elseif (preg_match('#^([^-]+)-([^\s]+)$#s', $currentValue, $rangeValue)) {
                        $value = new Range($rangeValue[1], self::fixQuotes($rangeValue[2]));
                    }
                }

                if (null !== $value) {
                    if (!$filterConfig->acceptRanges()) {
                        throw new ValidationException('no_range_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group+1));
                    }

                    if ($isExclude) {
                        $excludedRanges[$valueIndex] = $value;
                    } else {
                        $ranges[$valueIndex] = $value;
                    }
                }
                // Single (exclude) value
                else {
                    $value = new SingleValue(self::fixQuotes($currentValue));

                    if ($isExclude) {
                        $excludesValues[$valueIndex] = $value;
                    } else {
                        $singleValues[$valueIndex] = $value;
                    }
                }
            }
        }

        return new FilterValuesBag($filterConfig->getLabel(), $originalInput, $singleValues, $excludesValues, $ranges, $compares, $excludedRanges, $valueIndex);
    }

    /**
     * Get the corresponding fieldName by label.
     *
     * @param string $label
     *
     * @return string
     *
     * @throws \RuntimeException When no translator available
     */
    protected function getFieldNameByLabel($label)
    {
        if (null !== $this->aliasTranslatorPrefix && empty($this->translator)) {
            throw new \RuntimeException('No translator registered.');
        }

        $fieldName = $label;

        if (isset($this->labelsResolve[$label])) {
            $fieldName = $this->labelsResolve[$label];
        } elseif (null !== $this->aliasTranslatorPrefix) {
            $fieldName = $this->translator->trans($this->aliasTranslatorPrefix . $label, array(), $this->aliasTranslatorDomain);

            if ($this->aliasTranslatorPrefix . $label === $fieldName) {
                $fieldName = $label;
            }
        }

        return $fieldName;
    }

    /**
     * Remove and normalise quoted-values.
     *
     * @param string $input
     *
     * @return string
     */
    protected static function fixQuotes($input)
    {
        $input = trim($input);

        if ('"' === mb_substr($input, 0, 1)) {
            $input = mb_substr($input, 1, -1);
            $input = str_replace('""', '"', $input);
        }

        return $input;
    }
}
