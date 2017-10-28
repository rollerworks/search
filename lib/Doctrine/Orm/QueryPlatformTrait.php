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
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform\SqlQueryPlatform;

/**
 * @internal
 */
trait QueryPlatformTrait
{
    protected function getQueryPlatform(Connection $connection): QueryPlatform
    {
        $dbPlatform = ucfirst($connection->getDatabasePlatform()->getName());
        $platformClass = 'Rollerworks\\Component\\Search\\Doctrine\\Dbal\\QueryPlatform\\'.$dbPlatform.'QueryPlatform';

        if (class_exists($platformClass)) {
            return new $platformClass($connection);
        }

        return new SqlQueryPlatform($connection);
    }
}
