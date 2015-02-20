<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\SearchMatch;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
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
     * @var QueryField[]
     */
    protected $fields = array();

    /**
     * @var array
     */
    protected $fieldsMappingCache = array();

    /**
     * @var array
     */
    protected $fieldConversionCache = array();

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Constructor.
     *
     * @param Connection               $connection
     * @param SearchConditionInterface $searchCondition
     * @param QueryField[]             $fields
     */
    public function __construct(Connection $connection, SearchConditionInterface $searchCondition, array $fields)
    {
        $this->searchCondition = $searchCondition;
        $this->connection = $connection;
        $this->fields = $fields;
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     *
     * @return string
     */
    public function getGroupQuery(ValuesGroup $valuesGroup, FieldSet $fieldSet = null)
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

            $this->processSingleValues(
                $values->getSingleValues(),
                $fieldName,
                $inclusiveSqlGroup
            );

            $this->processRanges(
                $values->getRanges(),
                $fieldName,
                $inclusiveSqlGroup
            );

            $this->processCompares(
                $values->getComparisons(),
                $fieldName,
                $inclusiveSqlGroup
            );

            $this->processPatternMatchers(
                $values->getPatternMatchers(),
                $fieldName,
                $inclusiveSqlGroup
            );

            $this->processSingleValues(
                $values->getExcludedValues(),
                $fieldName,
                $exclusiveSqlGroup,
                true
            );

            $this->processRanges(
                $values->getExcludedRanges(),
                $fieldName,
                $exclusiveSqlGroup,
                true
            );

            $this->processPatternMatchers(
                $values->getPatternMatchers(),
                $fieldName,
                $exclusiveSqlGroup,
                true
            );

            $this->processCompares(
                $values->getComparisons(),
                $fieldName,
                $exclusiveSqlGroup,
                true
            );

            $groupSql[] = static::implodeWithValue(' OR ', $inclusiveSqlGroup, array('(', ')'));
            $groupSql[] = static::implodeWithValue(' AND ', $exclusiveSqlGroup, array('(', ')'));
            $query[] = static::implodeWithValue(' AND ', $groupSql, array('(', ')', true));
        }

        $groupSql = array();
        $finalQuery = array();

        // Wrap all the fields as a group
        $finalQuery[] = static::implodeWithValue(
            (ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical() ? ' OR ' : ' AND '),
            $query,
            array('(', ')', true)
        );

        if ($valuesGroup->hasGroups()) {
            foreach ($valuesGroup->getGroups() as $group) {
                $groupSql[] = $this->getGroupQuery($group);
            }

            $finalQuery[] = static::implodeWithValue(' OR ', $groupSql, array('(', ')', true));
        }

        return static::implodeWithValue(' AND ', $finalQuery, array('(', ')'));
    }

    /**
     * Returns the SQL for the Field conversion.
     *
     * @param string               $fieldName
     * @param string               $column
     * @param FieldConfigInterface $field
     * @param null|int             $strategy
     *
     * @return string
     */
    public function getFieldConversionSql($fieldName, $column, FieldConfigInterface $field, $strategy = null)
    {
        if (isset($this->fieldConversionCache[$fieldName]) &&
            array_key_exists($strategy, $this->fieldConversionCache[$fieldName])
        ) {
            return $this->fieldConversionCache[$fieldName][$strategy];
        }

        return $this->fieldConversionCache[$fieldName][$strategy] = $this->fields[$fieldName]->getFieldConversion()->convertSqlField(
            $column,
            $field->getOptions(),
            $this->getConversionHints($fieldName, $column, $strategy)
        );
    }

    /**
     * Returns the SQL for the SQL wrapped-value conversion.
     *
     * @param string   $fieldName
     * @param string   $column
     * @param string   $value
     * @param null|int $strategy
     *
     * @return string
     *
     * @throws BadMethodCallException
     */
    public function getValueConversionSql($fieldName, $column, $value, $strategy = null)
    {
        return $this->fields[$fieldName]->getValueConversion()->convertSqlValue(
            $value,
            $this->fields[$fieldName]->getFieldConfig()->getOptions(),
            $this->getConversionHints($fieldName, $column, $strategy)
        );
    }

    /**
     * Returns whether the field is accepted for processing.
     *
     * @param FieldConfigInterface $field
     *
     * @return bool
     */
    protected function acceptsField(FieldConfigInterface $field)
    {
        return isset($this->fields[$field->getName()]);
    }

    /**
     * @param string   $fieldName
     * @param string   $column
     * @param null|int $strategy
     *
     * @return array
     */
    protected function getConversionHints($fieldName, $column, $strategy = null)
    {
        return array(
            'search_field' => $this->fields[$fieldName]->getFieldConfig(),
            'connection' => $this->connection,
            'db_type' => $this->fields[$fieldName]->getDbType(),
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
     * @param bool          $exclude
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

        if (!empty($valuesQuery)) {
            $query[] = sprintf(
                ($exclude ? '%s NOT IN(%s)' : '%s IN(%s)'),
                $column,
                implode(', ', $valuesQuery)
            );
        }
    }

    /**
     * Processes the single-values and returns an SQL statement query result.
     *
     * @param SingleValue[] $values
     * @param string        $fieldName
     * @param array         $query
     * @param bool          $exclude
     */
    protected function processSingleValues(array $values, $fieldName, array &$query, $exclude = false)
    {
        if (!$this->fields[$fieldName]->hasConversionStrategy() &&
            !$this->fields[$fieldName]->getValueConversion() instanceof SqlValueConversionInterface
        ) {
            // Don't use IN() with a custom SQL-statement for better compatibility
            // Always using OR seems to decrease the performance on some DB engines
            $this->processSingleValuesInList($values, $fieldName, $query, $exclude);

            return;
        }

        foreach ($values as $value) {
            $strategy = $this->getConversionStrategy($fieldName, $value->getValue());
            $column = $this->getFieldColumn($fieldName, $strategy);

            if ($exclude) {
                $query[] = sprintf(
                    '%s <> %s',
                    $this->getFieldColumn($fieldName, $strategy),
                    $this->getValueAsSql($value->getValue(), $value, $fieldName, $column, $strategy)
                );
            } else {
                $query[] = sprintf(
                    '%s = %s',
                    $this->getFieldColumn($fieldName, $strategy),
                    $this->getValueAsSql($value->getValue(), $value, $fieldName, $column, $strategy)
                );
            }
        }
    }

    /**
     * @param Range[] $ranges
     * @param string  $fieldName
     * @param array   $query
     * @param bool    $exclude
     */
    protected function processRanges(array $ranges, $fieldName, array &$query, $exclude = false)
    {
        foreach ($ranges as $range) {
            $strategy = $this->getConversionStrategy($fieldName, $range->getLower());
            $column = $this->getFieldColumn($fieldName, $strategy);

            $query[] = sprintf(
                $this->getRangePattern($range, $exclude),
                $column,
                $this->getValueAsSql($range->getLower(), $range, $fieldName, $column, $strategy),
                $column,
                $this->getValueAsSql($range->getUpper(), $range, $fieldName, $column, $strategy)
            );
        }
    }

    /**
     * @param Range $range
     * @param bool  $exclude
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
    protected function processCompares(array $compares, $fieldName, array &$query, $exclude = false)
    {
        $valuesQuery = array();

        foreach ($compares as $comparison) {
            if ($exclude !== ('<>' === $comparison->getOperator())) {
                continue;
            }

            $strategy = $this->getConversionStrategy($fieldName, $comparison->getValue());
            $column = $this->getFieldColumn($fieldName, $strategy);

            $valuesQuery[] = sprintf(
                '%s %s %s',
                $column,
                $comparison->getOperator(),
                $this->getValueAsSql($comparison->getValue(), $comparison, $fieldName, $column, $strategy)
            );
        }

        $query[] = static::implodeWithValue(
            ' AND ',
            $valuesQuery,
            count($valuesQuery) > 1 && !$exclude ? array('(', ')') : array()
        );
    }

    /**
     * @param PatternMatch[] $patternMatchers
     * @param string         $fieldName
     * @param array          $query
     * @param bool           $exclude
     */
    protected function processPatternMatchers(array $patternMatchers, $fieldName, array &$query, $exclude = false)
    {
        foreach ($patternMatchers as $patternMatch) {
            if ($exclude !== $patternMatch->isExclusive()) {
                continue;
            }

            $strategy = $this->getConversionStrategy($fieldName, $patternMatch->getValue());
            $column = $this->getFieldColumn($fieldName, $strategy);

            $query[] = $this->getPatternMatcher(
                $patternMatch,
                $column,
                $this->getValueAsSql($patternMatch->getValue(), $patternMatch, $fieldName, $column, $strategy, true)
            );
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
        if ($patternMatch->isRegex()) {
            return SearchMatch::getMatchSqlRegex(
                $column,
                $value,
                $patternMatch->isCaseInsensitive(),
                $patternMatch->isExclusive(),
                $this->connection
            );
        }

        return SearchMatch::getMatchSqlLike(
            $column,
            $value,
            $patternMatch->isCaseInsensitive(),
            $patternMatch->isExclusive(),
            $this->connection
        );
    }

    /**
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return null|int
     */
    protected function getConversionStrategy($fieldName, $value)
    {
        if ($this->fields[$fieldName]->getValueConversion() instanceof ConversionStrategyInterface) {
            return $this->fields[$fieldName]->getValueConversion()->getConversionStrategy(
                $value,
                $this->fields[$fieldName]->getFieldConfig()->getOptions(),
                $this->getConversionHints($fieldName, $this->fields[$fieldName]->getColumn())
            );
        }

        if ($this->fields[$fieldName]->getFieldConversion() instanceof ConversionStrategyInterface) {
            return $this->fields[$fieldName]->getFieldConversion()->getConversionStrategy(
                $value,
                $this->fields[$fieldName]->getFieldConfig()->getOptions(),
                $this->getConversionHints($fieldName, $this->fields[$fieldName]->getColumn())
            );
        }

        return;
    }

    /**
     * Returns either the converted value.

     * @param string   $value
     * @param object   $inputValue
     * @param string   $fieldName
     * @param string   $column
     * @param int|null $strategy
     * @param bool     $noSqlConversion
     *
     * @return string
     */
    protected function getValueAsSql($value, $inputValue, $fieldName, $column, $strategy = null, $noSqlConversion = false)
    {
        $converter = $this->fields[$fieldName]->getValueConversion();
        $field = $this->fields[$fieldName]->getFieldConfig();
        $type = $this->fields[$fieldName]->getDbType();

        if (null === $converter) {
            return $this->connection->quote(
                $type->convertToDatabaseValue($value, $this->connection->getDatabasePlatform()),
                $type->getBindingType()
            );
        }

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
            return $this->getValueConversionSql($fieldName, $column, $convertedValue, $strategy);
        }

        // Don't quote numbers as SQLite doesn't follow standards for casting
        if (!ctype_digit((string) $convertedValue)) {
            $convertedValue = $this->connection->quote($convertedValue, $type->getBindingType());
        }

        return $convertedValue;
    }

    /**
     * Returns the correct column (with SQLField conversions applied).
     *
     * @param string   $fieldName
     * @param null|int $strategy
     *
     * @return string
     */
    protected function getFieldColumn($fieldName, $strategy = null)
    {
        if (isset($this->fieldsMappingCache[$fieldName])
            && array_key_exists($strategy, $this->fieldsMappingCache[$fieldName])
        ) {
            return $this->fieldsMappingCache[$fieldName][$strategy];
        }

        if ($this->fields[$fieldName]->getFieldConversion() instanceof SqlFieldConversionInterface) {
            $this->fieldsMappingCache[$fieldName][$strategy] = $this->getFieldConversionSql(
                $fieldName,
                $this->fields[$fieldName]->getColumn(),
                $this->fields[$fieldName]->getFieldConfig(),
                $strategy
            );
        } else {
            $this->fieldsMappingCache[$fieldName][$strategy] = $this->fields[$fieldName]->getColumn();
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }

    protected static function implodeWithValue($glue, array $values, array $wrap = array())
    {
        // Remove the empty values
        $values = array_filter($values, 'strlen');

        if (empty($values)) {
            return;
        }

        $value = implode($glue, $values);

        if (!empty($wrap) && (isset($wrap[2]) || count($values) > 1)) {
            return $wrap[0].$value.$wrap[1];
        }

        return $value;
    }
}
