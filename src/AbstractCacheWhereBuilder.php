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

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Rollerworks\Component\Search\Exception\BadMethodCallException;

/***
 * Handles caching of the Doctrine ORM WhereBuilder.
 *
 * This checks if there is a cached result, if not it delegates
 * the creating to the parent and caches the result.
 *
 * Instead of calling getWhereClause()/updateQuery() on the WhereBuilder
 * class you should call getWhereClause()/updateQuery() on this class instead.
 *
 * WARNING. Any changes to the entities mapping should invalidate the cache
 * the system does not do this automatically.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractCacheWhereBuilder implements WhereBuilderInterface
{
    /**
     * @var Cache
     */
    protected $cacheDriver;

    /**
     * @var int
     */
    protected $cacheLifeTime;

    /**
     * @var WhereBuilderInterface
     */
    protected $whereBuilder;

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @var string
     */
    protected $whereClause;

    /**
     * Constructor.
     *
     * @param Cache $cacheDriver Doctrine Cache instance
     * @param int   $lifeTime    Lifetime in seconds after which the cache is expired
     *                           Set this 0 to never expire
     */
    public function __construct(Cache $cacheDriver, $lifeTime = 0)
    {
        $this->cacheDriver = $cacheDriver;
        $this->cacheLifeTime = (int) $lifeTime;
    }

    /**
     * Set the cache key.
     *
     * This method also accepts a callback that can calculate the key for you.
     * The callback will receive the WhereBuilder.
     *
     * @param string   $key
     * @param callable $callback
     *
     * @throws BadMethodCallException
     *
     * @return self
     */
    public function setCacheKey($key = null, $callback = null)
    {
        if ((null === $key && null === $callback) || ($callback && !is_callable($callback))) {
            throw new BadMethodCallException('Either a key or legal callback must be given.');
        }

        if ($callback) {
            $key = call_user_func($callback, $this->whereBuilder);
        }

        $this->cacheKey = (string) $key;

        return $this;
    }

    /**
     * Returns the original WhereBuilder that is used for generating
     * the where-clause.
     *
     * @return WhereBuilderInterface
     */
    public function getInnerWhereBuilder()
    {
        return $this->whereBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCondition()
    {
        return $this->whereBuilder->getSearchCondition();
    }

    /**
     * @return Query|NativeQuery
     */
    public function getQuery()
    {
        return $this->whereBuilder->getQuery();
    }
}
