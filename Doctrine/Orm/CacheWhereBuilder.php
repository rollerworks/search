<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\CacheFormatterInterface;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query as ORMQuery;
use Doctrine\Common\Cache\Cache;

/***
 * Handles the caching of the Doctrine ORM WhereBuilder.
 *
 * Checks the cache if there is a cached result present, if not it
 * delegates the creating to the parent and caches the result.
 *
 * Instead of calling getWhereClause() on the WhereBuilder class
 * you should call getWhereClause() on this class instead.
 *
 * Any conversions must be set on the 'parent' WhereBuilder object.
 * This class is specifically designed to be reused.
 *
 * WARNING: When changing the entities metadata or conversions
 * the cache **must** be invalidated the system does not do this automatically.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CacheWhereBuilder
{
    /**
     * @var Cache
     */
    private $cacheDriver;

    /**
     * @var integer
     */
    private $cacheLifeTime;

    /**
     * Constructor.
     *
     * @param Cache   $cacheProvider
     * @param integer $lifeTime
     */
    public function __construct(Cache $cacheProvider, $lifeTime = 0)
    {
        $this->cacheDriver = $cacheProvider;
        $this->cacheLifeTime = (int) $lifeTime;
    }

    /**
     * Returns the (cached) WHERE clause for the query.
     *
     * @see WhereBuilder#getWhereClause()
     *
     * @param CacheFormatterInterface $formatter
     * @param WhereBuilder            $whereBuilder
     * @param array                   $entityAliasMapping
     * @param AbstractQuery           $query
     * @param string|null             $appendQuery
     * @param boolean                 $resetParameterIndex
     *
     * @return null|string Returns null when there is no result
     */
    public function getWhereClause(CacheFormatterInterface $formatter, WhereBuilder $whereBuilder, array $entityAliasMapping = array(), AbstractQuery $query = null, $appendQuery = null, $resetParameterIndex = true)
    {
        $cacheKey = 'doctrine.orm.where.';
        $cacheKeyAppend = '';

        if (null !== $query) {
            if ($query instanceof ORMQuery) {
                $cacheKeyAppend .= 'dql_';
            } else {
                $cacheKeyAppend .= 'nat_';
            }

            if ($appendQuery && $query instanceof ORMQuery) {
                $cacheKeyAppend .= $query->getDQL();
            } elseif ($appendQuery) {
                $cacheKeyAppend .= $query->getSQL();
            }

            if ($resetParameterIndex) {
                $cacheKeyAppend .= 'reset';
            }
        }

        if ($entityAliasMapping) {
            $cacheKeyAppend .= serialize($entityAliasMapping);
        }

        if ($cacheKeyAppend) {
            $cacheKey .= md5($cacheKeyAppend . $formatter->getCacheKey());
        } else {
            $cacheKey .= $formatter->getCacheKey();
        }

        if ($this->cacheDriver->contains($cacheKey)) {
            $data = $this->cacheDriver->fetch($cacheKey);

            if (null !== $query && !empty($data[1])) {
                $query->setParameters($data[1]);
            }

            return $data[0];
        }

        $result = $whereBuilder->getWhereClause($formatter, $entityAliasMapping, $query, $appendQuery, $resetParameterIndex);
        $this->cacheDriver->save($cacheKey, array($result, $whereBuilder->getParameters()), $this->cacheLifeTime);

        return $result;
    }
}
