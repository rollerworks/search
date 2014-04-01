<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Doctrine QueryGenerator.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class QueryGenerator
{
    /**
     * @var SearchConditionInterface
     */
    protected $searchCondition;

    /**
     * @var string
     */
    protected $parameterPrefix;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @var array
     */
    protected $fieldsMappingCache = array();

    /**
     * @var array
     */
    protected $fieldConversionCache = array();

    /**
     * @var array
     */
    protected $paramPosition = array();

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Constructor.
     *
     * @param Connection               $connection      Doctrine DBAL Connection object
     * @param SearchConditionInterface $searchCondition SearchCondition object
     * @param array                    $fields          Array containing the: field(FieldConfigInterface), column (including alias), db_type, conversion; per field-name
     * @param string                   $parameterPrefix
     */
    public function __construct(Connection $connection, SearchConditionInterface $searchCondition, array $fields, $parameterPrefix = '')
    {
        $this->searchCondition = $searchCondition;
        $this->connection = $connection;
        $this->configureFields($fields);
        $this->parameterPrefix = $parameterPrefix;
    }

    protected function configureFields(array $fields)
    {
        foreach ($fields as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['column'])) {
                throw new InvalidArgumentException(
                    sprintf('The keys "column" should not be empty for field "%s"', $fieldName)
                );
            }

            if (empty($fieldConfig['field'])) {
                throw new InvalidArgumentException(
                    sprintf('The keys "alias" should not be empty for field "%s"', $fieldName)
                );
            }

            if (empty($fieldConfig['db_type'])) {
                throw new InvalidArgumentException(
                    sprintf('The keys "db_type" should not be empty for field "%s"', $fieldName)
                );
            }

            if (empty($fieldConfig['conversion'])) {
                $fieldConfig['conversion'] = null;
            }
        }

        $this->fields = $fields;
    }

    /**
     * Returns the parameters that where set during the generation process.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns the parameter-value that where set during the generation process.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     *
     * @return string
     */
    public function getGroupSql(ValuesGroup $valuesGroup, FieldSet $fieldSet = null)
    {
        $query = array();
        $fieldSet = $fieldSet ?: $this->searchCondition->getFieldSet();

        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            $field = $fieldSet->get($fieldName);

            if (!$this->acceptsField($field)) {
                continue;
            }

            $column = $this->fields[$fieldName]['column'];

            $groupSql = array();
            $inclusiveSqlGroup = array();
            $exclusiveSqlGroup = array();

            if ($values->hasSingleValues()) {
                $inclusiveSqlGroup[] = $this->processSingleValues($values->getSingleValues(), $column, $fieldName, $field);
            }

            if ($values->hasRanges()) {
                $inclusiveSqlGroup[] = $this->processRanges($values->getRanges(), $column, $fieldName, $field);
            }

            if ($values->hasComparisons()) {
                $inclusiveSqlGroup[] = $this->processCompares($values->getComparisons(), $column, $fieldName, $field);
            }

            if ($values->hasPatternMatchers()) {
                $inclusiveSqlGroup[] = $this->processPatternMatchers($values->getPatternMatchers(), $column, $fieldName, $field);
            }

            if ($inclusiveSqlGroup) {
                $groupSql[] = '('.implode(' OR ', $inclusiveSqlGroup).')';
            }

            if ($values->hasExcludedValues()) {
                $exclusiveSqlGroup[] = $this->processSingleValues($values->getExcludedValues(), $column, $fieldName, $field, true);
            }

            if ($values->hasExcludedRanges()) {
                $exclusiveSqlGroup[] = $this->processRanges($values->getExcludedRanges(), $column, $fieldName, $field, true);
            }

            if ($values->hasPatternMatchers()) {
                $exclusiveSqlGroup[] = $this->processPatternMatchers($values->getPatternMatchers(), $column, $fieldName, $field, true);
            }

            if ($exclusiveSqlGroup) {
                $groupSql[] = '('.implode(' AND ', $inclusiveSqlGroup).')';
            }

            if ($groupSql) {
                $query[] = '('.implode(' AND ', $groupSql).')';
            }
        }

        // Wrap all the fields as a group
        if ($query) {
            $query[] = '('.implode(ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical() ? ' OR ' : ' AND ', $query).')';
        }

        if ($valuesGroup->hasGroups()) {
            $groupSql = array();

            foreach ($valuesGroup->getGroups() as $group) {
                $groupSql[] = $this->getGroupSql($group);
            }

            if ($groupSql) {
                if ($query) {
                    $query[] = '('.implode(' OR ', $groupSql).')';
                } else {
                    $query[] = implode(' OR ', $groupSql);
                }
            }
        }

        // Now wrap it one more time to finalize the group
        if ($query) {
            return '('.implode(' AND ', $query).')';
        }

        return '';
    }

    /**
     * Returns whether the field is accepted for processing.
     *
     * @param FieldConfigInterface $field
     *
     * @return boolean
     */
    protected function acceptsField(FieldConfigInterface $field)
    {
        return true;
    }

    /**
     * Returns the SQL for the Field conversion.
     *
     * @param string               $fieldName
     * @param string               $column
     * @param FieldConfigInterface $field
     * @param null|integer         $strategy
     *
     * @return string
     */
    protected function getFieldConversionSql($fieldName, $column, FieldConfigInterface $field, $strategy = null)
    {
        if (isset($this->fieldConversionCache[$fieldName]) && array_key_exists($strategy, $this->fieldConversionCache[$fieldName])) {
            return $this->fieldConversionCache[$fieldName][$strategy];
        }

        return $this->fieldConversionCache[$fieldName][$strategy] = $this->fields[$fieldName]['conversion']->convertSqlField(
            $column,
            $field->getOptions(),
            $this->getConversionHints($fieldName, $column, $field, $strategy)
        );
    }

    /**
     * Returns the SQL for the SQL wrapped-value conversion.
     *
     * @param string               $fieldName
     * @param string               $column
     * @param string               $value
     * @param FieldConfigInterface $field
     * @param null|integer         $strategy
     *
     * @return string
     *
     * @throws BadMethodCallException
     */
    protected function getValueConversionSql($fieldName, $column, $value, FieldConfigInterface $field, $strategy = null)
    {
        return $this->fields[$fieldName]['conversion']->convertSqlValue(
            $value,
            $field->getOptions(),
            $this->getConversionHints($fieldName, $column, $field, $strategy)
        );
    }

    /**
     * @param string       $fieldName
     * @param string       $column
     * @param null|integer $strategy
     *
     * @return array
     */
    protected function getConversionHints($fieldName, $column, $strategy = null)
    {
        return array(
            'search_field' => $this->fields[$fieldName]['field'],
            'connection' => $this->connection,
            'db_type' => $this->fields[$fieldName]['db_type'],
            'column' => $column,
            'conversion_strategy' => $strategy,
        );
    }

    /**
     * Processes the single-values and returns an SQL statement query result.
     *
     * @param SingleValue[]        $values
     * @param string               $column
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     * @param boolean              $exclude
     *
     * @return string
     */
    protected function processSingleValues(array $values, $column, $fieldName, FieldConfigInterface $field, $exclude = false)
    {
        $inList = array();

        // Don't use IN() with a custom SQL-statement for better compatibility
        if (!$this->fields[$fieldName]['conversion'] instanceof ConversionStrategyInterface && !$this->fields[$fieldName]['conversion'] instanceof SqlValueConversionInterface) {
            foreach ($values as $value) {
                $inList[] = $this->getValueAsSql($value->getValue(), $value, $fieldName, $column);
            }

            if ($exclude) {
                return sprintf('%s NOT IN(%s)', $column, implode(', ', $inList));
            }

            return sprintf('%s IN(%s)', $column, implode(', ', $inList));
        }

        foreach ($values as $value) {
            if ($this->fields[$fieldName]['conversion'] instanceof ConversionStrategyInterface) {
                $strategy = $this->fields[$fieldName]['conversion']->getConversionStrategy($value->getValue(), $field->getOptions(), $this->getConversionHints($fieldName, $column));
            } else {
                $strategy = null;
            }

            if ($exclude) {
                $inList[] = sprintf('%s <> %s', $this->getFieldColumn($fieldName, $strategy), $this->getValueAsSql($value->getValue(), $value, $fieldName, $column, $strategy));
            } else {
                $inList[] = sprintf('%s = %s', $this->getFieldColumn($fieldName, $strategy), $this->getValueAsSql($value->getValue(), $value, $fieldName, $column, $strategy));
            }
        }

        return implode(($exclude ? ' AND ' : ' OR '), $inList);
    }

    /**
     * @param Range[]              $ranges
     * @param string               $column
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     * @param boolean              $exclude
     *
     * @return string
     */
    protected function processRanges(array $ranges, $column, $fieldName, FieldConfigInterface $field, $exclude = false)
    {
        $query = array();
        $hints = array();

        if ($this->fields[$fieldName]['conversion'] instanceof ConversionStrategyInterface) {
            $hints = $this->getConversionHints($fieldName, $column);
        }

        foreach ($ranges as $range) {
            if ($this->fields[$fieldName]['conversion'] instanceof ConversionStrategyInterface) {
                $strategy = $this->fields[$fieldName]['conversion']->getConversionStrategy($range->getLower(), $field->getOptions(), $hints);
            } else {
                $strategy = null;
            }

            $column = $this->getFieldColumn($fieldName, $strategy);

            $query[] = sprintf(
                $this->getRangePattern($range, $exclude),
                $column,
                $this->getValueAsSql($range->getLower(), $range, $fieldName, $column, $strategy),
                $column,
                $this->getValueAsSql($range->getUpper(), $range, $fieldName, $column, $strategy)
            );
        }

        if ($query) {
            return '('.implode(($exclude ? ' AND ' : ' OR '), $query).')';
        }

        return '';
    }

    /**
     * @param Range   $range
     * @param boolean $exclude
     *
     * @return string eg. "(%s >= %s AND %s <= %s)"
     */
    protected function getRangePattern(Range $range, $exclude = false)
    {
        $pattern = '(%s ';

        if ($exclude) {
            $pattern .= ($range->isLowerInclusive() ? '<=' : '<');
            $pattern .= ' %s OR %s '; // lower-bound value, AND fieldname
            $pattern .= ($range->isUpperInclusive() ? '>=' : '>');
            $pattern .= ' %s'; // upper-bound value
        } else {
            $pattern .= ($range->isLowerInclusive() ? '>=' : '>');
            $pattern .= ' %s AND %s '; // lower-bound value, AND fieldname
            $pattern .= ($range->isUpperInclusive() ? '<=' : '<');
            $pattern .= ' %s'; // upper-bound value
        }

        $pattern .= ')';

        return $pattern;
    }

    /**
     * @param Compare[]            $compares
     * @param string               $column
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     *
     * @return string
     */
    protected function processCompares($compares, $column, $fieldName, FieldConfigInterface $field)
    {
        $query = array();
        $hints = array();

        if ($this->fields[$fieldName]['conversion'] instanceof ConversionStrategyInterface) {
            $hints = $this->getConversionHints($fieldName, $column);
        }

        foreach ($compares as $comparison) {
            if ($this->fields[$fieldName]['conversion'] instanceof ConversionStrategyInterface) {
                $strategy = $this->fields[$fieldName]['conversion']->getConversionStrategy($comparison->getValue(), $field->getOptions(), $hints);
            } else {
                $strategy = null;
            }

            $column = $this->getFieldColumn($fieldName, $strategy);
            $query[] =  sprintf('%s %s %s', $column, $comparison->getOperator(), $this->getValueAsSql($comparison->getValue(), $comparison, $fieldName, $column, $strategy));
        }

        if ($query) {
            return '('.implode(' OR ', $query).')';
        }

        return '';
    }

    /**
     * @param PatternMatch[]       $patternMatchers
     * @param string               $column
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     * @param boolean              $exclude
     *
     * @return string
     */
    protected function processPatternMatchers($patternMatchers, $column, $fieldName, FieldConfigInterface $field, $exclude = false)
    {
        $query = array();
        $hints = array();

        if ($this->fields[$fieldName]['conversion'] instanceof ConversionStrategyInterface) {
            $hints = $this->getConversionHints($fieldName, $column);
        }

        foreach ($patternMatchers as $patternMatch) {
            $isExclusive = $patternMatch->isExclusive();
            if ((!$exclude && $isExclusive) || ($exclude && !$isExclusive)) {
                continue;
            }

            if ($this->fields[$fieldName]['conversion'] instanceof ConversionStrategyInterface) {
                $strategy = $this->fields[$fieldName]['conversion']->getConversionStrategy($patternMatch->getValue(), $field->getOptions(), $hints);
            } else {
                $strategy = null;
            }

            $column = $this->getFieldColumn($fieldName, $strategy);
            $query[] = $this->getPatternMatcherPattern($patternMatch, $column, $this->getValueAsSql($patternMatch->getValue(), $patternMatch, $fieldName, $column, $strategy, true));
        }

        if ($query) {
            return '('.implode(($exclude ? ' AND ' : ' OR '), $query).')';
        }

        return '';
    }

    /**
     * @param PatternMatch $patternMatch
     * @param string       $column
     * @param string       $value
     *
     * @return string
     */
    protected function getPatternMatcherPattern(PatternMatch $patternMatch, $column, $value)
    {
        if (PatternMatch::PATTERN_REGEX === $patternMatch->getType() || PatternMatch::PATTERN_NOT_REGEX === $patternMatch->getType()) {
            return SearchMatch::getMatchSqlRegex($column, $value, $patternMatch->isCaseInsensitive(), $patternMatch->isExclusive(), $this->connection);
        }

        return SearchMatch::getMatchSqlLike($column, $value, $patternMatch->isCaseInsensitive(), $patternMatch->isExclusive(), $this->connection);
    }

    /**
     * Returns either a parameter-name or converted value.
     *
     * When there is a conversion and the conversion returns SQL the value is threaded as-is.
     * But if DQL is used the value is wrapped inside a FILTER_VALUE_CONVERSION() DQL function,
     * and replaced when the SQL is created.
     *
     * @param string       $value
     * @param object       $inputValue
     * @param string       $fieldName
     * @param string       $column
     * @param integer|null $strategy
     * @param boolean      $noSqlConversion
     *
     * @return string|float|integer
     */
    protected function getValueAsSql($value, $inputValue, $fieldName, $column, $strategy = null, $noSqlConversion = false)
    {
        // No conversions so set the value as query-parameter
        if (!$this->fields[$fieldName]['conversion'] instanceof ValueConversionInterface) {
            $paramName = $this->getUniqueParameterName($fieldName);
            $this->parameters[$paramName] = $value;

            return ':' . $paramName;
        }

        /** @var \Doctrine\DBAL\Types\Type $type */
        $type = $this->fields[$fieldName]['db_type'];
        /** @var ValueConversionInterface|SqlValueConversionInterface $converter */
        $converter = $this->fields[$fieldName]['conversion'];
        /** @var FieldConfigInterface $field */
        $field = $this->fields[$fieldName]['field'];

        $convertedValue = $value;
        $hints = $this->getConversionHints($fieldName, $column, $strategy) + array(
            'original_value' => $value,
            'value_object' => $inputValue,
        );

        if ($converter->requiresBaseConversion($value, $field->getOptions(), $hints)) {
            $convertedValue = $type->convertToDatabaseValue($value, $this->connection->getDatabasePlatform());
        }

        $convertedValue = $converter->convertValue($convertedValue, $field->getOptions(), $hints);

        if (!$noSqlConversion && $converter instanceof SqlValueConversionInterface) {
            return $this->convertSqlValue($converter, $fieldName, $column, $value, $convertedValue, $field, $hints, $strategy);
        }

        $paramName = $this->getUniqueParameterName($fieldName);
        $this->parameters[$paramName] = $convertedValue;

        return ':' . $paramName;
    }

    /**
     * @param SqlValueConversionInterface $converter
     * @param string                      $fieldName
     * @param string                      $column
     * @param mixed                       $value
     * @param string                      $convertedValue
     * @param FieldConfigInterface        $field
     * @param array                       $hints
     * @param integer|null                $strategy
     *
     * @return string
     */
    protected function convertSqlValue(SqlValueConversionInterface $converter, $fieldName, $column, $value, $convertedValue, FieldConfigInterface $field, array $hints, $strategy)
    {
        if (!$converter->valueRequiresEmbedding($value, $field->getOptions(), $hints)) {
            $paramName = $this->getUniqueParameterName($fieldName);
            $this->parameters[$paramName] = $convertedValue;
            $convertedValue = ':' . $paramName;
        }

        return $this->getValueConversionSql($fieldName, $column, $convertedValue, $field, $strategy);
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    protected function getUniqueParameterName($fieldName)
    {
        if (!isset($this->paramPosition[$fieldName])) {
            $this->paramPosition[$fieldName] = -1;
        }

        return (null !== $this->parameterPrefix ? $this->parameterPrefix . '_' : '') . $fieldName . '_' . $this->paramPosition[$fieldName] += 1;
    }

    /**
     * Returns the correct column (with SQLField conversions applied).
     *
     * @param string       $fieldName
     * @param null|integer $strategy
     *
     * @return string
     */
    protected function getFieldColumn($fieldName, $strategy = null)
    {
        if (isset($this->fieldsMappingCache[$fieldName]) && array_key_exists($strategy, $this->fieldsMappingCache[$fieldName])) {
            return $this->fieldsMappingCache[$fieldName][$strategy];
        }

        if (isset($this->fields[$fieldName])) {
            $this->fieldsMappingCache[$fieldName][$strategy] = $this->getFieldConversionSql($fieldName, $this->fields[$fieldName]['column'], $this->fields[$fieldName]['field'], $strategy);
        } else {
            $this->fieldsMappingCache[$fieldName][$strategy] = $this->fields[$fieldName]['column'];
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }
}
