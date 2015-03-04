<?php

/**
 * PhpStorm.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

class MssqlQueryPlatform extends AbstractQueryPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getMatchSqlRegex($column, $value, $caseInsensitive, $negative)
    {
        throw new \RuntimeException(
            "MSSQL doesn't have support for regexes out-of-the box.\n".
            "It's possible to support simple regexes, but we need of help of someone with experience in MSSQL."
        );
    }

    /**
     * Returns the list of characters to escape.
     *
     * @return string
     */
    protected function getLikeEscapeChars()
    {
        return '%_[]';
    }
}
