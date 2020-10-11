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
use Doctrine\ORM\QueryBuilder;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Doctrine\Dbal\AbstractCachedConditionGenerator;
use Rollerworks\Component\Search\Exception\BadMethodCallException;

/**
 * Handles caching of the Doctrine ORM DqlConditionGenerator.
 *
 * Instead of using the ConditionGenerator directly you should use the
 * CachedConditionGenerator as all related calls are delegated.
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
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @final
 */
class CachedDqlConditionGenerator extends AbstractCachedConditionGenerator implements ConditionGenerator
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var ConditionGenerator
     */
    private $conditionGenerator;

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @param ConditionGenerator $conditionGenerator The actual ConditionGenerator to use when no cache exists
     * @param mixed|null         $ttl
     */
    public function __construct(ConditionGenerator $conditionGenerator, Cache $cacheDriver, $ttl = null)
    {
        parent::__construct($cacheDriver, $ttl);

        $this->query = $conditionGenerator->getQuery();
        $this->conditionGenerator = $conditionGenerator;
    }

    public function getWhereClause(string $prependQuery = ''): string
    {
        if ($this->whereClause === null) {
            $cacheKey = $this->getCacheKey();
            $cached = $this->getFromCache($cacheKey);

            if ($cached !== null) {
                $this->whereClause = $cached[0];
                $this->parameters = $cached[1];
            } else {
                $this->whereClause = $this->conditionGenerator->getWhereClause();
                $this->parameters = $this->conditionGenerator->getParameters();

                if ($this->whereClause !== '') {
                    $this->cacheDriver->set(
                        $cacheKey,
                        [$this->whereClause, $this->packParameters($this->parameters)],
                        $this->cacheLifeTime
                    );
                }
            }
        }

        if ($this->whereClause !== '') {
            return $prependQuery . $this->whereClause;
        }

        return '';
    }

    private function getCacheKey(): string
    {
        if ($this->cacheKey === null) {
            $searchCondition = $this->conditionGenerator->getSearchCondition();

            $this->cacheKey = \hash(
                'sha256',
                "dql\n" .
                $searchCondition->getFieldSet()->getSetName() .
                "\n" .
                \serialize($searchCondition->getValuesGroup()) .
                "\n" .
                \serialize($searchCondition->getPrimaryCondition()) .
                "\n" .
                \serialize($this->conditionGenerator->getFieldsConfig()->getFields())
            );
        }

        return $this->cacheKey;
    }

    public function updateQuery(string $prependQuery = ' WHERE '): self
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ($whereCase !== '') {
            if ($this->query instanceof QueryBuilder) {
                $this->query->andWhere($this->getWhereClause());
            } else {
                $this->query->setDQL($this->query->getDQL() . $whereCase);
            }

            $this->bindParameters();
        }

        return $this;
    }

    public function bindParameters(): void
    {
        foreach ($this->parameters as $name => [$value, $type]) {
            $this->query->setParameter($name, $value, $type);
        }
    }

    public function setDefaultEntity(string $entity, string $alias)
    {
        $this->guardNotGenerated();
        $this->conditionGenerator->setDefaultEntity($entity, $alias);

        return $this;
    }

    /**
     * @throws BadMethodCallException When the where-clause is already generated
     */
    private function guardNotGenerated(): void
    {
        if ($this->whereClause !== null) {
            throw new BadMethodCallException(
                'ConditionGenerator configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }
    }

    public function setField(string $fieldName, string $property, string $alias = null, string $entity = null, string $dbType = null)
    {
        $this->guardNotGenerated();
        $this->conditionGenerator->setField($fieldName, $property, $alias, $entity, $dbType);

        return $this;
    }
}
