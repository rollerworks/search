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
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\SearchCondition;

/**
 * SearchCondition Doctrine ORM DQL ConditionGenerator.
 *
 * Note that only fields that have been configured with `setField()`
 * will be actually used in the generated query.
 *
 * Keep the following in mind when using conversions.
 *
 *  * Conversions are performed per search field and must be stateless, they receive the db-type
 *    and connection information for the conversion process.
 *  * Unlike DBAL conversions the conversion must be DQL (not SQL)
 *  * Values must be registered as parameters (using the ConversionHints)
 *  * Conversion results must be properly escaped to prevent DQL injections.
 *
 * After the fields are configured call `apply()` to apply
 * the condition on the QueryBuilder.
 */
final class QueryBuilderConditionGenerator implements ConditionGenerator
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

    public function __construct(QueryBuilder $query, SearchCondition $searchCondition)
    {
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
    private function guardNotGenerated(): void
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

    public function apply(): self
    {
        if ($this->isApplied) {
            \trigger_error('SearchCondition was already applied. Ignoring operation.', \E_USER_WARNING);

            return $this;
        }

        $this->isApplied = true;
        $generator = new DqlConditionGenerator($this->qb->getEntityManager(), $this->searchCondition, $this->fieldsConfig);
        $whereClause = $generator->getWhereClause();

        if (null !== $primaryCondition = $this->searchCondition->getPrimaryCondition()) {
            DqlConditionGenerator::applySortingTo($primaryCondition->getOrder(), $this->qb, $this->fieldsConfig);
        }

        DqlConditionGenerator::applySortingTo($this->searchCondition->getOrder(), $this->qb, $this->fieldsConfig);

        if ($whereClause === '') {
            return $this;
        }

        $this->qb->andWhere($whereClause);

        foreach ($generator->getParameters() as $name => [$value, $type]) {
            $this->qb->setParameter($name, $value, $type);
        }

        return $this;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }
}
