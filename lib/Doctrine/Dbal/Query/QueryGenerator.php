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
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;
use Rollerworks\Component\Search\Doctrine\Dbal\StrategySupportedConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;
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
 *
 * @internal
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
     * @var QueryPlatform
     */
    private $queryPlatform;

    /**
     * Constructor.
     *
     * @param Connection    $connection
     * @param QueryPlatform $queryPlatform
     * @param array         $fields
     */
    public function __construct(Connection $connection, QueryPlatform $queryPlatform, array $fields)
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
    public function getGroupQuery(ValuesGroup $valuesGroup): string
    {
        $query = [];

        foreach ($valuesGroup->getFields() as $mappingConfig => $values) {
            if (!isset($this->fields[$mappingConfig])) {
                continue;
            }

            $groupSql = [];
            $inclusiveSqlGroup = [];
            $exclusiveSqlGroup = [];

            foreach ($this->fields[$mappingConfig] as $mappingsConfig) {
                $this->processFieldValues($values, $mappingsConfig, $inclusiveSqlGroup, $exclusiveSqlGroup);
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
     * @param array      $values
     * @param QueryField $mappingConfig
     * @param array      $query
     * @param bool       $exclude
     *
     * @return string
     */
    private function processSingleValuesInList(array $values, QueryField $mappingConfig, array &$query, bool $exclude = false)
    {
        $valuesQuery = [];
        $column = $this->queryPlatform->getFieldColumn($mappingConfig);

        foreach ($values as $value) {
            $valuesQuery[] = $this->queryPlatform->getValueAsSql($value, $mappingConfig, $column);
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
     * @param array      $values
     * @param QueryField $mappingConfig
     * @param array      $query
     * @param bool       $exclude
     */
    private function processSingleValues(array $values, QueryField $mappingConfig, array &$query, bool $exclude = false)
    {
        if (!$mappingConfig->strategyEnabled && !$mappingConfig->valueConversion instanceof ValueConversion) {
            // Don't use IN() with a custom SQL-statement for better compatibility
            // Always using OR seems to decrease the performance on some DB engines
            $this->processSingleValuesInList($values, $mappingConfig, $query, $exclude);

            return;
        }

        $patterns = ['%s = %s', '%s <> %s'];

        foreach ($values as $value) {
            $strategy = $this->getConversionStrategy($mappingConfig, $value);
            $column = $this->queryPlatform->getFieldColumn($mappingConfig, $strategy);

            $query[] = sprintf(
                $patterns[(int) $exclude],
                $column,
                $this->queryPlatform->getValueAsSql($value, $mappingConfig, $column, $strategy)
            );
        }
    }

    /**
     * @param Range[]    $ranges
     * @param QueryField $mappingConfig
     * @param array      $query
     * @param bool       $exclude
     */
    private function processRanges(array $ranges, QueryField $mappingConfig, array &$query, bool $exclude = false)
    {
        foreach ($ranges as $range) {
            $strategy = $this->getConversionStrategy($mappingConfig, $range->getLower());
            $column = $this->queryPlatform->getFieldColumn($mappingConfig, $strategy);

            $query[] = sprintf(
                $this->getRangePattern($range, $exclude),
                $column,
                $this->queryPlatform->getValueAsSql($range->getLower(), $mappingConfig, $column, $strategy),
                $column,
                $this->queryPlatform->getValueAsSql($range->getUpper(), $mappingConfig, $column, $strategy)
            );
        }
    }

    /**
     * @param Range $range
     * @param bool  $exclude
     *
     * @return string eg. "(%s >= %s AND %s <= %s)"
     */
    private function getRangePattern(Range $range, bool $exclude = false): string
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
     * @param Compare[]  $compares
     * @param QueryField $mappingConfig
     * @param array      $query
     * @param bool       $exclude
     */
    private function processCompares(array $compares, QueryField $mappingConfig, array &$query, bool $exclude = false)
    {
        $valuesQuery = [];

        foreach ($compares as $comparison) {
            if ($exclude !== ('<>' === $comparison->getOperator())) {
                continue;
            }

            $strategy = $this->getConversionStrategy($mappingConfig, $comparison->getValue());
            $column = $this->queryPlatform->getFieldColumn($mappingConfig, $strategy);

            $valuesQuery[] = sprintf(
                '%s %s %s',
                $column,
                $comparison->getOperator(),
                $this->queryPlatform->getValueAsSql($comparison->getValue(), $mappingConfig, $column, $strategy)
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
     * @param QueryField     $mappingConfig
     * @param array          $query
     * @param bool           $exclude
     */
    private function processPatternMatchers(array $patternMatchers, QueryField $mappingConfig, array &$query, bool $exclude = false)
    {
        foreach ($patternMatchers as $patternMatch) {
            if ($exclude !== $patternMatch->isExclusive()) {
                continue;
            }

            $query[] = $this->queryPlatform->getPatternMatcher(
                $patternMatch,
                $this->queryPlatform->getFieldColumn($mappingConfig)
            );
        }
    }

    private function getConversionStrategy(QueryField $mappingConfig, $value): int
    {
        if ($mappingConfig->valueConversion instanceof StrategySupportedConversion) {
            return $mappingConfig->valueConversion->getConversionStrategy(
                $value,
                $mappingConfig->fieldConfig->getOptions(),
                $this->getConversionHints($mappingConfig)
            );
        }

        if ($mappingConfig->columnConversion instanceof StrategySupportedConversion) {
            return $mappingConfig->columnConversion->getConversionStrategy(
                $value,
                $mappingConfig->fieldConfig->getOptions(),
                $this->getConversionHints($mappingConfig)
            );
        }

        return 0;
    }

    private function getConversionHints(QueryField $mappingConfig, string $column = null): ConversionHints
    {
        $hints = new ConversionHints();
        $hints->field = $mappingConfig;
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

    private function processFieldValues(ValuesBag $values, QueryField $mappingConfig, array &$inclusiveSqlGroup, array &$exclusiveSqlGroup)
    {
        $this->processSingleValues(
            $values->getSimpleValues(),
            $mappingConfig,
            $inclusiveSqlGroup
        );

        $this->processRanges(
            $values->get(Range::class),
            $mappingConfig,
            $inclusiveSqlGroup
        );

        $this->processCompares(
            $values->get(Compare::class),
            $mappingConfig,
            $inclusiveSqlGroup
        );

        $this->processPatternMatchers(
            $values->get(PatternMatch::class),
            $mappingConfig,
            $inclusiveSqlGroup
        );

        $this->processSingleValues(
            $values->getExcludedSimpleValues(),
            $mappingConfig,
            $exclusiveSqlGroup,
            true
        );

        $this->processRanges(
            $values->get(ExcludedRange::class),
            $mappingConfig,
            $exclusiveSqlGroup,
            true
        );

        $this->processPatternMatchers(
            $values->get(PatternMatch::class),
            $mappingConfig,
            $exclusiveSqlGroup,
            true
        );

        $this->processCompares(
            $values->get(Compare::class),
            $mappingConfig,
            $exclusiveSqlGroup,
            true
        );
    }
}
