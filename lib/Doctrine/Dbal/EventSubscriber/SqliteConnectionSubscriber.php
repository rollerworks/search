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

namespace Rollerworks\Component\Search\Doctrine\Dbal\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

final class SqliteConnectionSubscriber implements EventSubscriber
{
    public function postConnect(ConnectionEventArgs $args): void
    {
        if ('sqlite' === $args->getDatabasePlatform()->getName()) {
            /** @var PDOConnection $conn */
            $conn = $args->getConnection()->getWrappedConnection();
            $conn->sqliteCreateFunction(
                'search_conversion_age',
                function ($date) {
                    return date_create($date)->diff(new \DateTime())->y;
                },
                1
            );
        }
    }

    public function getSubscribedEvents(): array
    {
        return [Events::postConnect];
    }
}
