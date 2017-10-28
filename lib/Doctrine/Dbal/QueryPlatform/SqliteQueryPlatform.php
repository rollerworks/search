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

use Doctrine\DBAL\Types\Type;

final class SqliteQueryPlatform extends AbstractQueryPlatform
{
    /**
     * {@inheritdoc}
     */
    protected function quoteValue($value, Type $type): string
    {
        // Don't quote numbers as SQLite doesn't follow the standards.
        // PDO::quote() should not actually quote them for int, but it does.
        if (is_scalar($value) && ctype_digit((string) $value)) {
            return (string) $value;
        }

        return $this->connection->quote($value, $type->getBindingType());
    }
}
