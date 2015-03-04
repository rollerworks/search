<?php

/**
 * PhpStorm.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

final class MockQueryPlatform extends AbstractQueryPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getMatchSqlRegex($column, $value, $caseInsensitive, $negative)
    {
        return ($negative ? 'NOT ' : '').sprintf(
            "RW_REGEXP(%s, %s, '%s')",
            $value,
            $column,
            ($caseInsensitive ? 'ui' : 'u')
        );
    }
}
