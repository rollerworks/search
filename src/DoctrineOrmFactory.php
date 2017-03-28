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
     * Constructor.
     *
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
     * @param NativeQuery|Query $query           Doctrine ORM (Native)Query object
     * @param SearchCondition   $searchCondition SearchCondition object
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
            sprintf('Query "%s" is not supported by the DoctrineOrmFactory.', get_class($query))
        );
    }

    /**
     * Creates a new CachedConditionGenerator instance for the ConditionGenerator.
     *
     * @param DqlConditionGenerator|NativeQueryConditionGenerator $conditionGenerator
     * @param int                                                 $lifetime           Lifetime in seconds after which the cache is expired.
     *                                                                                Set this 0 to never expire (not recommended)
     *
     * @return ConditionGenerator
     */
    public function createCachedConditionGenerator($conditionGenerator, int $lifetime = null): ConditionGenerator
    {
        if (null === $this->cacheDriver) {
            return $conditionGenerator;
        }

        if ($conditionGenerator instanceof DqlConditionGenerator) {
            return new CachedDqlConditionGenerator($conditionGenerator, $this->cacheDriver, $lifetime);
        } elseif ($conditionGenerator instanceof NativeQueryConditionGenerator) {
            return new CachedNativeQueryConditionGenerator($conditionGenerator, $this->cacheDriver, $lifetime);
        }

        throw new \InvalidArgumentException(
            sprintf('ConditionGenerator "%s" is not supported by the DoctrineOrmFactory.', get_class($conditionGenerator))
        );
    }
}
