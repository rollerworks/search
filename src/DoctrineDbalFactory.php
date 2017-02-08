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

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\SearchCondition;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DoctrineDbalFactory
{
    /**
     * @var Cache
     */
    protected $cacheDriver;

    /**
     * Constructor.
     *
     * @param Cache $cacheDriver
     */
    public function __construct(Cache $cacheDriver = null)
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Creates a new WhereBuilder for the SearchCondition.
     *
     * Conversions are applied using the 'doctrine_dbal_conversion' option.
     *
     * @param Connection      $connection      Doctrine DBAL Connection object
     * @param SearchCondition $searchCondition SearchCondition object
     *
     * @return WhereBuilderInterface
     */
    public function createWhereBuilder(Connection $connection, SearchCondition $searchCondition): WhereBuilderInterface
    {
        return new WhereBuilder($connection, $searchCondition);
    }

    /**
     * Creates a new CacheWhereBuilder instance for the given WhereBuilder.
     *
     * @param WhereBuilderInterface $whereBuilder
     * @param int                   $lifetime
     *
     * @return WhereBuilderInterface
     */
    public function createCacheWhereBuilder(WhereBuilderInterface $whereBuilder, int $lifetime = 0): WhereBuilderInterface
    {
        if (null === $this->cacheDriver) {
            return $whereBuilder;
        }

        return new CacheWhereBuilder($whereBuilder, $this->cacheDriver, $lifetime);
    }
}
