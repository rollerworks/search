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

namespace Rollerworks\Component\Search\Doctrine\Dbal\Query;

use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatformInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

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
     * @var array
     */
    private $fields = [];

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
     * @param Connection             $connection
     * @param QueryPlatformInterface $queryPlatform
     * @param array                  $fields
     */
    public function __construct(Connection $connection, QueryPlatformInterface $queryPlatform, array $fields)
    {
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
        $query = [];

        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            if (!isset($this->fields[$fieldName])) {
                continue;
            }

            $groupSql = [];
            $inclusiveSqlGroup = [];
            $exclusiveSqlGroup = [];

            if (is_array($this->fields[$fieldName])) {
                foreach ($this->fields[$fieldName] as $n) {
                    $this->processFieldValues($values, $n, $inclusiveSqlGroup, $exclusiveSqlGroup);
                }
            } else {
                $this->processFieldValues($values, $fieldName, $inclusiveSqlGroup, $exclusiveSqlGroup);
            }

            $groupSql[] = self::implodeWithValue(' OR ', $inclusiveSqlGroup, ['(', ')']);
            $groupSql[] = self::implodeWithValue(' AND ', $exclusiveSqlGroup, ['(', ')']);
            $query[] = self::implodeWithValue(' AND ', $groupSql, ['(', ')', true]);
        }

        $finalQuery = [];

        // Wrap all the fields as a group
        $finalQuery[] = self::implodeWithValue(
            ' '.strtoupper($valuesGroup->getGroupLogical()).' ',
            $query,
            ['(', ')', true]
        );

        $this->processGroups($valuesGroup->getGroups(), $finalQuery);

        return (string) self::implodeWithValue(' AND ', $finalQuery, ['(', ')']);
    }

    /**
     * @param ValuesGroup[] $groups
     * @param array         $query
     */
    private function processGroups(array $groups, array &$query)
    {
        $groupSql = [];

        foreach ($groups as $group) {
            $groupSql[] = $this->getGroupQuery($group);
        }

        $query[] = self::implodeWithValue(' OR ', $groupSql, ['(', ')', true]);
    }

    /**
     * Processes the single-values and returns an SQL statement query result.
     *
     * @param array  $values
     * @param string $fieldName
     * @param array  $query
     * @param bool   $exclude
     *
     * @return string
     */
    private function processSingleValuesInList(array $values, $fieldName, array &$query, $exclude = false)
    {
        $valuesQuery = [];
        $column = $this->queryPlatform->getFieldColumn($fieldName);

        foreach ($values as $value) {
            $valuesQuery[] = $this->queryPlatform->getValueAsSql($value, $fieldName, $column);
        }

        $patterns = ['%s IN(%s)', '%s NOT IN(%s)'];

        if (count($valuesQuery) > 0) {
            $query[] = sprintf(
                $patterns[(int) $exclude],
                $column,
                implode(', ', $valuesQuery)
            );
        }
    }

    /**
     * Processes the single-values and returns an SQL statement query result.
     *
     * @param array  $values
     * @param string $fieldName
     * @param array  $query
     * @param bool   $exclude
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

        $patterns = ['%s = %s', '%s <> %s'];

        foreach ($values as $value) {
            $strategy = $this->getConversionStrategy($fieldName, $value);
            $column = $this->queryPlatform->getFieldColumn($fieldName, $strategy);

            $query[] = sprintf(
                $patterns[(int) $exclude],
                $column,
                $this->queryPlatform->getValueAsSql($value, $fieldName, $column, $strategy)
            );
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
        $valuesQuery = [];

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
            count($valuesQuery) > 1 && !$exclude ? ['(', ')'] : []
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
    private function getConversionStrategy($fieldName, $value): int
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

        return 0;
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

    private static function implodeWithValue($glue, array $values, array $wrap = [])
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

    private function processFieldValues(ValuesBag $values, $fieldName, array &$inclusiveSqlGroup, array &$exclusiveSqlGroup)
    {
        $this->processSingleValues(
            $values->getSimpleValues(),
            $fieldName,
            $inclusiveSqlGroup
        );

        $this->processRanges(
            $values->get(Range::class),
            $fieldName,
            $inclusiveSqlGroup
        );

        $this->processCompares(
            $values->get(Compare::class),
            $fieldName,
            $inclusiveSqlGroup
        );

        $this->processPatternMatchers(
            $values->get(PatternMatch::class),
            $fieldName,
            $inclusiveSqlGroup
        );

        $this->processSingleValues(
            $values->getExcludedSimpleValues(),
            $fieldName,
            $exclusiveSqlGroup,
            true
        );

        $this->processRanges(
            $values->get(ExcludedRange::class),
            $fieldName,
            $exclusiveSqlGroup,
            true
        );

        $this->processPatternMatchers(
            $values->get(PatternMatch::class),
            $fieldName,
            $exclusiveSqlGroup,
            true
        );

        $this->processCompares(
            $values->get(Compare::class),
            $fieldName,
            $exclusiveSqlGroup,
            true
        );
    }
}
