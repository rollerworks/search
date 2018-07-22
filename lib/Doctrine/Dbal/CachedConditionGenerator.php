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

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\SearchCondition;

/***
 * Handles caching of a Doctrine DBAL ConditionGenerator.
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
final class CachedConditionGenerator implements ConditionGenerator
{
    /**
     * @var Cache
     */
    private $cacheDriver;

    /**
     * @var null|int|\DateInterval
     */
    private $cacheLifeTime;

    /**
     * @var ConditionGenerator
     */
    private $conditionGenerator;

    /**
     * @var string|null
     */
    private $cacheKey;

    /**
     * @var string
     */
    private $whereClause;

    /**
     * Constructor.
     *
     * @param ConditionGenerator     $conditionGenerator The actual ConditionGenerator to use when no cache exists
     * @param Cache                  $cacheDriver        PSR-16 SimpleCache instance. Use a custom pool to ease
     *                                                   purging invalidated items
     * @param null|int|\DateInterval $ttl                Optional. The TTL value of this item. If no value is sent and
     *                                                   the driver supports TTL then the library may set a default value
     *                                                   for it or let the driver take care of that.
     */
    public function __construct(ConditionGenerator $conditionGenerator, Cache $cacheDriver, $ttl = 0)
    {
        $this->cacheDriver = $cacheDriver;
        $this->cacheLifeTime = $ttl;
        $this->conditionGenerator = $conditionGenerator;
    }

    /**
     * Returns the generated/cached where-clause.
     *
     * @see SqlConditionGenerator::getWhereClause()
     *
     * @param string $prependQuery Prepends this string to the where-clause
     *                             (" WHERE " or " AND " for example)
     *
     * @return string
     */
    public function getWhereClause(string $prependQuery = ''): string
    {
        if (null === $this->whereClause) {
            $cacheKey = $this->getCacheKey();

            if ($this->cacheDriver->has($cacheKey)) {
                $this->whereClause = $this->cacheDriver->get($cacheKey);
            } else {
                $this->whereClause = $this->conditionGenerator->getWhereClause();
                $this->cacheDriver->set($cacheKey, $this->whereClause, $this->cacheLifeTime);
            }
        }

        if ('' !== $this->whereClause) {
            return $prependQuery.$this->whereClause;
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCondition(): SearchCondition
    {
        return $this->conditionGenerator->getSearchCondition();
    }

    /**
     * {@inheritdoc}
     */
    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string')
    {
        $this->conditionGenerator->setField($fieldName, $column, $alias, $type);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsMapping(): array
    {
        return $this->conditionGenerator->getFieldsMapping();
    }

    private function getCacheKey(): string
    {
        if (null === $this->cacheKey) {
            $searchCondition = $this->conditionGenerator->getSearchCondition();
            $this->cacheKey = hash(
                'sha256',
                $searchCondition->getFieldSet()->getSetName().
                "\n".
                serialize($searchCondition->getValuesGroup()).
                "\n".
                serialize($searchCondition->getPrimaryCondition()).
                "\n".
                serialize($this->conditionGenerator->getFieldsMapping())
            );
        }

        return $this->cacheKey;
    }
}
