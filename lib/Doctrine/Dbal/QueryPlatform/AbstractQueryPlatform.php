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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Value\PatternMatch;

/**
 * The AbstractQueryPlatform provides a shared base functionality
 * for all the query platforms.
 *
 * Note that is class is also used by the Doctrine ORM processor and therefore
 * methods and properties must be protected and easy to overwrite.
 */
abstract class AbstractQueryPlatform
{
    /**
     * @var Connection
     */
    protected $connection;

    /** @var ArrayCollection<string,array> */
    private $parameters;

    /** @var int */
    private $parameterIdx = -1;

    public string $platformName;

    public function __construct(Connection $connection, string $platformName)
    {
        $this->connection = $connection;
        $this->parameters = new ArrayCollection();
        $this->platformName = $platformName;
    }

    public function getValueAsSql($value, QueryField $mappingConfig, ConversionHints $hints): string
    {
        if ($mappingConfig->valueConversion !== null) {
            return $mappingConfig->valueConversion->convertValue(
                $value,
                $mappingConfig->fieldConfig->getOptions(),
                $hints
            );
        }

        return $this->createParamReferenceFor($value, $mappingConfig->dbTypeName);
    }

    public function createParamReferenceFor($value, ?string $type = null): string
    {
        $name = 'search_' . (++$this->parameterIdx);
        $this->parameters->set($name, [$value, $type]);

        return ':' . $name;
    }

    public function getFieldColumn(QueryField $mappingConfig, string $column, ConversionHints $hints): string
    {
        if ($mappingConfig->columnConversion !== null) {
            return $mappingConfig->columnConversion->convertColumn(
                $column,
                $mappingConfig->fieldConfig->getOptions(),
                $hints
            );
        }

        return $column;
    }

    public function getPatternMatcher(PatternMatch $patternMatch, string $column): string
    {
        if (\in_array($patternMatch->getType(), [PatternMatch::PATTERN_EQUALS, PatternMatch::PATTERN_NOT_EQUALS], true)) {
            $value = $this->createParamReferenceFor($patternMatch->getValue(), 'text');

            if ($patternMatch->isCaseInsensitive()) {
                $column = "LOWER({$column})";
                $value = "LOWER({$value})";
            }

            return $column . ($patternMatch->isExclusive() ? ' <>' : ' =') . " {$value}";
        }

        $patternMap = [
            PatternMatch::PATTERN_STARTS_WITH => ['%s', "'%%'"],
            PatternMatch::PATTERN_NOT_STARTS_WITH => ['%s', "'%%'"],
            PatternMatch::PATTERN_CONTAINS => ["'%%'", '%s', "'%%'"],
            PatternMatch::PATTERN_NOT_CONTAINS => ["'%%'", '%s', "'%%'"],
            PatternMatch::PATTERN_ENDS_WITH => ["'%%'", '%s'],
            PatternMatch::PATTERN_NOT_ENDS_WITH => ["'%%'", '%s'],
        ];

        $value = addcslashes($patternMatch->getValue(), $this->getLikeEscapeChars());
        $value = \sprintf($this->connection->getDatabasePlatform()->getConcatExpression(...$patternMap[$patternMatch->getType()]), $this->createParamReferenceFor($value, 'text'));

        if ($patternMatch->isCaseInsensitive()) {
            $column = "LOWER({$column})";
            $value = "LOWER({$value})";
        }

        return $column . ($patternMatch->isExclusive() ? ' NOT' : '') . " LIKE {$value}";
    }

    /**
     * Returns the list of characters to escape (per character).
     */
    protected function getLikeEscapeChars(): string
    {
        return '%_';
    }

    public function getParameters(): ArrayCollection
    {
        return $this->parameters;
    }
}
