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
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;
use Rollerworks\Component\Search\Exception\BadMethodCallException;

/**
 * Handles caching of the Doctrine ORM DqlConditionGenerator.
 *
 * Instead of using the ConditionGenerator directly you should use the
 * CachedConditionGenerator as all related calls are delegated.
 *
 * The cache-key is a hashed (sha256) combination of the SearchCondition
 * (root ValuesGroup and FieldSet name) and configured field mappings.
 *
 * Caution: Any noticeable changes to your (FieldSet's) configuration
 * should purge all cached entries.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @final
 */
class CachedDqlConditionGenerator extends AbstractCachedConditionGenerator
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
     * @var QueryPlatform
     */
    private $nativePlatform;

    /**
     * Constructor.
     *
     * @param DqlConditionGenerator  $conditionGenerator The ConditionGenerator to use for generating
     *                                                   the condition when no cache exists
     * @param Cache                  $cacheDriver        PSR-16 SimpleCache instance. Use a custom pool to ease
     *                                                   purging invalidated items
     * @param null|int|\DateInterval $ttl                Optional. The TTL value of this item. If no value is sent and
     *                                                   the driver supports TTL then the library may set a default value
     *                                                   for it or let the driver take care of that.
     */
    public function __construct(DqlConditionGenerator $conditionGenerator, Cache $cacheDriver, int $ttl = null)
    {
        parent::__construct($conditionGenerator, $cacheDriver, $ttl);

        $this->ttl = $ttl;
        $this->cacheDriver = $cacheDriver;
        $this->query = $conditionGenerator->getQuery();
    }

    /**
     * Returns the generated/cached where-clause.
     *
     * @param string $prependQuery Prepends this string to the where-clause
     *                             ("WHERE" or "AND" for example)
     *
     * @return string
     */
    public function getWhereClause(string $prependQuery = ''): string
    {
        if (null === $this->whereClause) {
            $cacheKey = $this->getCacheKey();

            $this->nativePlatform = $this->getQueryPlatform($this->conditionGenerator->getEntityManager()->getConnection());

            if (null !== $cacheItem = $this->cacheDriver->get($cacheKey)) {
                list($this->whereClause, $this->parameters) = $cacheItem;
            } elseif ('' !== $this->whereClause = $this->conditionGenerator->getWhereClause($prependQuery)) {
                $this->parameters = $this->conditionGenerator->getParameters();

                $this->cacheDriver->set(
                    $cacheKey,
                    [
                        $this->whereClause,
                        $this->parameters,
                    ],
                    $this->ttl
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
     * @return CachedDqlConditionGenerator
     */
    public function updateQuery(string $prependQuery = ' WHERE ')
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ($whereCase !== '') {
            $this->query->setDQL($this->query->getDQL().$whereCase);
            $this->query->setHint(
                $this->conditionGenerator->getQueryHintName(),
                $this->getQueryHintValue()
            );
        }

        return $this;
    }

    /**
     * Returns the Query-hint name for the final query object.
     *
     * The Query-hint is used for conversions.
     *
     * @return string
     */
    public function getQueryHintName()
    {
        return $this->conditionGenerator->getQueryHintName();
    }

    /**
     * Returns the Query-hint value for the final query object.
     *
     * The Query hint is used for value-conversions.
     *
     * @return SqlConversionInfo
     */
    public function getQueryHintValue(): SqlConversionInfo
    {
        if (null === $this->whereClause) {
            throw new BadMethodCallException(
                'Unable to get query-hint value for ConditionGenerator. Call getWhereClause() before calling this method.'
            );
        }

        return new SqlConversionInfo(
            $this->nativePlatform,
            $this->parameters,
            $this->conditionGenerator->getFieldsConfig()->getFields()
        );
    }

    private function getCacheKey(): string
    {
        if (null === $this->cacheKey) {
            $searchCondition = $this->conditionGenerator->getSearchCondition();
            $this->cacheKey = hash(
                'sha256',
                "dql\n".
                $searchCondition->getFieldSet()->getSetName().
                "\n".
                serialize($searchCondition->getValuesGroup()).
                "\n".
                serialize($searchCondition->getPreCondition()).
                "\n".
                serialize($this->conditionGenerator->getFieldsConfig()->getFields())
            );
        }

        return $this->cacheKey;
    }
}
