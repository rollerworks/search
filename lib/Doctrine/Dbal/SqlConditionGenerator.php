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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as MappingType;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform\SqlQueryPlatform;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;

/**
 * SearchCondition Doctrine DBAL ConditionGenerator.
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
 *  * SQL conversions must be properly escaped to prevent SQL injections.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SqlConditionGenerator implements ConditionGenerator
{
    /**
     * @var SearchCondition
     */
    private $searchCondition;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @var array[]
     */
    private $fields = [];

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection, SearchCondition $searchCondition)
    {
        $this->searchCondition = $searchCondition;
        $this->fieldSet = $searchCondition->getFieldSet();
        $this->connection = $connection;
    }

    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string'): self
    {
        if ($this->whereClause) {
            throw new BadMethodCallException(
                'ConditionGenerator configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }

        $mappingIdx = null;

        if (false !== strpos($fieldName, '#')) {
            list($fieldName, $mappingIdx) = explode('#', $fieldName, 2);
            unset($this->fields[$fieldName][null]);
        } else {
            $this->fields[$fieldName] = [];
        }

        $this->fields[$fieldName][$mappingIdx] = new QueryField(
            $fieldName.(null !== $mappingIdx ? "#$mappingIdx" : ''),
            $this->fieldSet->get($fieldName),
            MappingType::getType($type),
            $column,
            $alias
        );

        return $this;
    }

    public function getWhereClause(string $prependQuery = ''): string
    {
        if (null === $this->whereClause) {
            $this->whereClause = (new QueryGenerator(
                $this->connection, $this->getQueryPlatform(), $this->fields
            ))->getWhereClause($this->searchCondition);
        }

        if ('' !== $this->whereClause) {
            return $prependQuery.$this->whereClause;
        }

        return '';
    }

    public function getFieldsMapping(): array
    {
        return $this->fields;
    }

    public function getSearchCondition(): SearchCondition
    {
        return $this->searchCondition;
    }

    private function getQueryPlatform(): QueryPlatform
    {
        $dbPlatform = ucfirst($this->connection->getDatabasePlatform()->getName());
        $platformClass = 'Rollerworks\\Component\\Search\\Doctrine\\Dbal\\QueryPlatform\\'.$dbPlatform.'QueryPlatform';

        if (!class_exists($platformClass)) {
            $platformClass = SqlQueryPlatform::class;
        }

        return new $platformClass($this->connection);
    }
}
