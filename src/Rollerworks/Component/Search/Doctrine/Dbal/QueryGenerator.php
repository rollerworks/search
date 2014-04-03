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

use Doctrine\DBAL\Driver\Connection;
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

    /**
     * @param array $fields
     *
     * @throws InvalidArgumentException
     */
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
                    sprintf('The keys "field" should not be empty for field "%s"', $fieldName)
                );
            }

            if (empty($fieldConfig['db_type'])) {
                throw new InvalidArgumentException(
                    sprintf('The keys "db_type" should not be empty for field "%s"', $fieldName)
                );
            }

            if (empty($fieldConfig['field_convertor'])) {
                $fieldConfig['field_convertor'] = null;
            }

            if (empty($fieldConfig['value_convertor'])) {
                $fieldConfig['value_convertor'] = null;
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

            $groupSql = array();
            $inclusiveSqlGroup = array();
            $exclusiveSqlGroup = array();

            $this->processSingleValues($values->getSingleValues(), $fieldName, $inclusiveSqlGroup);
            $this->processRanges($values->getRanges(), $fieldName, $inclusiveSqlGroup);
            $this->processCompares($values->getComparisons(), $fieldName, $inclusiveSqlGroup);
            $this->processPatternMatchers($values->getPatternMatchers(), $fieldName, $inclusiveSqlGroup);

            if ($inclusiveSqlGroup) {
                $groupSql[] = '('.implode(' OR ', $inclusiveSqlGroup).')';
            }

            $this->processSingleValues($values->getExcludedValues(), $fieldName, $exclusiveSqlGroup, true);
            $this->processRanges($values->getExcludedRanges(), $fieldName, $exclusiveSqlGroup, true);
            $this->processPatternMatchers($values->getPatternMatchers(), $fieldName, $exclusiveSqlGroup, true);

            if ($exclusiveSqlGroup) {
                $groupSql[] = '('.implode(' AND ', $exclusiveSqlGroup).')';
            }

            if ($groupSql) {
                $query[] = '('.implode(' AND ', $groupSql).')';
            }
        }

        $finalQuery = array();

        // Wrap all the fields as a group
        if ($query) {
            $finalQuery[] = '('.implode(ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical() ? ' OR ' : ' AND ', $query).')';
        }

        if ($valuesGroup->hasGroups()) {
            $groupSql = array();

            foreach ($valuesGroup->getGroups() as $group) {
                $groupSql[] = $this->getGroupSql($group);
            }

            if ($groupSql) {
                $finalQuery[] = '('.implode(' OR ', $groupSql).')';
            }

            if ($query) {
                return '('.implode(' AND ', $finalQuery).')';
            }
        }

        if ($finalQuery) {
            return implode(' AND ', $finalQuery);
        }

        return '';
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
    public function getFieldConversionSql($fieldName, $column, FieldConfigInterface $field, $strategy = null)
    {
        if (isset($this->fieldConversionCache[$fieldName]) && array_key_exists($strategy, $this->fieldConversionCache[$fieldName])) {
            return $this->fieldConversionCache[$fieldName][$strategy];
        }

        return $this->fieldConversionCache[$fieldName][$strategy] = $this->fields[$fieldName]['field_convertor']->convertSqlField(
            $column,
            $field->getOptions(),
            $this->getConversionHints($fieldName, $column, $strategy)
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
     * @param boolean              $isValueEmbedded
     *
     * @return string
     *
     * @throws BadMethodCallException
     */
    public function getValueConversionSql($fieldName, $column, $value, FieldConfigInterface $field, $strategy = null, $isValueEmbedded = false)
    {
        if ($isValueEmbedded) {
            if (!array_key_exists($value, $this->parameters)) {
                throw new BadMethodCallException(sprintf('Unable to find query-parameter "%s", requested for embedding by ValueConversion.', $value));
            }

            $value = $this->parameters[$value];
        }

        return $this->fields[$fieldName]['value_convertor']->convertSqlValue(
            $value,
            $field->getOptions(),
            $this->getConversionHints($fieldName, $column, $strategy)
        );
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
        return isset($this->fields[$field->getName()]);
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
     * @param SingleValue[] $values
     * @param string        $fieldName
     * @param array         $query
     * @param boolean       $exclude
     *
     * @return string
     */
    protected function processSingleValuesInList(array $values, $fieldName, array &$query, $exclude = false)
    {
        $valuesQuery = array();
        $column = $this->getFieldColumn($fieldName);

        foreach ($values as $value) {
            $valuesQuery[] = $this->getValueAsSql($value->getValue(), $value, $fieldName, $column);
        }

        if ($valuesQuery) {
            $query[] = sprintf(($exclude ? '%s NOT IN(%s)' : '%s IN(%s)'), $column, implode(', ', $valuesQuery));
        }
    }

    /**
     * Processes the single-values and returns an SQL statement query result.
     *
     * @param SingleValue[] $values
     * @param string        $fieldName
     * @param array         $query
     * @param boolean       $exclude
     */
    protected function processSingleValues(array $values, $fieldName, array &$query, $exclude = false)
    {
        if (!$this->fields[$fieldName]['field_convertor'] instanceof ConversionStrategyInterface && !$this->fields[$fieldName]['value_convertor'] instanceof SqlValueConversionInterface) {
            // Don't use IN() with a custom SQL-statement for better compatibility
            // Always using OR seems to decrease the performance on some DB engines
            $this->processSingleValuesInList($values, $fieldName, $query, $exclude);

            return ;
        }

        $valuesQuery = array();

        foreach ($values as $value) {
            $strategy = $this->getConversionStrategy($fieldName, $value->getValue());
            $column = $this->getFieldColumn($fieldName, $strategy);

            if ($exclude) {
                $valuesQuery[] = sprintf('%s <> %s', $this->getFieldColumn($fieldName, $strategy), $this->getValueAsSql($value->getValue(), $value, $fieldName, $column, $strategy));
            } else {
                $valuesQuery[] = sprintf('%s = %s', $this->getFieldColumn($fieldName, $strategy), $this->getValueAsSql($value->getValue(), $value, $fieldName, $column, $strategy));
            }
        }

        if ($valuesQuery) {
            $query[] = implode(($exclude ? ' AND ' : ' OR '), $valuesQuery);
        }
    }

    /**
     * @param Range[] $ranges
     * @param string  $fieldName
     * @param array   $query
     * @param boolean $exclude
     */
    protected function processRanges(array $ranges, $fieldName, array &$query, $exclude = false)
    {
        $valuesQuery = array();

        foreach ($ranges as $range) {
            $strategy = $this->getConversionStrategy($fieldName, $range->getLower());
            $column = $this->getFieldColumn($fieldName, $strategy);

            $valuesQuery[] = sprintf(
                $this->getRangePattern($range, $exclude),
                $column,
                $this->getValueAsSql($range->getLower(), $range, $fieldName, $column, $strategy),
                $column,
                $this->getValueAsSql($range->getUpper(), $range, $fieldName, $column, $strategy)
            );
        }

        if ($valuesQuery) {
            $query[] = implode(($exclude ? ' AND ' : ' OR '), $valuesQuery);
        }
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
     * @param Compare[] $compares
     * @param string    $fieldName
     * @param array     $query
     */
    protected function processCompares(array $compares, $fieldName, array &$query)
    {
        $valuesQuery = array();

        foreach ($compares as $comparison) {
            $strategy = $this->getConversionStrategy($fieldName, $comparison->getValue());

            $column = $this->getFieldColumn($fieldName, $strategy);
            $valuesQuery[] =  sprintf('%s %s %s', $column, $comparison->getOperator(), $this->getValueAsSql($comparison->getValue(), $comparison, $fieldName, $column, $strategy));
        }

        if ($valuesQuery) {
            $query[] = implode(' OR ', $valuesQuery);
        }
    }

    /**
     * @param PatternMatch[] $patternMatchers
     * @param string         $fieldName
     * @param array          $query
     * @param boolean        $exclude
     */
    protected function processPatternMatchers(array $patternMatchers, $fieldName, array &$query, $exclude = false)
    {
        $valuesQuery = array();

        foreach ($patternMatchers as $patternMatch) {
            $isExclusive = $patternMatch->isExclusive();
            if ((!$exclude && $isExclusive) || ($exclude && !$isExclusive)) {
                continue;
            }

            $strategy = $this->getConversionStrategy($fieldName, $patternMatch->getValue());
            $column = $this->getFieldColumn($fieldName, $strategy);
            $valuesQuery[] = $this->getPatternMatcher($patternMatch, $column, $this->getValueAsSql($patternMatch->getValue(), $patternMatch, $fieldName, $column, $strategy, true));
        }

        if ($valuesQuery) {
            $query[] = implode(($exclude ? ' AND ' : ' OR '), $valuesQuery);
        }
    }

    /**
     * @param PatternMatch $patternMatch
     * @param string       $column
     * @param string       $value
     *
     * @return string
     */
    protected function getPatternMatcher(PatternMatch $patternMatch, $column, $value)
    {
        if (PatternMatch::PATTERN_REGEX === $patternMatch->getType() || PatternMatch::PATTERN_NOT_REGEX === $patternMatch->getType()) {
            return SearchMatch::getMatchSqlRegex($column, $value, $patternMatch->isCaseInsensitive(), $patternMatch->isExclusive(), $this->connection);
        }

        return SearchMatch::getMatchSqlLike($column, $value, $patternMatch->isCaseInsensitive(), $patternMatch->isExclusive(), $this->connection);
    }

    /**
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return null|integer
     */
    protected function getConversionStrategy($fieldName, $value)
    {
        if ($this->fields[$fieldName]['value_convertor'] instanceof ConversionStrategyInterface) {
            $hints = $this->getConversionHints($fieldName, $this->fields[$fieldName]['column']);

            return $this->fields[$fieldName]['value_convertor']->getConversionStrategy($value, $this->fields[$fieldName]['field']->getOptions(), $hints);
        }

        if ($this->fields[$fieldName]['field_convertor'] instanceof ConversionStrategyInterface) {
            $hints = $this->getConversionHints($fieldName, $this->fields[$fieldName]['column']);

            return $this->fields[$fieldName]['field_convertor']->getConversionStrategy($value, $this->fields[$fieldName]['field']->getOptions(), $hints);
        }

        return null;
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
        if (!$this->fields[$fieldName]['value_convertor'] instanceof ValueConversionInterface) {
            $paramName = $this->getUniqueParameterName($fieldName);
            $this->parameters[$paramName] = $value;

            return ':' . $paramName;
        }

        /** @var \Doctrine\DBAL\Types\Type $type */
        $type = $this->fields[$fieldName]['db_type'];
        /** @var ValueConversionInterface|SqlValueConversionInterface $converter */
        $converter = $this->fields[$fieldName]['value_convertor'];
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

        if ($this->fields[$fieldName]['field_convertor'] instanceof SqlFieldConversionInterface) {
            $this->fieldsMappingCache[$fieldName][$strategy] = $this->getFieldConversionSql($fieldName, $this->fields[$fieldName]['column'], $this->fields[$fieldName]['field'], $strategy);
        } else {
            $this->fieldsMappingCache[$fieldName][$strategy] = $this->fields[$fieldName]['column'];
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }
}
