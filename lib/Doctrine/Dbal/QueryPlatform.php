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

interface QueryPlatform
{
    /**
     * Returns the correct column (with a SQLField conversion applied).
     */
    public function getFieldColumn(QueryField $mappingConfig, int $strategy = 0, string $column = null): string;

    /**
     * Returns either the converted value.
     *
     * @param mixed $value
     */
    public function getValueAsSql($value, QueryField $mappingConfig, string $column, int $strategy = 0): string;

    /**
     * Returns the formatted PatternMatch query.
     *
     * @return string Some like: u.name LIKE '%foo%'
     */
    public function getPatternMatcher(PatternMatch $patternMatch, string $column): string;

    /**
     * @param mixed $value
     */
    public function convertSqlValue($value, QueryField $mappingConfig, string $column, int $strategy = 0): string;
}
