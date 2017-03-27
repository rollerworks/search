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
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform;
use Rollerworks\Component\Search\Doctrine\Orm\QueryPlatform\DqlQueryPlatform;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
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
final class DqlConditionGenerator extends AbstractConditionGenerator
{
    /**
     * @var DqlQuery
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
     * Constructor.
     *
     * @param DqlQuery        $query           Doctrine ORM Query object
     * @param SearchCondition $searchCondition SearchCondition object
     */
    public function __construct(DqlQuery $query, SearchCondition $searchCondition)
    {
        parent::__construct($searchCondition, $query->getEntityManager());

        $this->query = $query;
    }

    /**
     * Returns the generated where-clause.
     *
     * The Where-clause is wrapped inside a group so it can be safely used
     * with other conditions.
     *
     * For SQL conversions to work properly you need to set the required
     * hints using getQueryHintName() and getQueryHintValue().
     *
     * @param string $prependQuery Prepends this string to the where-clause
     *                             (" WHERE " or " AND " for example)
     *
     * @return string
     */
    public function getWhereClause(string $prependQuery = ''): string
    {
        if (null === $this->whereClause) {
            $fields = $this->fieldsConfig->getFields();
            $platform = new DqlQueryPlatform($this->entityManager);
            $connection = $this->entityManager->getConnection();
            $queryGenerator = new QueryGenerator($connection, $platform, $fields);

            $this->nativePlatform = $this->getQueryPlatform($connection, $fields);
            $this->whereClause = $queryGenerator->getGroupQuery($this->searchCondition->getValuesGroup());
            $this->parameters = $platform->getEmbeddedValues();
        }

        if ('' !== $this->whereClause) {
            return $prependQuery.$this->whereClause;
        }

        return '';
    }

    /**
     * Updates the configured query object with the where-clause and query-hints.
     *
     * @param string $prependQuery Prepend before the generated WHERE clause
     *                             Eg. " WHERE " or " AND ", ignored when WHERE
     *                             clause is empty. Default is ' WHERE '
     *
     * @return DqlConditionGenerator
     */
    public function updateQuery(string $prependQuery = ' WHERE ')
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ('' !== $whereCase) {
            $this->query->setDQL($this->query->getDQL().$whereCase);
            $this->query->setHint($this->getQueryHintName(), $this->getQueryHintValue());
        }

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
     *
     * @return SqlConversionInfo
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
    public function getQuery(): DqlQuery
    {
        return $this->query;
    }
}
