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
 * SearchCondition Doctrine ORM WhereBuilder.
 *
 * This class provides the functionality for creating an DQL WHERE-clause
 * based on the provided SearchCondition.
 *
 * Keep the following in mind when using conversions.
 *
 *  * Conversions are performed per field and must be stateless,
 *    they receive the type and connection information for the conversion process.
 *  * Conversions apply at the SQL level, meaning they must be platform specific.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class WhereBuilder extends AbstractWhereBuilder implements WhereBuilderInterface
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
     *
     * @throws BadMethodCallException When SearchCondition contains errors
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
    public function getWhereClause($prependQuery = '')
    {
        if (null === $this->whereClause) {
            $fields = $this->fieldsConfig->getFields();
            $platform = new DqlQueryPlatform($this->entityManager);

            $queryGenerator = new QueryGenerator(
                $this->entityManager->getConnection(), $platform, $fields
            );

            $this->nativePlatform = $this->getQueryPlatform($this->entityManager->getConnection(), $fields);
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
     * @param string $prependQuery Prepends this string to the where-clause
     *                             (" WHERE " or " AND " for example)
     *
     * @return self
     */
    public function updateQuery($prependQuery = ' WHERE ')
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ('' !== $whereCase) {
            $this->query->setDQL($this->query->getDQL().$whereCase);
            $this->query->setHint(
                $this->getQueryHintName(),
                $this->getQueryHintValue()
            );
        }

        return $this;
    }

    /**
     * Returns the Query hint name for the final query object.
     *
     * The Query hint is used for conversions.
     *
     * @return string
     */
    public function getQueryHintName()
    {
        return 'rws_conversion_hint';
    }

    /**
     * Returns the Query hint value for the final query object.
     *
     * The Query hint is used for sql-value-conversions.
     *
     * @return SqlConversionInfo|\Closure
     */
    public function getQueryHintValue()
    {
        if (null === $this->whereClause) {
            throw new BadMethodCallException(
                'Unable to get query-hint value for WhereBuilder. Call getWhereClause() before calling this method.'
            );
        }

        return new SqlConversionInfo($this->nativePlatform, $this->parameters, $this->fieldsConfig->getFieldsForHint());
    }

    /**
     * @return array
     *
     * @internal
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return DqlQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
