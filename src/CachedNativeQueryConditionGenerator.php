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
use Psr\SimpleCache\CacheInterface as Cache;

/**
 * Handles caching of the Doctrine ORM NativeQueryConditionGenerator.
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
 */
final class CachedNativeQueryConditionGenerator extends AbstractCachedConditionGenerator
{
    /**
     * @var NativeQuery
     */
    private $query;

    /**
     * Constructor.
     *
     * @param NativeQueryConditionGenerator $conditionGenerator The ConditionGenerator to use for generating
     *                                                          the condition when no cache exists
     * @param Cache                         $cacheDriver        PSR-16 SimpleCache instance. Use a custom pool to ease
     *                                                          purging invalidated items
     * @param null|int|\DateInterval        $ttl                Optional. The TTL value of this item. If no value is sent and
     *                                                          the driver supports TTL then the library may set a default value
     *                                                          for it or let the driver take care of that.
     */
    public function __construct(NativeQueryConditionGenerator $conditionGenerator, Cache $cacheDriver, int $ttl = null)
    {
        parent::__construct($conditionGenerator, $cacheDriver, $ttl);

        $this->conditionGenerator = $conditionGenerator;
        $this->query = $conditionGenerator->getQuery();
        $this->cacheDriver = $cacheDriver;
        $this->ttl = $ttl;
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

            if (null === $this->whereClause = $this->cacheDriver->get($cacheKey)) {
                if ('' !== $this->whereClause = $this->conditionGenerator->getWhereClause($prependQuery)) {
                    $this->cacheDriver->set($cacheKey, $this->whereClause, $this->ttl);
                }
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
     * @return CachedNativeQueryConditionGenerator
     */
    public function updateQuery(string $prependQuery = ' WHERE ')
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ($whereCase !== '') {
            $this->query->setSQL($this->query->getSQL().$whereCase);
        }

        return $this;
    }

    private function getCacheKey(): string
    {
        if (null === $this->cacheKey) {
            $searchCondition = $this->conditionGenerator->getSearchCondition();
            $this->cacheKey = hash(
                'sha256',
                "native\n".
                $searchCondition->getFieldSet()->getSetName().
                "\n".
                serialize($searchCondition->getValuesGroup()).
                "\n".
                serialize($this->conditionGenerator->getFieldsConfig()->getFields())
            );
        }

        return $this->cacheKey;
    }
}
