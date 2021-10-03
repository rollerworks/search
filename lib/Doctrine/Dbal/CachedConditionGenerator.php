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

use Doctrine\DBAL\Statement;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\SearchCondition;

/**
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
final class CachedConditionGenerator extends AbstractCachedConditionGenerator implements ConditionGenerator
{
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
        $this->conditionGenerator = $conditionGenerator;
    }

    /**
     * @see SqlConditionGenerator::getWhereClause()
     *
     * @param string $prependQuery Prepends this string to the where-clause
     *                             (" WHERE " or " AND " for example)
     */
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

                $this->cacheDriver->set(
                    $cacheKey,
                    [$this->whereClause, $this->packParameters($this->parameters)],
                    $this->cacheLifeTime
                );
            }
        }

        if ($this->whereClause !== '') {
            return $prependQuery . $this->whereClause;
        }

        return '';
    }

    public function bindParameters(Statement $statement): void
    {
        foreach ($this->parameters as $name => [$value, $type]) {
            $statement->bindValue($name, $value, $type);
        }
    }

    public function getSearchCondition(): SearchCondition
    {
        return $this->conditionGenerator->getSearchCondition();
    }

    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string')
    {
        $this->conditionGenerator->setField($fieldName, $column, $alias, $type);

        return $this;
    }

    public function getFieldsMapping(): array
    {
        return $this->conditionGenerator->getFieldsMapping();
    }

    private function getCacheKey(): string
    {
        if ($this->cacheKey === null) {
            $searchCondition = $this->conditionGenerator->getSearchCondition();

            $this->cacheKey = hash(
                'sha256',
                $searchCondition->getFieldSet()->getSetName() .
                "\n" .
                serialize($searchCondition->getValuesGroup()) .
                "\n" .
                serialize($searchCondition->getPrimaryCondition()) .
                "\n" .
                serialize($this->conditionGenerator->getFieldsMapping())
            );
        }

        return $this->cacheKey;
    }
}
