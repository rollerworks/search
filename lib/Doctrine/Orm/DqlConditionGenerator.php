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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query as DqlQuery;
use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\QueryPlatform\DqlQueryPlatform;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\SearchCondition;

/**
 * SearchCondition Doctrine ORM DQL ConditionGenerator.
 *
 * This class provides the functionality for creating a DQL
 * WHERE-clause based on the provided SearchCondition.
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
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @final
 */
class DqlConditionGenerator implements ConditionGenerator
{
    /**
     * @var SearchCondition
     */
    private $searchCondition;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @var FieldConfigBuilder
     */
    private $fieldsConfig;

    /**
     * @var ArrayCollection
     */
    private $parameters;

    /**
     * @var DqlQuery|QueryBuilder
     */
    private $query;

    public function __construct($query, SearchCondition $searchCondition)
    {
        if (! $query instanceof DqlQuery && ! $query instanceof QueryBuilder) {
            throw new UnexpectedTypeException($query, [DqlQuery::class, QueryBuilder::class]);
        }

        $this->entityManager = $query->getEntityManager();
        $this->fieldsConfig = new FieldConfigBuilder($this->entityManager, $searchCondition->getFieldSet());
        $this->searchCondition = $searchCondition;
        $this->parameters = new ArrayCollection();
        $this->query = $query;
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
        if ($this->whereClause !== null) {
            throw new BadMethodCallException(
                'ConditionGenerator configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }
    }

    public function setField(string $fieldName, string $property, string $alias = null, string $entity = null, string $dbType = null)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setField($fieldName, $property, $alias, $entity, $dbType);

        return $this;
    }

    public function getWhereClause(string $prependQuery = ''): string
    {
        if ($this->whereClause === null) {
            $fields = $this->fieldsConfig->getFields();
            $connection = $this->entityManager->getConnection();
            $platform = new DqlQueryPlatform($connection);
            $queryGenerator = new QueryGenerator($connection, $platform, $fields);

            $this->whereClause = $queryGenerator->getWhereClause($this->searchCondition);
            $this->parameters = $platform->getParameters();
        }

        if ($this->whereClause !== '') {
            return $prependQuery . $this->whereClause;
        }

        return '';
    }

    public function updateQuery(string $prependQuery = ' WHERE '): self
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ($whereCase === '') {
            return $this;
        }

        if ($this->query instanceof QueryBuilder) {
            $this->query->andWhere($this->getWhereClause());
        } else {
            $this->query->setDQL($this->query->getDQL() . $whereCase);
        }

        foreach ($this->parameters as $name => [$value, $type]) {
            $this->query->setParameter($name, $value, $type);
        }

        return $this;
    }

    public function getParameters(): ArrayCollection
    {
        return $this->parameters;
    }

    /**
     * @internal
     */
    public function getSearchCondition(): SearchCondition
    {
        return $this->searchCondition;
    }

    /**
     * @internal
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @internal
     */
    public function getFieldsConfig(): FieldConfigBuilder
    {
        return $this->fieldsConfig;
    }

    /**
     * @internal
     *
     * @return DqlQuery|QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
    }
}
