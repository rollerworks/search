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

use Doctrine\ORM\QueryBuilder;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Doctrine\Dbal\AbstractCachedConditionGenerator;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\SearchCondition;

/**
 * SearchCondition Doctrine ORM DQL CachedConditionGenerator.
 *
 * Note: this class should not be relied upon as interface,
 * use the ConditionGenerator interface instead for type hinting
 *
 * The cache-key is a hashed (sha256) combination of the SearchCondition
 * (root ValuesGroup and FieldSet name) and configured field mappings.
 *
 * Caution: Any noticeable changes to your (FieldSet) configuration
 * should purge all cached entries.
 *
 * After the fields are configured call `apply()` to apply
 * the condition on the QueryBuilder.
 *
 * @final
 */
class CachedDqlConditionGenerator extends AbstractCachedConditionGenerator implements ConditionGenerator
{
    /**
     * @var FieldConfigBuilder
     */
    private $fieldsConfig;

    /**
     * @var SearchCondition
     */
    private $searchCondition;

    /**
     * @var QueryBuilder
     */
    private $qb;

    private $isApplied = false;

    /**
     * @param mixed|null $ttl
     */
    public function __construct(QueryBuilder $query, SearchCondition $searchCondition, Cache $cacheDriver, $ttl = null)
    {
        parent::__construct($cacheDriver, $ttl);

        $this->fieldsConfig = new FieldConfigBuilder($query->getEntityManager(), $searchCondition->getFieldSet());
        $this->searchCondition = $searchCondition;
        $this->qb = $query;
    }

    public function setDefaultEntity(string $entity, string $alias)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setDefaultEntity($entity, $alias);

        return $this;
    }

    /**
     * @throws BadMethodCallException When the where-clause is already generated
     */
    protected function guardNotGenerated(): void
    {
        if ($this->isApplied) {
            throw new BadMethodCallException('ConditionGenerator configuration cannot be changed once the query is applied.');
        }
    }

    public function setField(string $fieldName, string $property, string $alias = null, string $entity = null, string $dbType = null)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setField($fieldName, $property, $alias, $entity, $dbType);

        return $this;
    }

    public function apply(): void
    {
        if ($this->isApplied) {
            \trigger_error('SearchCondition was already applied. Ignoring operation.', \E_USER_WARNING);

            return;
        }

        $this->isApplied = true;

        $cacheKey = $this->getCacheKey();
        $cached = $this->getFromCache($cacheKey);

        // Note that ordering is not part of the cache as this only applies at the root level
        // And is handled by Doctrine DQL itself, making it possible to reuse the same condition
        // with a different ordering.

        if ($cached !== null) {
            [$whereClause, $parameters] = $cached;
        } else {
            $generator = new DqlConditionGenerator($this->qb->getEntityManager(), $this->searchCondition, $this->fieldsConfig);
            $whereClause = $generator->getWhereClause();
            $parameters = $generator->getParameters();

            if ($whereClause !== '') {
                $this->cacheDriver->set(
                    $cacheKey,
                    [$whereClause, $this->packParameters($parameters)],
                    $this->cacheLifeTime
                );
            }
        }

        if (null !== $primaryCondition = $this->searchCondition->getPrimaryCondition()) {
            DqlConditionGenerator::applySortingTo($primaryCondition->getOrder(), $this->qb, $this->fieldsConfig);
        }

        DqlConditionGenerator::applySortingTo($this->searchCondition->getOrder(), $this->qb, $this->fieldsConfig);

        if ($whereClause === '') {
            return;
        }

        $this->qb->andWhere($whereClause);

        foreach ($parameters as $name => [$value, $type]) {
            $this->qb->setParameter($name, $value, $type);
        }
    }

    private function getCacheKey(): string
    {
        if ($this->cacheKey === null) {
            $searchCondition = $this->searchCondition;
            $primaryCondition = $searchCondition->getPrimaryCondition();

            $this->cacheKey = \hash(
                'sha256',
                "dql\n" .
                $searchCondition->getFieldSet()->getSetName() .
                "\n" .
                \serialize($searchCondition->getValuesGroup()) .
                "\n" .
                \serialize($primaryCondition ? $primaryCondition->getValuesGroup() : null) .
                "\n" .
                \serialize($this->fieldsConfig->getFields())
            );
        }

        return $this->cacheKey;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }
}
