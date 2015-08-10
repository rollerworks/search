<?php

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
    protected $fields = array();

    /**
     * @var array[]
     */
    protected $fieldsMappingCache = array();

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
    public function getValueAsSql($value, $fieldName, $column, $strategy = 0)
    {
        $converter = $this->fields[$fieldName]->getValueConversion();
        $type = $this->fields[$fieldName]->getDbType();

        if (null !== $converter) {
            return $this->convertValue($value, $fieldName, $column, $strategy);
        }

        return $this->quoteValue(
            $type->convertToDatabaseValue($value, $this->connection->getDatabasePlatform()),
            $type
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldColumn($fieldName, $strategy = 0, $column = '')
    {
        if (isset($this->fieldsMappingCache[$fieldName][$strategy])) {
            return $this->fieldsMappingCache[$fieldName][$strategy];
        }

        $field = $this->fields[$fieldName];

        if ('' === $column) {
            $column = $field->getColumn();
        }

        $this->fieldsMappingCache[$fieldName][$strategy] = $column;

        if ($field->getFieldConversion() instanceof SqlFieldConversionInterface) {
            $this->fieldsMappingCache[$fieldName][$strategy] = $field->getFieldConversion()->convertSqlField(
                $column,
                $field->getFieldConfig()->getOptions(),
                $this->getConversionHints($fieldName, $column, $strategy)
            );
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }

    /**
     * {@inheritdoc}
     */
    public function getPatternMatcher(PatternMatch $patternMatch, $column)
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

        $patternMap = array(
            PatternMatch::PATTERN_STARTS_WITH => '%%%s',
            PatternMatch::PATTERN_NOT_STARTS_WITH => '%%%s',
            PatternMatch::PATTERN_CONTAINS => '%%%s%%',
            PatternMatch::PATTERN_NOT_CONTAINS => '%%%s%%',
            PatternMatch::PATTERN_ENDS_WITH => '%s%%',
            PatternMatch::PATTERN_NOT_ENDS_WITH => '%s%%',
        );

        $value = addcslashes($patternMatch->getValue(), $this->getLikeEscapeChars());
        $value = $this->connection->quote(sprintf($patternMap[$patternMatch->getType()], $value));
        $escape = $this->connection->quote('\\');

        if ($patternMatch->isCaseInsensitive()) {
            $column = "LOWER($column)";
            $value = "LOWER($value)";
        }

        return $column.($patternMatch->isExclusive() ? ' NOT' : '')." LIKE $value ESCAPE $escape";
    }

    /**
     * {@inheritdoc}
     */
    public function convertSqlValue($value, $fieldName, $column, $strategy = 0)
    {
        $field = $this->fields[$fieldName];

        return $field->getValueConversion()->convertSqlValue(
            $value,
            $field->getFieldConfig()->getOptions(),
            $this->getConversionHints($fieldName, $column, $strategy)
        );
    }

    /**
     * @param mixed $value
     * @param Type  $type
     *
     * @return string
     */
    protected function quoteValue($value, Type $type)
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
    protected function getLikeEscapeChars()
    {
        return '%_';
    }

    /**
     * @param string $fieldName
     * @param string $column
     * @param int    $strategy
     *
     * @return ConversionHints
     */
    protected function getConversionHints($fieldName, $column, $strategy = 0)
    {
        $hints = new ConversionHints();
        $hints->field = $this->fields[$fieldName];
        $hints->column = $column;
        $hints->connection = $this->connection;
        $hints->conversionStrategy = $strategy;

        return $hints;
    }

    /**
     * @param mixed  $value
     * @param string $fieldName
     * @param string $column
     * @param int    $strategy
     *
     * @return string
     */
    protected function convertValue($value, $fieldName, $column, $strategy = 0)
    {
        $field = $this->fields[$fieldName];
        $converter = $field->getValueConversion();
        $options = $field->getFieldConfig()->getOptions();
        $type = $field->getDbType();

        $convertedValue = $value;
        $hints = $this->getConversionHints($fieldName, $column, $strategy);

        if ($converter->requiresBaseConversion($value, $options, $hints)) {
            $convertedValue = $type->convertToDatabaseValue($value, $this->connection->getDatabasePlatform());
        }

        $convertedValue = $converter->convertValue($convertedValue, $options, $hints);

        if ($converter instanceof SqlValueConversionInterface) {
            return $this->convertSqlValue($convertedValue, $fieldName, $column, $strategy);
        }

        return $this->quoteValue($convertedValue, $type);
    }
}
