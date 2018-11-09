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

use Doctrine\ORM\NativeQuery;
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

    /**
     * @param Cache $cacheDriver
     */
    public function __construct(Cache $cacheDriver = null)
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Creates a new ConditionGenerator for the SearchCondition.
     *
     * Conversions are applied using the 'doctrine_dbal_conversion' option (when present).
     *
     * @param NativeQuery|Query|QueryBuilder $query           Doctrine ORM (Native)Query object
     * @param SearchCondition                $searchCondition SearchCondition object
     *
     * @return NativeQueryConditionGenerator|DqlConditionGenerator
     */
    public function createConditionGenerator($query, SearchCondition $searchCondition)
    {
        if ($query instanceof NativeQuery) {
            return new NativeQueryConditionGenerator($query, $searchCondition);
        }

        if ($query instanceof Query || $query instanceof QueryBuilder) {
            return new DqlConditionGenerator($query, $searchCondition);
        }

        throw new \InvalidArgumentException(
            sprintf('Query "%s" is not supported by the DoctrineOrmFactory.', \get_class($query))
        );
    }

    /**
     * Creates a new CachedConditionGenerator instance for the ConditionGenerator.
     *
     * @param DqlConditionGenerator|NativeQueryConditionGenerator $conditionGenerator
     * @param null|int|\DateInterval                              $ttl                Optional. The TTL value of this item. If no value is sent and
     *                                                                                the driver supports TTL then the library may set a default value
     *                                                                                for it or let the driver take care of that.
     */
    public function createCachedConditionGenerator($conditionGenerator, $ttl = null): ConditionGenerator
    {
        if (null === $this->cacheDriver) {
            return $conditionGenerator;
        }

        if ($conditionGenerator instanceof DqlConditionGenerator) {
            return new CachedDqlConditionGenerator($conditionGenerator, $this->cacheDriver, $ttl);
        } elseif ($conditionGenerator instanceof NativeQueryConditionGenerator) {
            return new CachedNativeQueryConditionGenerator($conditionGenerator, $this->cacheDriver, $ttl);
        }

        throw new \InvalidArgumentException(
            sprintf('ConditionGenerator "%s" is not supported by the DoctrineOrmFactory.', \get_class($conditionGenerator))
        );
    }
}
