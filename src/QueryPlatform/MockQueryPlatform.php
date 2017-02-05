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

final class MockQueryPlatform extends AbstractQueryPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getMatchSqlRegex(string $column, string $value, bool $caseInsensitive, bool $negative): string
    {
        return ($negative ? 'NOT ' : '').sprintf(
            "RW_REGEXP(%s, %s, '%s')",
            $value,
            $column,
            ($caseInsensitive ? 'ui' : 'u')
        );
    }
}
