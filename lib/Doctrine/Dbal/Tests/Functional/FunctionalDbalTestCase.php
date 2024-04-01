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

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema as DbSchema;
use Doctrine\DBAL\SQL\Builder\DropSchemaObjectsSQLBuilder;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\Warning;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\Test\QueryBuilderAssertion;
use Rollerworks\Component\Search\Doctrine\Dbal\Tests\TestUtil;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\DbalTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;

abstract class FunctionalDbalTestCase extends DbalTestCase
{
    /**
     * Shared connection when a TestCase is run alone (outside of it's functional suite).
     *
     * @var Connection|null
     */
    private static $sharedConn;

    /**
     * @var Connection|null
     */
    protected $conn;

    /**
     * @var DebugStack|null
     */
    protected $sqlLoggerStack;

    /**
     * @var string|null
     */
    protected $query;

    protected static function resetSharedConn(): void
    {
        if (self::$sharedConn) {
            self::$sharedConn->close();
            self::$sharedConn = null;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! isset(
            $GLOBALS['db_driver'],
            $GLOBALS['db_host'],
            $GLOBALS['db_user'],
            $GLOBALS['db_password'],
            $GLOBALS['db_dbname'],
            $GLOBALS['db_port']
        )) {
            self::markTestSkipped('GLOBAL variables not enabled');
        }

        if (! isset(self::$sharedConn)) {
            self::$sharedConn = TestUtil::getConnection();

            $schema = new DbSchema();
            $this->setUpDbSchema($schema);

            foreach ((new DropSchemaObjectsSQLBuilder(self::$sharedConn->getDatabasePlatform()))->buildSQL($schema) as $s) {
                try {
                    $this->conn->executeStatement($s);
                } catch (\Throwable) {
                }
            }

            $this->updateSchema(self::$sharedConn, $schema);

            foreach ($this->getDbRecords() as $set) {
                $set->executeRecords(self::$sharedConn);
            }
        }

        $this->conn = self::$sharedConn;
        $this->sqlLoggerStack = new DebugStack();
        $this->conn->getConfiguration()->setSQLLogger($this->sqlLoggerStack);
    }

