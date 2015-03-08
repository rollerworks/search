<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\ORM\NativeQuery;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\SearchConditionInterface;

/**
 * SearchCondition Doctrine ORM WhereBuilder for NativeQuery.
 *
 * This class provides the functionality for creating an SQL WHERE-clause
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
class NativeWhereBuilder extends AbstractWhereBuilder implements WhereBuilderInterface
{
    /**
     * @var NativeQuery
     */
    private $query;

    /**
     * Constructor.
     *
     * @param NativeQuery              $query           Doctrine ORM NativeQuery object
     * @param SearchConditionInterface $searchCondition SearchCondition object
     *
     * @throws BadMethodCallException When SearchCondition contains errors.
     */
    public function __construct(NativeQuery $query, SearchConditionInterface $searchCondition)
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
     * @param string $prependQuery Prepends this string to the where-clause
     *                             (" WHERE " or " AND " for example).
     *
     * @return string
     */
    public function getWhereClause($prependQuery = '')
    {
        if (null === $this->whereClause) {
            $fields = $this->fieldsConfig->getFields(true);
            $connection = $this->entityManager->getConnection();

            $queryGenerator = new QueryGenerator(
                $this->entityManager->getConnection(), $this->getQueryPlatform($connection, $fields), $fields
            );

            $this->whereClause = $queryGenerator->getGroupQuery(
                $this->searchCondition->getValuesGroup()
            );
        }

        if ('' !== $this->whereClause) {
            return $prependQuery.$this->whereClause;
        }

        return '';
    }

    /**
     * Updates the configured query object with the where-clause.
     *
     * @param string $prependQuery Prepends this string to the where-clause
     *                             (" WHERE " or " AND " for example).
     *
     * @return self
     */
    public function updateQuery($prependQuery = ' WHERE ')
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ($whereCase !== '') {
            $this->query->setSQL($this->query->getSQL().$whereCase);
        }

        return $this;
    }

    /**
     * @return NativeQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
