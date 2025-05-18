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
use Doctrine\DBAL\Query\QueryBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform\AbstractQueryPlatform;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform\SqlQueryPlatform;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
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
 */
final class SqlConditionGenerator implements ConditionGenerator
{
    private QueryBuilder $qb;
    private SearchCondition $searchCondition;
    private FieldConfigurationSet $fieldsConfig;
    private bool $isApplied = false;

    public function __construct(QueryBuilder $queryBuilder, SearchCondition $searchCondition)
    {
        $this->qb = $queryBuilder;
        $this->searchCondition = $searchCondition;
        $this->fieldsConfig = new FieldConfigurationSet($searchCondition->getFieldSet());
    }

    public function setField(string $fieldName, string $column, ?string $alias = null, string $type = 'string'): self
    {
        if ($this->isApplied) {
            throw new BadMethodCallException(
                'ConditionGenerator configuration cannot be changed anymore once the condition is applied.'
            );
        }

        $this->fieldsConfig->setField($fieldName, $column, $alias, $type);

        return $this;
    }

    public function getSearchCondition(): SearchCondition
    {
        return $this->searchCondition;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }

    public function apply(): void
    {
        if ($this->isApplied) {
            trigger_error('SearchCondition was already applied. Ignoring operation.', \E_USER_WARNING);

            return;
        }

        $this->isApplied = true;
        $fields = $this->fieldsConfig->fields;

        QueryGenerator::applySortingTo($this->searchCondition->getPrimaryCondition()?->getOrder(), $this->qb, $fields);
        QueryGenerator::applySortingTo($this->searchCondition->getOrder(), $this->qb, $fields);

        if (method_exists($this->qb, 'getConnection')) {
            $connection = $this->qb->getConnection();
        } else {
            // XXX This is the only way to get the connection from a QueryBuilder, unless this method is restored.
            //
            // The DBAL generator is executed multiple levels deep, passing this with the constructor requires
            // too much for now. This is a workaround until the DBAL generator is refactored, or the method is restored.
            $connection = (new \ReflectionClass(QueryBuilder::class))->getProperty('connection')->getValue($this->qb);
        }

        $generator = new QueryGenerator($connection, self::getQueryPlatform($connection), $fields);
        $whereClause = $generator->getWhereClause($this->searchCondition);

        if ($whereClause !== '') {
            $this->qb->andWhere($whereClause);

            foreach ($generator->getParameters() as $name => [$value, $type]) {
                $this->qb->setParameter($name, $value, $type);
            }
        }
    }

    /**
     * @internal
     */
    public static function getQueryPlatform(Connection $connection): AbstractQueryPlatform
    {
        $dbPlatform = self::getPlatformName($connection);
        $platformClass = 'Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform\\' . ucfirst($dbPlatform) . 'QueryPlatform';

        if (! class_exists($platformClass)) {
            $platformClass = SqlQueryPlatform::class;
        }

        return new $platformClass($connection, $dbPlatform);
    }

    private static function getPlatformName(Connection $connection): string
    {
        $platform = $connection->getDatabasePlatform();

        return match (true) {
            $platform instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform => 'mysql',
            $platform instanceof \Doctrine\DBAL\Platforms\SQLitePlatform => 'sqlite',
            $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform => 'pgsql',
            $platform instanceof \Doctrine\DBAL\Platforms\OraclePlatform => 'oci',
            $platform instanceof \Doctrine\DBAL\Platforms\SQLServerPlatform => 'sqlsrv',
            $platform instanceof \Rollerworks\Component\Search\Tests\Doctrine\Dbal\Mocks\DatabasePlatformMock => 'mock',
            default => $platform::class,
        };
    }
}
