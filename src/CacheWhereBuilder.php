<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/***
 * Handles caching of the Doctrine DBAL WhereBuilder.
 *
 * Note. For best performance caching of the WhereClause should be done on a
 * per user-session fieldset basis. This ensures enough uniqueness and
 * no complex serialization.
 *
 * This checks if there is a cached result, if not it delegates
 * the creating to the parent and caches the result.
 *
 * Instead of calling getWhereClause() on the WhereBuilder class
 * you should call getWhereClause() on this class instead.
 *
 * WARNING. Any changes to the mapping-data should invalidate the cache
 * the system does not do this automatically.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CacheWhereBuilder extends AbstractCacheWhereBuilder implements WhereBuilderInterface
{
    /**
     * Constructor.
     *
     * @param WhereBuilderInterface $whereBuilder The WhereBuilder to use for generating and updating the query
     * @param Cache                 $cacheDriver  Doctrine Cache instance
     * @param int                   $lifeTime     Lifetime in seconds after which the cache is expired
     *
     * @throws UnexpectedTypeException when the whereBuilder is invalid
     */
    public function __construct(WhereBuilderInterface $whereBuilder, Cache $cacheDriver, $lifeTime = 0)
    {
        $this->cacheDriver = $cacheDriver;
        $this->cacheLifeTime = (int) $lifeTime;
        $this->whereBuilder = $whereBuilder;
    }

    /**
     * Returns the generated/cached where-clause.
     *
     * @see WhereBuilder::getWhereClause()
     *
     * @param bool $embedValues Whether to embed the values, default is to assign as parameters.
     *
     * @return string
     */
    public function getWhereClause()
    {
        if ($this->whereClause) {
            return $this->whereClause;
        }

        $cacheKey = 'rw_search.doctrine.dbal.where.'.$this->cacheKey.($this->keySuffix ? '_'.$this->keySuffix : '');

        if ($this->cacheDriver->contains($cacheKey)) {
            $data = $this->cacheDriver->fetch($cacheKey);

            $this->whereClause = $data;
        } else {
            $this->whereClause = $this->whereBuilder->getWhereClause();

            $this->cacheDriver->save(
                $cacheKey,
                $this->whereClause,
                $this->cacheLifeTime
            );
        }

        return $this->whereClause;
    }
}
