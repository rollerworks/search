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
    private $cacheDriver;

    public function __construct(Cache $cacheDriver = null)
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Creates a new SqlConditionGenerator for the SearchCondition.
     *
     * Conversions are applied using the 'doctrine_dbal_conversion' option.
     *
     * @param Connection $connection Doctrine DBAL Connection
     */
    public function createConditionGenerator(Connection $connection, SearchCondition $searchCondition): ConditionGenerator
    {
        return new SqlConditionGenerator($connection, $searchCondition);
    }

    /**
     * Creates a new CachedConditionGenerator instance for the given ConditionGenerator.
     *
     * Note: When no cache driver was configured the original ConditionGenerator
     * is returned instead.
     *
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                    the driver supports TTL then the library may set a default value
     *                                    for it or let the driver take care of that.
     */
    public function createCachedConditionGenerator(ConditionGenerator $conditionGenerator, $ttl = 0): ConditionGenerator
    {
        if (null === $this->cacheDriver) {
            return $conditionGenerator;
        }

        return new CachedConditionGenerator($conditionGenerator, $this->cacheDriver, $ttl);
    }
}
