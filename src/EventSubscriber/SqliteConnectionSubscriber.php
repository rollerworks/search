<?php

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
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

final class SqliteConnectionSubscriber implements EventSubscriber
{
    /**
     * @param ConnectionEventArgs $args
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        if ('sqlite' === $args->getDatabasePlatform()->getName()) {
            $conn = $args->getConnection()->getWrappedConnection();

            $conn->sqliteCreateFunction(
                'search_conversion_age',
                function ($date) {
                    return date_create($date)->diff(new \DateTime())->y;
                },
                1
            );

            $conn->sqliteCreateFunction(
                'RW_REGEXP',
                function ($pattern, $string, $flags) {
                    return (preg_match('{'.$pattern.'}'.$flags, $string) > 0) ? 1 : 0;
                },
                3
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(Events::postConnect);
    }
}
