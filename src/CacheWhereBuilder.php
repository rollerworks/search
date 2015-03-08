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
use Doctrine\ORM\Query;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatformInterface;
use Rollerworks\Component\Search\Exception\BadMethodCallException;

/**
 * Handles caching of the Doctrine ORM WhereBuilder.
 *
 * Note. For best performance caching of the WhereClause should be done on a
 * per user-session fieldset basis. This ensures enough uniqueness and
 * no complex serialization.
 *
 * This checks if there is a cached result, if not it delegates
 * the creating to the parent and caches the result.
 *
 * Instead of calling getWhereClause()/updateQuery() on the WhereBuilder
 * class you should call getWhereClause()/updateQuery() on this class instead.
 *
 * Caution: You must call the getQueryHintValue() on the this object and not
 * the WhereBuilder as the WhereBuilder is not executed.
 *
 * WARNING. Any changes to the entities mapping should invalidate the cache
 * the system does not do this automatically.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CacheWhereBuilder extends AbstractCacheWhereBuilder implements WhereBuilderInterface
{
    use QueryPlatformTrait;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var QueryPlatformInterface
     */
    private $nativePlatform;

    /**
     * Constructor.
     *
     * @param WhereBuilder $whereBuilder The WhereBuilder to use for generating and updating the query
     * @param Cache        $cacheDriver  Doctrine Cache instance
     * @param int          $lifeTime     Lifetime in seconds after which the cache is expired
     *                                   Set this 0 to never expire.
     */
    public function __construct(WhereBuilder $whereBuilder, Cache $cacheDriver, $lifeTime = 0)
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
    public function getWhereClause($prependQuery = '')
    {
        if (null === $this->whereClause) {
            $cacheKey = 'rw_search.doctrine.orm.where.dql.'.$this->cacheKey;

            $this->nativePlatform = $this->getQueryPlatform(
                $this->whereBuilder->getEntityManager()->getConnection(),
                $this->whereBuilder->getFieldsConfig()->getFields()
            );

            if ($this->cacheDriver->contains($cacheKey)) {
                list($this->whereClause, $this->parameters) = $this->cacheDriver->fetch($cacheKey);
            } else {
                $this->whereClause = $this->whereBuilder->getWhereClause();
                $this->parameters = $this->whereBuilder->getParameters();

                $this->cacheDriver->save(
                    $cacheKey,
                    [
                        $this->whereClause,
                        $this->parameters,
                    ],
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
            $this->query->setDQL($this->query->getDQL().$whereCase);
            $this->query->setHint(
                $this->whereBuilder->getQueryHintName(),
                $this->getQueryHintValue()
            );
        }

        return $this;
    }

    /**
     * Returns the Query hint name for the final query object.
     *
     * The Query hint is used for conversions.
     *
     * @return string
     */
    public function getQueryHintName()
    {
        return $this->whereBuilder->getQueryHintName();
    }

    /**
     * Returns the Query hint value for the final query object.
     *
     * The Query hint is used for sql-value-conversions.
     *
     * @return \Closure
     */
    public function getQueryHintValue()
    {
        if (null === $this->whereClause) {
            throw new BadMethodCallException(
                'Unable to get query-hint value for WhereBuilder. Call getWhereClause() before calling this method.'
            );
        }

        // Use a closure here to prevent to deep nesting and recursions
        return function () {
            return [$this->nativePlatform, $this->parameters];
        };
    }
}