    private function updateSchema(Connection $connection, DbSchema $providedSchema): void
    {
        $schemaManager = $connection->createSchemaManager();
        $schemaDiff = $schemaManager->createComparator()
            ->compareSchemas($schemaManager->introspectSchema(), $providedSchema)
        ;

        $platform = $connection->getDatabasePlatform();

        if ($platform->supportsSchemas()) {
            foreach ($schemaDiff->getCreatedSchemas() as $schema) {
                $connection->executeStatement($platform->getCreateSchemaSQL($schema));
            }
        }

        if ($platform->supportsSequences()) {
            foreach ($schemaDiff->getAlteredSequences() as $sequence) {
                $connection->executeStatement($platform->getAlterSequenceSQL($sequence));
            }

            foreach ($schemaDiff->getCreatedSequences() as $sequence) {
                $connection->executeStatement($platform->getCreateSequenceSQL($sequence));
            }
        }

        foreach ($platform->getCreateTablesSQL($schemaDiff->getCreatedTables()) as $sql) {
            $connection->executeStatement($sql);
        }

        foreach ($schemaDiff->getAlteredTables() as $tableDiff) {
            foreach ($platform->getAlterTableSQL($tableDiff) as $sql) {
                $connection->executeStatement($sql);
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Ensure the connection is reset between class-runs
        self::resetSharedConn();
    }

    protected function setUpDbSchema(DbSchema $schema): void
    {
        $invoiceTable = $schema->createTable('invoice');
        $invoiceTable->addOption('collate', 'utf8_bin');
        $invoiceTable->addColumn('id', 'integer', ['notNull' => false]);
        $invoiceTable->addColumn('status', 'integer', ['notNull' => false]);
        $invoiceTable->addColumn('label', 'string', ['notNull' => false]);
        $invoiceTable->addColumn('customer', 'integer', ['notNull' => false]);
        $invoiceTable->setPrimaryKey(['id']);

        $customerTable = $schema->createTable('customer');
        $customerTable->addOption('collate', 'utf8_bin');
        $customerTable->addColumn('id', 'integer', ['notNull' => false]);
        $customerTable->addColumn('name', 'string', ['notNull' => false]);
        $customerTable->addColumn('birthday', 'date', ['notNull' => false]);
        $customerTable->setPrimaryKey(['id']);
    }

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        return [];
    }

    /**
     * Returns the string for the ConditionGenerator.
     */
    abstract protected function getQuery(): QueryBuilder;

    /**
     * Configure fields of the ConditionGenerator.
     */
    protected function configureConditionGenerator(ConditionGenerator $conditionGenerator): void
    {
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function assertRecordsAreFound(SearchCondition $condition, array $ids): void
    {
        $conditionGenerator = $this->getDbalFactory()->createConditionGenerator($this->getQuery(), $condition);
        $this->configureConditionGenerator($conditionGenerator);

        $qb = $conditionGenerator->getQueryBuilder();

        $conditionGenerator->apply();
        $result = $qb->execute();

        $paramsString = '';
        $platform = $this->conn->getDatabasePlatform();

        static $bindingToType = [
            ParameterType::NULL => 'string',
            ParameterType::STRING => 'string',
            ParameterType::INTEGER => 'integer',
            ParameterType::BINARY => 'binary',
            ParameterType::BOOLEAN => 'boolean',
            ParameterType::ASCII => 'string',
            ParameterType::LARGE_OBJECT => 'blob',
        ];

        foreach ($qb->getParameters() as $name => $value) {
            $type = $qb->getParameterType($name) ?? 'string';

            if (\is_int($type)) {
                $type = $bindingToType[$type];
            } elseif (\is_object($type)) {
                $type = Type::lookupName($type);
            }

            $paramsString .= sprintf("%s = '%s'\n", $name, Type::getType($type)->convertToDatabaseValue($value, $platform));
        }

        $rows = $result->fetchAllAssociative();
        $idRows = array_map(
            static fn ($value) => $value['id'],
            $rows
        );

        sort($ids);
        sort($idRows);

        self::assertEquals(
            $ids,
            array_merge([], array_unique($idRows)),
            sprintf("Found these records instead: \n%s\nWith Query: %s\nAnd params: %s", print_r($rows, true), $qb->getSQL(), $paramsString)
        );
    }

    /**
     * @param SearchCondition|ConditionGenerator $conditionOrWhere
     */
    protected function assertQueryIsExecutable($conditionOrWhere, string $expectedSql = '', ?array $parameters = null): void
    {
        if ($conditionOrWhere instanceof SearchCondition) {
            $conditionGenerator = $this->getDbalFactory()->createConditionGenerator($this->getQuery(), $conditionOrWhere);
            $this->configureConditionGenerator($conditionGenerator);
        } else {
            $conditionGenerator = $conditionOrWhere;
        }

        $qb = $conditionGenerator->getQueryBuilder();

        if ($expectedSql !== '') {
            QueryBuilderAssertion::assertQueryBuilderEquals(
                $conditionGenerator,
                $expectedSql,
                $parameters
            );
        } else {
            $conditionGenerator->apply();

            $this->addToAssertionCount(1);
        }

        $qb->execute();
    }

    protected function onNotSuccessfulTest(\Throwable $e): void
    {
        // Ignore deprecation warnings.
        if ($e instanceof AssertionFailedError || ($e instanceof Warning && mb_strpos($e->getMessage(), ' is deprecated,'))) {
            throw $e;
        }

        if (isset($this->sqlLoggerStack->queries) && \count($this->sqlLoggerStack->queries)) {
            $queries = '';
            $i = \count($this->sqlLoggerStack->queries);

            foreach (array_reverse($this->sqlLoggerStack->queries) as $query) {
                $params = array_map(
                    static function ($p) {
                        if (\is_object($p)) {
                            return $p::class;
                        }

                        return "'" . var_export($p, true) . "'";
                    },
                    $query['params'] ?: []
                );

                $queries .= ($i + 1) . ". SQL: '" . $query['sql'] . "' Params: " . implode(', ', $params) . \PHP_EOL;
                --$i;
            }

            $trace = $e->getTrace();
            $traceMsg = '';

            foreach ($trace as $part) {
                if (isset($part['file'])) {
                    if (mb_strpos($part['file'], 'PHPUnit/') !== false) {
                        // Beginning with PHPUnit files we don't print the trace anymore.
                        break;
                    }

                    $traceMsg .= $part['file'] . ':' . $part['line'] . \PHP_EOL;
                }
            }

            $message =
                '[' . $e::class . '] ' .
                $e->getMessage() .
                \PHP_EOL . \PHP_EOL .
                'With queries:' . \PHP_EOL .
                $queries . \PHP_EOL .
                'Trace:' . \PHP_EOL .
                $traceMsg;

            throw new Exception($message, (int) $e->getCode(), $e);
        }

        throw $e;
    }
}
