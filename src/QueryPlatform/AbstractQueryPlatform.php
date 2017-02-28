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

namespace Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;
use Rollerworks\Component\Search\Value\PatternMatch;

/**
 * The AbstractQueryPlatform provides a shared base functionality
 * for all the query platforms.
 *
 * Note that is class is also used by the Doctrine ORM processor and therefor
 * methods and properties must be protected and easy to overwrite.
 */
abstract class AbstractQueryPlatform implements QueryPlatform
{
    /**
     * @var array[]
     */
    protected $fieldsMappingCache = [];

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueAsSql($value, QueryField $mappingConfig, string $column, $strategy = 0): string
    {
        if ($mappingConfig->valueConversion) {
            return $this->convertSqlValue($value, $mappingConfig, $column, $strategy);
        }

        return (string) $this->quoteValue(
            $mappingConfig->dbType->convertToDatabaseValue($value, $this->connection->getDatabasePlatform()),
            $mappingConfig->dbType
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldColumn(QueryField $mappingConfig, $strategy = 0, string $column = null): string
    {
        $mappingName = $mappingConfig->mappingName;

        if (isset($this->fieldsMappingCache[$mappingName][$strategy])) {
            return $this->fieldsMappingCache[$mappingName][$strategy];
        }

        if (null === $column) {
            $column = $mappingConfig->column;
        }

        $this->fieldsMappingCache[$mappingName][$strategy] = $column;

        if ($mappingConfig->columnConversion instanceof ColumnConversion) {
            $this->fieldsMappingCache[$mappingName][$strategy] = $mappingConfig->columnConversion->convertColumn(
                $column,
                $mappingConfig->fieldConfig->getOptions(),
                $this->getConversionHints($mappingConfig, $column, $strategy)
            );
        }

        return $this->fieldsMappingCache[$mappingName][$strategy];
    }

    /**
     * {@inheritdoc}
     */
    public function getPatternMatcher(PatternMatch $patternMatch, string $column): string
    {
        if ($patternMatch->isRegex()) {
            return $this->getMatchSqlRegex(
                $column,
                $this->connection->quote($patternMatch->getValue()),
                $patternMatch->isCaseInsensitive(),
                $patternMatch->isExclusive()
            );
        }

        if (in_array($patternMatch->getType(), [PatternMatch::PATTERN_EQUALS, PatternMatch::PATTERN_NOT_EQUALS], true)) {
            $value = $this->connection->quote($patternMatch->getValue());

            if ($patternMatch->isCaseInsensitive()) {
                $column = "LOWER($column)";
                $value = "LOWER($value)";
            }

            return $column.($patternMatch->isExclusive() ? ' <>' : ' =')." $value";
        }

        $patternMap = [
            PatternMatch::PATTERN_STARTS_WITH => '%%%s',
            PatternMatch::PATTERN_NOT_STARTS_WITH => '%%%s',
            PatternMatch::PATTERN_CONTAINS => '%%%s%%',
            PatternMatch::PATTERN_NOT_CONTAINS => '%%%s%%',
            PatternMatch::PATTERN_ENDS_WITH => '%s%%',
            PatternMatch::PATTERN_NOT_ENDS_WITH => '%s%%',
        ];

        $value = addcslashes($patternMatch->getValue(), $this->getLikeEscapeChars());
        $value = $this->quoteValue(sprintf($patternMap[$patternMatch->getType()], $value), Type::getType('text'));
        $escape = $this->quoteValue('\\', Type::getType('text'));

        if ($patternMatch->isCaseInsensitive()) {
            $column = "LOWER($column)";
            $value = "LOWER($value)";
        }

        return $column.($patternMatch->isExclusive() ? ' NOT' : '')." LIKE $value ESCAPE $escape";
    }

    /**
     * {@inheritdoc}
     */
    public function convertSqlValue($value, QueryField $mappingConfig, string $column, $strategy = 0): string
    {
        return (string) $mappingConfig->valueConversion->convertValue(
            $value,
            $mappingConfig->fieldConfig->getOptions(),
            $this->getConversionHints($mappingConfig, $column, $strategy)
        );
    }

    /**
     * @param mixed $value
     * @param Type  $type
     *
     * @return string
     */
    protected function quoteValue($value, Type $type): string
    {
        return (string) $this->connection->quote($value, $type->getBindingType());
    }

    /**
     * Returns the list of characters to escape.
     *
     * @return string
     */
    protected function getLikeEscapeChars(): string
    {
        return '%_';
    }

    /**
     * @param QueryField $mappingConfig
     * @param string     $column
     * @param string|int $strategy
     *
     * @return ConversionHints
     */
    protected function getConversionHints(QueryField $mappingConfig, string $column, $strategy = 0): ConversionHints
    {
        $hints = new ConversionHints();
        $hints->field = $mappingConfig;
        $hints->column = $column;
        $hints->connection = $this->connection;
        $hints->conversionStrategy = $strategy;

        return $hints;
    }
}
