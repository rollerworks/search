<?php

/**
 * PhpStorm.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

class MysqlQueryPlatform extends AbstractQueryPlatform
{
    /**
     * {@inheritdoc}
     */
    protected function getMatchSqlRegex($column, $value, $caseInsensitive, $negative)
    {
        return sprintf(
            '%s%s REGEXP%s %s',
            $column,
            ($negative ? ' NOT' : ''),
            ($caseInsensitive ? ' BINARY' : ''),
            $value
        );
    }
}
