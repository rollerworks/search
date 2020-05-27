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
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
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
     * @var array [field-name][mapping-index] => {QueryField}
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

    public function __construct(Connection $connection, QueryPlatform $queryPlatform, array $fields)
    {
        $this->connection = $connection;
        $this->queryPlatform = $queryPlatform;
        $this->fields = $fields;
    }

    public function getWhereClause(SearchCondition $searchCondition): string
    {
        $conditions = [];

        if (null !== $primaryCondition = $searchCondition->getPrimaryCondition()) {
            $conditions[] = $this->getGroupQuery($primaryCondition->getValuesGroup());
        }

        $conditions[] = $this->getGroupQuery($searchCondition->getValuesGroup());

        return self::implodeWithValue(' AND ', $conditions);
    }

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

            $groupSql[] = self::implodeValuesWithWrapping(' OR ', $inclusiveSqlGroup, '(', ')');
            $groupSql[] = self::implodeValuesWithWrapping(' AND ', $exclusiveSqlGroup, '(', ')');
            $query[] = self::wrapIfNotEmpty(self::implodeWithValue(' AND ', $groupSql), '(', ')');
        }

        $finalQuery = [];

        // Wrap all the fields as a group
        $finalQuery[] = self::wrapIfNotEmpty(
            self::implodeWithValue(' '.strtoupper($valuesGroup->getGroupLogical()).' ', $query),
            '(',
            ')'
        );

        $this->processGroups($valuesGroup->getGroups(), $finalQuery);

        return self::implodeValuesWithWrapping(' AND ', $finalQuery, '(', ')');
    }

    /**
     * @param ValuesGroup[] $groups
     * @param string[]      $query
     */
    private function processGroups(array $groups, array &$query)
    {
        $groupSql = [];

        foreach ($groups as $group) {
            $groupSql[] = $this->getGroupQuery($group);
        }

        $query[] = self::wrapIfNotEmpty(self::implodeWithValue(' OR ', $groupSql), '(', ')');
    }

    private function processSingleValuesInList(array $values, QueryField $mappingConfig, array &$query, bool $exclude = false): void
    {
        $valuesQuery = [];
        $column = $this->queryPlatform->getFieldColumn($mappingConfig);

        foreach ($values as $value) {
            $valuesQuery[] = $this->queryPlatform->getValueAsSql($value, $mappingConfig, $column);
        }

        $patterns = ['%s IN(%s)', '%s NOT IN(%s)'];

        if (\count($valuesQuery) > 0) {
            $query[] = sprintf(
                $patterns[(int) $exclude],
                $column,
                implode(', ', $valuesQuery)
            );
        }
    }

    private function processSingleValues(array $values, QueryField $mappingConfig, array &$query, bool $exclude = false): void
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
     * @param Range[] $ranges
     */
    private function processRanges(array $ranges, QueryField $mappingConfig, array &$query, bool $exclude = false): void
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
     * @return string either "(%s >= %s AND %s <= %s)"
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
     * @param Compare[] $compares
     */
    private function processCompares(array $compares, QueryField $mappingConfig, array &$query, bool $exclude = false): void
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

        if (\count($valuesQuery) > 1 && !$exclude) {
            $query[] = self::implodeValuesWithWrapping(' AND ', $valuesQuery, '(', ')');
        } else {
            $query[] = self::implodeWithValue(' AND ', $valuesQuery);
        }
    }

    /**
     * @param PatternMatch[] $patternMatchers
     * @param string[]       $query
     */
    private function processPatternMatchers(array $patternMatchers, QueryField $mappingConfig, array &$query, bool $exclude = false): void
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

    /**
     * @param string[] $values
     */
    private static function implodeWithValue(string $glue, array $values): string
    {
        // Remove the empty values
        $values = array_filter($values, function (string $val): bool {
            return $val !== '';
        });

        if (0 === \count($values)) {
            return '';
        }

        return implode($glue, $values);
    }

    private static function implodeValuesWithWrapping(string $glue, array $values, string $prefix, string $suffix): string
    {
        // Remove the empty values
        $values = array_filter($values, function (string $val): bool {
            return '' !== $val;
        });

        if (0 === \count($values)) {
            return '';
        }

        $value = implode($glue, $values);

        if (\count($values) > 1) {
            return $prefix.$value.$suffix;
        }

        return $value;
    }

    private static function wrapIfNotEmpty(string $value, string $prefix, string $suffix)
    {
        if ('' === $value) {
            return '';
        }

        return $prefix.$value.$suffix;
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
