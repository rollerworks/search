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

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Value\PatternMatch;

interface QueryPlatformInterface
{
    /**
     * Returns the correct column (with SQLField conversions applied).
     *
     * @param QueryField $mappingConfig
     * @param int        $strategy
     *
     * @return string
     */
    public function getFieldColumn(QueryField $mappingConfig, int $strategy = 0): string;

    /**
     * Returns either the converted value.
     *
     * @param mixed      $value
     * @param QueryField $mappingConfig
     * @param string     $column
     * @param int        $strategy
     *
     * @return string
     */
    public function getValueAsSql($value, QueryField $mappingConfig, string $column, int $strategy = 0): string;

    /**
     * Returns the formatted PatternMatch query.
     *
     * @param PatternMatch $patternMatch
     * @param string       $column
     *
     * @return string Some like: u.name LIKE '%foo%'
     */
    public function getPatternMatcher(PatternMatch $patternMatch, string $column): string;

    /**
     * @param mixed      $value
     * @param QueryField $mappingConfig
     * @param string     $column
     * @param int        $strategy
     *
     * @return string
     */
    public function convertSqlValue($value, QueryField $mappingConfig, string $column, int $strategy = 0): string;

    /**
     * Returns the SQL for the match (regexp).
     *
     * @param string $column
     * @param string $value           Fully escaped value or parameter-name
     * @param bool   $caseInsensitive Is the match case insensitive
     * @param bool   $negative        Is the match negative (exclude)
     *
     * @return string
     */
    public function getMatchSqlRegex(string $column, string $value, bool $caseInsensitive, bool $negative): string;
}
