<?php

/**
 * PhpStorm.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

class OracleQueryPlatform extends AbstractQueryPlatform
{
    /**
     * {@inheritdoc}
     */
    protected function getMatchSqlRegex($column, $value, $caseInsensitive, $negative)
    {
        return ($negative ? 'NOT ' : '').sprintf(
            "REGEXP_LIKE(%s, %s, '%s')",
            $column,
            $value,
            ($caseInsensitive ? 'i' : 'c')
        );
    }
}
