<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\NativeQuery;

class CacheNativeWhereBuilder extends AbstractCacheWhereBuilder implements WhereBuilderInterface
{
    /**
     * @var NativeQuery
     */
    private $query;

    /**
     * Constructor.
     *
     * @param NativeWhereBuilder $whereBuilder The WhereBuilder to use for generating and updating the query
     * @param Cache              $cacheDriver  Doctrine Cache instance
     * @param int                $lifeTime     Lifetime in seconds after which the cache is expired
     *                                         Set this 0 to never expire.
     */
    public function __construct(NativeWhereBuilder $whereBuilder, Cache $cacheDriver, $lifeTime = 0)
    {
        parent::__construct($cacheDriver, $lifeTime);

        $this->cacheDriver = $cacheDriver;
        $this->cacheLifeTime = (int) $lifeTime;
        $this->whereBuilder = $whereBuilder;
        $this->query = $whereBuilder->getQuery();
    }

    /**
     * Returns the generated/cached where-clause.
     *
     * @param string $prependQuery Prepends this string to the where-clause
     *                             ("WHERE" or "AND" for example)
     *
     * @return string
     */
    public function getWhereClause($prependQuery = ' WHERE ')
    {
        if (null === $this->whereClause) {
            $cacheKey = 'rw_search.doctrine.orm.where.nat.'.$this->cacheKey;

            if ($this->cacheDriver->contains($cacheKey)) {
                $this->whereClause = $this->cacheDriver->fetch($cacheKey);
            } else {
                $this->whereClause = $this->whereBuilder->getWhereClause();

                $this->cacheDriver->save(
                    $cacheKey,
                    $this->whereClause,
                    $this->cacheLifeTime
                );
            }
        }

        if ('' !== $this->whereClause) {
            return $prependQuery.$this->whereClause;
        }

        return '';
    }

    /**
     * Updates the configured query object with the where-clause.
     *
     * @param string $prependQuery Prepends this string to the where-clause
     *                             ("WHERE" or "AND" for example)
     *
     * @return self
     */
    public function updateQuery($prependQuery = ' WHERE ')
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ($whereCase !== '') {
            $this->query->setSql($this->query->getSQL().$whereCase);
        }

        return $this;
    }
}
