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

use Doctrine\DBAL\Schema\Schema as DbSchema;
use Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer;
use Doctrine\Tests\TestUtil;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\Warning;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\DbalTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;

abstract class FunctionalDbalTestCase extends DbalTestCase
{
    /**
     * Shared connection when a TestCase is run alone (outside of it's functional suite).
     *
     * @var \Doctrine\DBAL\Connection|null
     */
    private static $sharedConn;

    /**
     * @var \Doctrine\DBAL\Connection|null
     */
    protected $conn;

    /**
     * @var \Doctrine\DBAL\Logging\DebugStack|null
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
            static::markTestSkipped('GLOBAL variables not enabled');
        }

        if (! isset(self::$sharedConn)) {
            self::$sharedConn = TestUtil::getConnection();

            $schema = new DbSchema();
            $this->setUpDbSchema($schema);

            $databaseSynchronizer = new SingleDatabaseSynchronizer(self::$sharedConn);
            $databaseSynchronizer->dropAllSchema();
            $databaseSynchronizer->updateSchema($schema);

            $recordSets = $this->getDbRecords();

            foreach ($recordSets as $set) {
                $set->executeRecords(self::$sharedConn);
            }
        }

        $this->conn = self::$sharedConn;
        $this->sqlLoggerStack = new \Doctrine\DBAL\Logging\DebugStack();
        $this->conn->getConfiguration()->setSQLLogger($this->sqlLoggerStack);
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
        $invoiceTable->addColumn('id', 'integer');
        $invoiceTable->addColumn('status', 'integer');
        $invoiceTable->addColumn('label', 'string');
        $invoiceTable->addColumn('customer', 'integer');
        $invoiceTable->setPrimaryKey(['id']);

        $customerTable = $schema->createTable('customer');
        $customerTable->addOption('collate', 'utf8_bin');
        $customerTable->addColumn('id', 'integer');
        $customerTable->addColumn('name', 'string');
        $customerTable->addColumn('birthday', 'date');
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
     *
     * @return string
     */
    protected function getQuery()
    {
    }

    /**
     * Configure fields of the ConditionGenerator.
     */
    protected function configureConditionGenerator(ConditionGenerator $conditionGenerator): void
    {
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function assertRecordsAreFound(SearchCondition $condition, array $ids): void
    {
        $conditionGenerator = $this->getDbalFactory()->createConditionGenerator($this->conn, $condition);
        $this->configureConditionGenerator($conditionGenerator);

        $whereClause = $conditionGenerator->getWhereClause();
        $statement = $this->conn->prepare($this->getQuery() . $whereClause);

        $paramsString = '';
        $platform = $this->conn->getDatabasePlatform();

        foreach ($conditionGenerator->getParameters() as $name => [$value, $type]) {
            $statement->bindValue($name, $value, $type);

            $paramsString .= \sprintf("%s = '%s'\n", $name, $type === null ? (\is_scalar($value) ? (string) $value : get_debug_type($value)) : $type->convertToDatabaseValue($value, $platform));
        }

        $statement->execute();

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $idRows = \array_map(
            static function ($value) {
                return $value['id'];
            },
            $rows
        );

        \sort($ids);
        \sort($idRows);

        static::assertEquals(
            $ids,
            \array_merge([], \array_unique($idRows)),
            \sprintf("Found these records instead: \n%s\nWith WHERE-clause: %s\nAnd params: %s", \print_r($rows, true), $whereClause, $paramsString)
        );
    }

    protected function assertQueryIsExecutable($conditionOrWhere, string $expectedSql = '', ?array $parameters = null): void
    {
        if ($conditionOrWhere instanceof SearchCondition) {
            $conditionGenerator = $this->getDbalFactory()->createConditionGenerator($this->conn, $conditionOrWhere);
            $this->configureConditionGenerator($conditionGenerator);
        } else {
            $conditionGenerator = $conditionOrWhere;
        }

        $whereClause = $conditionGenerator->getWhereClause();
        $statement = $this->conn->prepare($this->getQuery() . $whereClause);

        $conditionGenerator->bindParameters($statement);
        $statement->execute();

        static::assertNotNull($statement);

        if ($expectedSql !== '') {
            $expectedSql = \preg_replace('/\s+/', ' ', \trim($expectedSql));

            static::assertEquals($expectedSql, \preg_replace('/\s+/', ' ', \trim($whereClause)));
        }

        if ($parameters !== null) {
            static::assertEquals($parameters, $conditionGenerator->getParameters()->toArray());
        }
    }

    protected function onNotSuccessfulTest(\Throwable $e): void
    {
        // Ignore deprecation warnings.
        if ($e instanceof AssertionFailedError || ($e instanceof Warning && \mb_strpos($e->getMessage(), ' is deprecated,'))) {
            throw $e;
        }

        if (isset($this->sqlLoggerStack->queries) && \count($this->sqlLoggerStack->queries)) {
            $queries = '';
            $i = \count($this->sqlLoggerStack->queries);

            foreach (\array_reverse($this->sqlLoggerStack->queries) as $query) {
                $params = \array_map(
                    static function ($p) {
                        if (\is_object($p)) {
                            return \get_class($p);
                        }

                        return "'" . \var_export($p, true) . "'";
                    },
                    $query['params'] ?: []
                );

                $queries .= ($i + 1) . ". SQL: '" . $query['sql'] . "' Params: " . \implode(', ', $params) . \PHP_EOL;
                --$i;
            }

            $trace = $e->getTrace();
            $traceMsg = '';

            foreach ($trace as $part) {
                if (isset($part['file'])) {
                    if (\mb_strpos($part['file'], 'PHPUnit/') !== false) {
                        // Beginning with PHPUnit files we don't print the trace anymore.
                        break;
                    }

                    $traceMsg .= $part['file'] . ':' . $part['line'] . \PHP_EOL;
                }
            }

            $message =
                '[' . \get_class($e) . '] ' .
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
