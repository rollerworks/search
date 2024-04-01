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

namespace Rollerworks\Component\Search\Doctrine\Dbal\Tests\DbalExtensions;

/**
 * @internal
 *
 * @see \Doctrine\Tests\DbalExtensions\QueryLog
 */
final class QueryLog
{
    /** @var list<array{sql: string, params: array|null, types: array|null}> */
    public $queries = [];

    /** @var bool */
    private $enabled = false;

    public function logQuery(string $sql, ?array $params = null, ?array $types = null): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ];
    }

    /** @return $this */
    public function reset(): self
    {
        $this->enabled = false;
        $this->queries = [];

        return $this;
    }

    /** @return $this */
    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    /** @return $this */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }
}
