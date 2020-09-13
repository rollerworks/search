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

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\SearchCondition;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @final
 */
class DoctrineOrmFactory
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
     * Creates a new ConditionGenerator for the SearchCondition.
     *
     * Conversions are applied using the 'doctrine_dbal_conversion' option (when present).
     *
     * @param Query|QueryBuilder $query
     */
    public function createConditionGenerator($query, SearchCondition $searchCondition): DqlConditionGenerator
    {
        return new DqlConditionGenerator($query, $searchCondition);
    }

    /**
     * Creates a new CachedConditionGenerator instance for the ConditionGenerator.
     *
     * @param int|\DateInterval|null $ttl Optional. The TTL value of this item. If no value is sent and
     *                                    the driver supports TTL then the library may set a default value
     *                                    for it or let the driver take care of that.
     */
    public function createCachedConditionGenerator(ConditionGenerator $conditionGenerator, $ttl = null): ConditionGenerator
    {
        if (null === $this->cacheDriver) {
            return $conditionGenerator;
        }

        return new CachedDqlConditionGenerator($conditionGenerator, $this->cacheDriver, $ttl);
    }
}
