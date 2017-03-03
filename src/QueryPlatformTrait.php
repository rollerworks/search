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

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;

/**
 * @internal
 */
trait QueryPlatformTrait
{
    /**
     * Gets the QueryPlatform based on the connection.
     *
     * @param Connection $connection
     * @param array      $fields
     *
     * @return QueryPlatform
     */
    protected function getQueryPlatform(Connection $connection, array $fields): QueryPlatform
    {
        $dbPlatform = ucfirst($connection->getDatabasePlatform()->getName());
        $platformClass = 'Rollerworks\\Component\\Search\\Doctrine\\Dbal\\QueryPlatform\\'.$dbPlatform.'QueryPlatform';

        if (class_exists($platformClass)) {
            return new $platformClass($connection, $fields);
        }

        throw new \RuntimeException(sprintf('No supported class found for database-platform "%s".', $dbPlatform));
    }
}
