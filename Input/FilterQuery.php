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
 * FilterQuery - accepts input in the FilterQuery format.
 *
 * Every filter is a 'name=values;' pair
 *
 * The field name must follow the '[a-z][a-z_0-9]*' regex convention.
 * Unicode characters and numbers are accepted.
 *
 * If the value contains a ';' or '()', the whole value must be quoted (with double quotes).
 * If the value contains a special character, like the range symbol 'that' value-part must be quoted.
 * Like: "value-1"-value2
 *
 * Single values containing no special characters, can be quoted. But this is not required.
 *
 * If you want to use OR-groups place the 'name=value;' pairs between round-bars '()'
 * and separate them by one comma ','.
 *
 * Like: (field1=value1;),(field1=value2;)
 *
 * Important: the 'field=value' pairs must *always* end with an ';', especially when used in an OR-group.
 * The parser does not support an input like: (field=value),(field2=value)
 *
 * A comma at the end is always ignored.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterQuery extends AbstractInput
{
    /**
     * Current state of the parser.
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
     * @var string
     */
    protected $hash;

    /**
     * @var MessageBag
     */
    protected $messages;

    /**
     * Sets the filter-input.
     *
     * @param string $input
     *
     * @return self
     */
    public function setInput($input)
    {
        $this->isParsed = false;
        $this->query = trim($input);
        $this->hash = null;

        $this->messages = new MessageBag($this->translator);

        return $this;
    }

    /**
     * Returns the filter-input as-is.
     *
     * @return string|null
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
            $this->hash = md5($this->query);
        }

        return $this->hash;
    }

    /**
     * Parses the input-filter that is set.
     */
    protected function parseQuery()
    {
        // Look for the usage of OR-group(s)
        // There is 'minor problem', the 'field=value' pairs must end with an ;, or else the parsing is ignored.
        // Various solutions have been tried, but did not work...
        // But its still better, then to 'always' need to escape the grouping parentheses to use them as literals.
        if ('(' === mb_substr($this->query, 0, 1)) {
            if (preg_match_all('/\(((?:\s*(?:\p{L}[\p{L}\p{N}_]*)\s*=(?:(?:\s*(?:"(?:(?:[^"]+|"")+)"|[^;,]+)\s*,*)*);?\s*)*)\),?/us', $this->query, $groups)) {
                $groupsCount = count($groups[0]);

                if ($groupsCount > $this->limitGroups) {
                    throw new ValidationException('record_filter.maximum_groups_exceeded', array('{{ limit }}' => $this->limitGroups));
                }

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
     * Parses the 'field=value' pairs from the input.
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
                $name  = $this->getFieldNameByLabel($filterPairMatches[1][$i]);
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
                    throw new ValidationException('record_filter.required', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group+1));
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

        if (null !== $valueMatcher && null !== $regex = $valueMatcher->getMatcherRegex()) {
            $valueMatcherRegex = '|' . $regex . '-' . $regex . '|(?:>=|<=|<>|[<>!])?' . $regex;
        }

        if (preg_match_all('#\s*("(?:(?:[^"]+|"")+)"' . $valueMatcherRegex . '|[^,]+)\s*(,\s*|$)#ius', $values, $filterValues)) {
            return $filterValues[1];
        }

        return array();
    }

    /**
     * Converts the values list to an FilterValuesBag object.
     *
     * @param string       $originalInput
     * @param FilterField  $filterConfig
     * @param array|string $values
     * @param              $group
     *
     * @return FilterValuesBag
     *
     * @throws ValidationException
     */
    protected function valuesToBag($originalInput, FilterField $filterConfig, array $values, $group)
    {
        $ranges = $excludedRanges = $excludesValues = $compares = $singleValues = array();
        $valueMatcherRegex = '';

        if ($filterConfig->hasType() && ($filterConfig->getType() instanceof ValueMatcherInterface) && null !== $regex = $filterConfig->getType()->getMatcherRegex()) {
            $valueMatcherRegex = '|' . $filterConfig->getType()->getMatcherRegex();
        }

        $valueIndex = -1;

        if (count($values) > $this->limitValues) {
            throw new ValidationException('record_filter.maximum_values_exceeded', array('{{ limit }}' => $this->limitValues, '{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group+1));
        }

        foreach ($values as $valueIndex => $currentValue) {
            $value = null;

            // Comparison
            if (preg_match('#^(>=|<=|<>|[<>])("(?:(?:[^"]+|"")+)"'.$valueMatcherRegex.'|[^\h]+)$#us', $currentValue, $comparisonValue)) {
                if (!$filterConfig->acceptCompares()) {
                    throw new ValidationException('record_filter.no_compare_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group+1));
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
                    // Value starts with an quote, check if its range and not an quoted value with a '-' in it
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
                        throw new ValidationException('record_filter.no_range_support', array('{{ label }}' => $filterConfig->getLabel(), '{{ group }}' => $group+1));
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
     * Normalises an quoted-value.
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
