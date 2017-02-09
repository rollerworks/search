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
 * Handles caching of a Doctrine DBAL WhereBuilder.
 *
 * Instead of using the WhereBuilder directly you should use the CacheWhereBuilder
 * as all related calls are delegated.
 *
 * The cache-key is a hashed (sha256) combination of the SearchCondition
 * (root ValuesGroup and FieldSet name) and configured field mappings.
 *
 * Caution: Any noticeable changes to your (FieldSet's) configuration
 * should purge all cached entries.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class CacheWhereBuilder implements WhereBuilderInterface
{
    /**
     * @var Cache
     */
    private $cacheDriver;

    /**
     * @var int
     */
    private $cacheLifeTime;

    /**
     * @var WhereBuilderInterface
     */
    private $whereBuilder;

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
     * @param WhereBuilderInterface $whereBuilder The actual WhereBuilder to use when no cache exists
     * @param Cache                 $cacheDriver  PSR-16 SimpleCache instance. Use a custom pool to ease
     *                                            purging invalidated items
     * @param int                   $lifeTime     Lifetime in seconds after which the cache is expired.
     *                                            Set this 0 to never expire (not recommended)
     */
    public function __construct(WhereBuilderInterface $whereBuilder, Cache $cacheDriver, int $lifeTime = 0)
    {
        $this->cacheDriver = $cacheDriver;
        $this->cacheLifeTime = $lifeTime;
        $this->whereBuilder = $whereBuilder;
    }

    /**
     * Returns the generated/cached where-clause.
     *
     * @see WhereBuilder::getWhereClause()
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
                $this->whereClause = $this->whereBuilder->getWhereClause();
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
        return $this->whereBuilder->getSearchCondition();
    }

    /**
     * {@inheritdoc}
     */
    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string')
    {
        $this->whereBuilder->setField($fieldName, $column, $alias, $type);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsMapping(): array
    {
        return $this->whereBuilder->getFieldsMapping();
    }

    private function getCacheKey(): string
    {
        if (null === $this->cacheKey) {
            $searchCondition = $this->whereBuilder->getSearchCondition();
            $this->cacheKey = hash(
                'sha256',
                $searchCondition->getFieldSet()->getSetName().
                "\n".
                serialize($searchCondition->getValuesGroup()).
                "\n".
                serialize($this->whereBuilder->getFieldsMapping())
            );
        }

        return $this->cacheKey;
    }
}
