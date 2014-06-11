<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query as DqlQuery;
use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\AbstractCacheWhereBuilder;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/***
 * Handles caching of the Doctrine ORM WhereBuilder.
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
 * WARNING. Any changes to the entities mapping should invalidate the cache
 * the system does not do this automatically.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CacheWhereBuilder extends AbstractCacheWhereBuilder implements WhereBuilderInterface
{
    /**
     * @var boolean
     */
    private $queryModified;

    /**
     * Constructor.
     *
     * @param WhereBuilderInterface $whereBuilder The WhereBuilder to use for generating and updating the query
     * @param Cache                 $cacheDriver  Doctrine Cache instance
     * @param integer               $lifeTime     Lifetime in seconds after which the cache is expired
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
     * @return string
     */
    public function getWhereClause()
    {
        if ($this->whereClause) {
            return $this->whereClause;
        }

        $cacheKey = 'rw_search.doctrine.orm.where.';
        $query = $this->whereBuilder->getQuery();

        if ($query instanceof NativeQuery) {
            $cacheKey .= 'nat_';
        } else {
            $cacheKey .= 'dql_';
        }

        $cacheKey .= $this->cacheKey;
        $cacheKey .= $this->keySuffix ? '_'.$this->keySuffix : '';

        if ($this->cacheDriver->contains($cacheKey)) {
            $data = $this->cacheDriver->fetch($cacheKey);

            $this->whereClause = $data[0];
            $this->applyParameters($query, $data[1]);
        } else {
            $this->whereClause = $this->whereBuilder->getWhereClause();
            $this->applyParameters($query, $this->whereBuilder->getParameters());
            $this->cacheDriver->save(
                $cacheKey,
                array(
                    $this->whereClause,
                    $this->whereBuilder->getParameters()
                ),
                $this->cacheLifeTime
            );
        }

        return $this->whereClause;
    }

    /**
     * Updates the configured query object with the where-clause.
     *
     * @see WhereBuilder::updateQuery()
     *
     * @param string  $prependQuery Prepends this string to the where-clause ("WHERE" or "AND" for example)
     * @param boolean $forceUpdate  Force the where-builder to update the query
     *
     * @return self
     */
    public function updateQuery($prependQuery = '', $forceUpdate = false)
    {
        $whereCase = $this->getWhereClause();

        if ($whereCase === '' || ($this->queryModified && !$forceUpdate)) {
            return $this;
        }

        $query = $this->whereBuilder->getQuery();
        if ($query instanceof NativeQuery) {
            $query->setSQL($query->getSQL().$prependQuery.$whereCase);
        } else {
            $query->setDQL($query->getDQL().$prependQuery.$whereCase);
        }

        if ($query instanceof DqlQuery) {
            $query->setHint($this->whereBuilder->getQueryHintName(), $this->whereBuilder->getQueryHintValue());
        }

        $this->queryModified = true;

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
     * The Query hint is used for conversions for value-matchers.
     *
     * @return \Closure
     */
    public function getQueryHintValue()
    {
        return $this->whereBuilder->getQueryHintValue();
    }

    /**
     * @return object
     */
    public function getQuery()
    {
        return $this->whereBuilder->getQuery();
    }

    /**
     * @param NativeQuery|DqlQuery|QueryBuilder $query
     * @param array                             $params
     */
    private function applyParameters($query, array $params)
    {
        foreach ($params as $name => $value) {
            $query->setParameter($name, $value);
        }
    }
}
