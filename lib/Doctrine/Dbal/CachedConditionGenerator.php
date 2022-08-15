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

use Doctrine\DBAL\Query\QueryBuilder;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\SearchCondition;

/**
 * Handles caching of a Doctrine DBAL ConditionGenerator.
 *
 * Instead of using the ConditionGenerator directly you use the
 * CachedConditionGenerator as all related calls are delegated.
 *
 * The cache-key is a hashed (sha256) combination of the SearchCondition
 * (root ValuesGroup and FieldSet name) and configured field mappings.
 *
 * Caution: Any noticeable changes to your (FieldSet's) configuration
 * should purge all cached entries.
 */
final class CachedConditionGenerator extends AbstractCachedConditionGenerator implements ConditionGenerator
{
    private QueryBuilder $qb;
    private FieldConfigurationSet $fieldsConfig;

    /**
     * @param mixed|null $ttl
     */
    public function __construct(QueryBuilder $queryBuilder, SearchCondition $searchCondition, Cache $cacheDriver, $ttl = null)
    {
        parent::__construct($cacheDriver, $searchCondition, $ttl);

        $this->qb = $queryBuilder;
        $this->fieldsConfig = new FieldConfigurationSet($searchCondition->getFieldSet());
    }

    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string')
    {
        $this->fieldsConfig->setField($fieldName, $column, $alias, $type);

        return $this;
    }

    public function apply(): void
    {
        if ($this->isApplied) {
            trigger_error('SearchCondition was already applied. Ignoring operation.', \E_USER_WARNING);

            return;
        }

        $this->isApplied = true;

        $fields = $this->fieldsConfig->fields;
        $cacheKey = $this->getCacheKey($fields);
        $cached = $this->getFromCache($cacheKey);

        // Note that ordering is not part of the cache as this only applies at the root level
        // And is handled by QueryBuilder itself, making it possible to reuse the same condition
        // with a different ordering.

        if ($cached !== null) {
            [$whereClause, $parameters] = $cached;
        } else {
            $connection = $this->qb->getConnection();
            $generator = new QueryGenerator($connection, SqlConditionGenerator::getQueryPlatform($connection), $fields);

            $whereClause = $generator->getWhereClause($this->searchCondition);
            $parameters = $generator->getParameters();

            $this->storeInCache($whereClause, $cacheKey, $parameters);
        }

        QueryGenerator::applySortingTo($this->searchCondition->getPrimaryCondition()?->getOrder(), $this->qb, $fields);
        QueryGenerator::applySortingTo($this->searchCondition->getOrder(), $this->qb, $fields);

        if ($whereClause !== '') {
            $this->qb->andWhere($whereClause);

            foreach ($parameters as $name => [$value, $type]) {
                $this->qb->setParameter($name, $value, $type);
            }
        }
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }
}
