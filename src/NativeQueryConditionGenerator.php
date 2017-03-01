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

use Doctrine\ORM\NativeQuery;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\SearchCondition;

/**
 * SearchCondition Doctrine ORM ConditionGenerator for NativeQuery.
 *
 * This class provides the functionality for creating an SQL WHERE-clause
 * based on the provided SearchCondition.
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
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class NativeQueryConditionGenerator extends AbstractConditionGenerator
{
    private $query;

    /**
     * Constructor.
     *
     * @param NativeQuery     $query           Doctrine ORM NativeQuery object
     * @param SearchCondition $searchCondition SearchCondition object
     */
    public function __construct(NativeQuery $query, SearchCondition $searchCondition)
    {
        $this->fieldset = $searchCondition->getFieldSet();
        $this->searchCondition = $searchCondition;

        $this->entityManager = $query->getEntityManager();
        $this->fieldsConfig = new FieldConfigBuilder($this->entityManager, $this->fieldset, true);
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getWhereClause(string $prependQuery = ''): string
    {
        if (null === $this->whereClause) {
            $fields = $this->fieldsConfig->getFields();
            $connection = $this->entityManager->getConnection();

            $queryGenerator = new QueryGenerator($connection, $this->getQueryPlatform($connection, $fields), $fields);
            $this->whereClause = $queryGenerator->getGroupQuery($this->searchCondition->getValuesGroup());
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
     *                             (" WHERE " or " AND " for example)
     *
     * @return NativeQueryConditionGenerator
     */
    public function updateQuery(string $prependQuery = ' WHERE ')
    {
        $whereCase = $this->getWhereClause($prependQuery);

        if ($whereCase !== '') {
            $this->query->setSQL($this->query->getSQL().$whereCase);
        }

        return $this;
    }

    /**
     * @internal
     */
    public function getQuery(): NativeQuery
    {
        return $this->query;
    }
}
