<?php

/**
 * PhpStorm.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

final class PostgresqlQueryPlatform extends AbstractQueryPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getMatchSqlRegex($column, $value, $caseInsensitive, $negative)
    {
        return sprintf(
            '%s %s~%s %s',
            $column,
            ($negative ? '!' : ''),
            ($caseInsensitive ? '*' : ''),
            $value
        );
    }
}
