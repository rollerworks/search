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

class MssqlQueryPlatform extends AbstractQueryPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getMatchSqlRegex(string $column, string $value, bool $caseInsensitive, bool $negative): string
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
    protected function getLikeEscapeChars(): string
    {
        return '%_[]';
    }
}
