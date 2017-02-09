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
final class DoctrineDbalFactory
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
     * Creates a new SqlConditionGenerator for the SearchCondition.
     *
     * Conversions are applied using the 'doctrine_dbal_conversion' option.
     *
     * @param Connection      $connection      Doctrine DBAL Connection object
     * @param SearchCondition $searchCondition SearchCondition object
     *
     * @return ConditionGenerator
     */
    public function createConditionGenerator(Connection $connection, SearchCondition $searchCondition): ConditionGenerator
    {
        return new SqlConditionGenerator($connection, $searchCondition);
    }

    /**
     * Creates a new CachedConditionGenerator instance for the given ConditionGenerator.
     *
     * @param ConditionGenerator $whereBuilder
     * @param int                $lifetime
     *
     * @return ConditionGenerator
     */
    public function createCachedConditionGenerator(ConditionGenerator $whereBuilder, int $lifetime = 0): ConditionGenerator
    {
        if (null === $this->cacheDriver) {
            return $whereBuilder;
        }

        return new CachedConditionGenerator($whereBuilder, $this->cacheDriver, $lifetime);
    }
}
