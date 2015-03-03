<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal\Query;

use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatformInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Doctrine QueryGenerator.
 *
 * This class is only to be used by packages of RollerworksSearch
 * and is considered internal.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class QueryGenerator
{
    /**
     * @var SearchConditionInterface
     */
    private $searchCondition;

    /**
     * @var QueryField[]
     */
    private $fields = array();

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var QueryPlatformInterface
     */
    private $queryPlatform;

    /**
     * Constructor.
     *
     * @param Connection               $connection
     * @param QueryPlatformInterface   $queryPlatform
     * @param SearchConditionInterface $searchCondition
     * @param QueryField[]             $fields
     */
    public function __construct(
        Connection $connection,
        QueryPlatformInterface $queryPlatform,
        SearchConditionInterface $searchCondition,
        array $fields
    ) {
        $this->searchCondition = $searchCondition;
        $this->connection = $connection;
        $this->queryPlatform = $queryPlatform;
        $this->fields = $fields;
    }

    /**
     * @param ValuesGroup $valuesGroup
     *
     * @return string
     */
    public function getGroupQuery(ValuesGroup $valuesGroup)
    {
        $query = array();
        $fieldSet = $this->searchCondition->getFieldSet();

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

            $groupSql[] = self::implodeWithValue(' OR ', $inclusiveSqlGroup, array('(', ')'));
            $groupSql[] = self::implodeWithValue(' AND ', $exclusiveSqlGroup, array('(', ')'));
            $query[] = self::implodeWithValue(' AND ', $groupSql, array('(', ')', true));
        }

        $groupSql = array();
        $finalQuery = array();

        // Wrap all the fields as a group
        $finalQuery[] = self::implodeWithValue(
            (ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical() ? ' OR ' : ' AND '),
            $query,
            array('(', ')', true)
        );

        foreach ($valuesGroup->getGroups() as $group) {
            $groupSql[] = $this->getGroupQuery($group);
        }

        $finalQuery[] = self::implodeWithValue(' OR ', $groupSql, array('(', ')', true));

        return self::implodeWithValue(' AND ', $finalQuery, array('(', ')'));
    }

    /**
     * Returns whether the field is accepted for processing.
     *
     * @param FieldConfigInterface $field
     *
     * @return bool
     */
    private function acceptsField(FieldConfigInterface $field)
    {
        return isset($this->fields[$field->getName()]);
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
    private function processSingleValuesInList(array $values, $fieldName, array &$query, $exclude = false)
    {
        $valuesQuery = array();
        $column = $this->queryPlatform->getFieldColumn($fieldName);

        foreach ($values as $value) {
            $valuesQuery[] = $this->queryPlatform->getValueAsSql($value->getValue(), $fieldName, $column);
        }

        if (count($valuesQuery) > 0) {
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
    private function processSingleValues(array $values, $fieldName, array &$query, $exclude = false)
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
            $column = $this->queryPlatform->getFieldColumn($fieldName, $strategy);

            if ($exclude) {
                $query[] = sprintf(
                    '%s <> %s',
                    $column,
                    $this->queryPlatform->getValueAsSql($value->getValue(), $fieldName, $column, $strategy)
                );
            } else {
                $query[] = sprintf(
                    '%s = %s',
                    $column,
                    $this->queryPlatform->getValueAsSql($value->getValue(), $fieldName, $column, $strategy)
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
    private function processRanges(array $ranges, $fieldName, array &$query, $exclude = false)
    {
        foreach ($ranges as $range) {
            $strategy = $this->getConversionStrategy($fieldName, $range->getLower());
            $column = $this->queryPlatform->getFieldColumn($fieldName, $strategy);

            $query[] = sprintf(
                $this->getRangePattern($range, $exclude),
                $column,
                $this->queryPlatform->getValueAsSql($range->getLower(), $fieldName, $column, $strategy),
                $column,
                $this->queryPlatform->getValueAsSql($range->getUpper(), $fieldName, $column, $strategy)
            );
        }
    }

    /**
     * @param Range $range
     * @param bool  $exclude
     *
     * @return string eg. "(%s >= %s AND %s <= %s)"
     */
    private function getRangePattern(Range $range, $exclude = false)
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
     * @param bool      $exclude
     */
    private function processCompares(array $compares, $fieldName, array &$query, $exclude = false)
    {
        $valuesQuery = array();

        foreach ($compares as $comparison) {
            if ($exclude !== ('<>' === $comparison->getOperator())) {
                continue;
            }

            $strategy = $this->getConversionStrategy($fieldName, $comparison->getValue());
            $column = $this->queryPlatform->getFieldColumn($fieldName, $strategy);

            $valuesQuery[] = sprintf(
                '%s %s %s',
                $column,
                $comparison->getOperator(),
                $this->queryPlatform->getValueAsSql($comparison->getValue(), $fieldName, $column, $strategy)
            );
        }

        $query[] = self::implodeWithValue(
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
    private function processPatternMatchers(array $patternMatchers, $fieldName, array &$query, $exclude = false)
    {
        foreach ($patternMatchers as $patternMatch) {
            if ($exclude !== $patternMatch->isExclusive()) {
                continue;
            }

            $query[] = $this->queryPlatform->getPatternMatcher(
                $patternMatch,
                $this->queryPlatform->getFieldColumn($fieldName)
            );
        }
    }

    /**
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return int
     */
    private function getConversionStrategy($fieldName, $value)
    {
        if ($this->fields[$fieldName]->getValueConversion() instanceof ConversionStrategyInterface) {
            return $this->fields[$fieldName]->getValueConversion()->getConversionStrategy(
                $value,
                $this->fields[$fieldName]->getFieldConfig()->getOptions(),
                $this->getConversionHints($fieldName)
            );
        }

        if ($this->fields[$fieldName]->getFieldConversion() instanceof ConversionStrategyInterface) {
            return $this->fields[$fieldName]->getFieldConversion()->getConversionStrategy(
                $value,
                $this->fields[$fieldName]->getFieldConfig()->getOptions(),
                $this->getConversionHints($fieldName)
            );
        }
    }

    /**
     * @param string $fieldName
     * @param string $column
     *
     * @return ConversionHints
     */
    private function getConversionHints($fieldName, $column = null)
    {
        $hints = new ConversionHints();
        $hints->field = $this->fields[$fieldName];
        $hints->column = $column;
        $hints->connection = $this->connection;

        return $hints;
    }

    private static function implodeWithValue($glue, array $values, array $wrap = array())
    {
        // Remove the empty values
        $values = array_filter($values, 'strlen');

        if (0 === count($values)) {
            return;
        }

        $value = implode($glue, $values);

        if (count($wrap) > 0 && (isset($wrap[2]) || count($values) > 1)) {
            return $wrap[0].$value.$wrap[1];
        }

        return $value;
    }
}
