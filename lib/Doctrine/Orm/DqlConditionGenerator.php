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

use Doctrine\ORM\Query as DqlQuery;
use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;
use Rollerworks\Component\Search\Doctrine\Orm\QueryPlatform\DqlQueryPlatform;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
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
 *  * Conversions are performed per search field and must be stateless,
 *    they receive the db-type and connection information for the conversion process.
 *  * Conversions apply at the SQL level, meaning they must be platform specific.
 *  * Conversion results must be properly escaped to prevent SQL injections.
 *  * Conversions require the correct query-hint to be set.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @final
 */
class DqlConditionGenerator extends AbstractConditionGenerator
{
    /**
     * @var DqlQuery|QueryBuilder
     */
    private $query;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var QueryPlatform
     */
    private $nativePlatform;

    /**
     * @param DqlQuery|QueryBuilder $query Doctrine ORM Query
     */
    public function __construct($query, SearchCondition $searchCondition)
    {
        if ($query instanceof QueryBuilder) {
            if (!method_exists($query, 'setHint')) {
                throw new InvalidArgumentException(sprintf('An "%s" instance was provided but method setHint is not implemented in "%s".', QueryBuilder::class, \get_class($query)));
            }
        } elseif (!$query instanceof DqlQuery) {
            throw new UnexpectedTypeException($query, [DqlQuery::class, QueryBuilder::class.' (with QueryHint support)']);
        }

        parent::__construct($searchCondition, $query->getEntityManager());

        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     *
     * Note: For SQL conversions to work properly you need to set the required
     * hints using getQueryHintName() and getQueryHintValue().
     */
    public function getWhereClause(string $prependQuery = ''): string
    {
        if (null === $this->whereClause) {
            $fields = $this->fieldsConfig->getFields();
            $platform = new DqlQueryPlatform($this->entityManager);
            $connection = $this->entityManager->getConnection();
            $queryGenerator = new QueryGenerator($connection, $platform, $fields);

            $this->nativePlatform = $this->getQueryPlatform($connection);
            $this->whereClause = $queryGenerator->getWhereClause($this->searchCondition);
            $this->parameters = $platform->getEmbeddedValues();
        }

        if ('' !== $this->whereClause) {
            return $prependQuery.$this->whereClause;
        }

        return '';
    }

    public function updateQuery(string $prependQuery = ' WHERE '): self
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ('' === $whereCase) {
            return $this;
        }

        if ($this->query instanceof QueryBuilder) {
            $this->query->andWhere($this->getWhereClause());
        } else {
            $this->query->setDQL($this->query->getDQL().$whereCase);
        }

        $this->query->setHint($this->getQueryHintName(), $this->getQueryHintValue());

        return $this;
    }

    /**
     * Returns the Query-hint name for the query object.
     *
     * The Query-hint is used for conversions.
     *
     * @return string
     */
    public function getQueryHintName(): string
    {
        return 'rws_conversion_hint';
    }

    /**
     * Returns the Query-hint value for the query object.
     *
     * The Query hint is used for conversions.
     */
    public function getQueryHintValue(): SqlConversionInfo
    {
        if (null === $this->whereClause) {
            throw new BadMethodCallException(
                'Unable to get query-hint value for ConditionGenerator. Call getWhereClause() before calling this method.'
            );
        }

        return new SqlConversionInfo($this->nativePlatform, $this->parameters, $this->fieldsConfig->getFieldsForHint());
    }

    /**
     * @internal
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @internal
     */
    public function getQuery()
    {
        return $this->query;
    }
}
