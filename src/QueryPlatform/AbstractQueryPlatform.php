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
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatformInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\Value\PatternMatch;

abstract class AbstractQueryPlatform implements QueryPlatformInterface
{
    /**
     * @var QueryField[]
     */
    protected $fields = [];

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
     * @param Connection   $connection
     * @param QueryField[] $fields
     */
    public function __construct(Connection $connection, array $fields)
    {
        $this->connection = $connection;
        $this->fields = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueAsSql($value, QueryField $mappingConfig, string $column, int $strategy = 0)
    {
        if ($mappingConfig->valueConversion) {
            return $this->convertValue($value, $mappingConfig, $column, $strategy);
        }

        return $this->quoteValue(
            $mappingConfig->dbType->convertToDatabaseValue($value, $this->connection->getDatabasePlatform()),
            $mappingConfig->dbType
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldColumn(QueryField $mappingConfig, int $strategy = 0): string
    {
        $mappingName = $mappingConfig->mappingName;

        if (isset($this->fieldsMappingCache[$mappingName][$strategy])) {
            return $this->fieldsMappingCache[$mappingName][$strategy];
        }

        $column = $mappingConfig->column;
        $this->fieldsMappingCache[$mappingName][$strategy] = $column;

        if ($mappingConfig->fieldConversion instanceof SqlFieldConversionInterface) {
            $this->fieldsMappingCache[$mappingName][$strategy] = $mappingConfig->fieldConversion->convertSqlField(
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
    public function convertSqlValue($value, QueryField $mappingConfig, string $column, int $strategy = 0)
    {
        return $mappingConfig->valueConversion->convertSqlValue(
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
        // Don't quote numbers as some don't follow standards for casting
        if (is_scalar($value) && ctype_digit((string) $value)) {
            return (string) $value;
        }

        return $this->connection->quote($value,
            $type->getBindingType()
        );
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
     * @param int        $strategy
     *
     * @return ConversionHints
     */
    protected function getConversionHints($mappingConfig, $column, $strategy = 0): ConversionHints
    {
        $hints = new ConversionHints();
        $hints->field = $mappingConfig;
        $hints->column = $column;
        $hints->connection = $this->connection;
        $hints->conversionStrategy = $strategy;

        return $hints;
    }

    /**
     * @param mixed      $value
     * @param QueryField $mappingConfig
     * @param string     $column
     * @param int        $strategy
     *
     * @return string
     */
    protected function convertValue($value, QueryField $mappingConfig, string $column, int $strategy = 0)
    {
        $options = $mappingConfig->fieldConfig->getOptions();

        $hints = $this->getConversionHints($mappingConfig, $column, $strategy);
        $convertedValue = $mappingConfig->valueConversion->convertValue($value, $options, $hints);

        if ($mappingConfig->valueConversion instanceof SqlValueConversionInterface) {
            return $this->convertSqlValue($convertedValue, $mappingConfig, $column, $strategy);
        }

        return $this->quoteValue($convertedValue, $mappingConfig->dbType);
    }
}
