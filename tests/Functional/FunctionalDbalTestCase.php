<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional;

use Doctrine\DBAL\Schema\Schema as DbSchema;
use Doctrine\Tests\TestUtil;
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\DbalTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;

abstract class FunctionalDbalTestCase extends DbalTestCase
{
    /**
     * Shared connection when a TestCase is run alone (outside of it's functional suite)
     *
     * @var \Doctrine\DBAL\Connection
     */
    private static $sharedConn;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @var \Doctrine\DBAL\Logging\DebugStack
     */
    protected $sqlLoggerStack;

    /**
     * @var string|null
     */
    protected $query;

    protected static function resetSharedConn()
    {
        if (self::$sharedConn) {
            self::$sharedConn->close();
            self::$sharedConn = null;
        }
    }

    protected function setUp()
    {
        parent::setUp();

        if (!isset(self::$sharedConn)) {
            self::$sharedConn = TestUtil::getConnection();

            $schema = new DbSchema();
            $this->setUpDbSchema($schema);

            $platform = self::$sharedConn->getDatabasePlatform();
            $queries = $schema->toSql($platform);

            foreach ($queries as $query) {
                self::$sharedConn->exec($query);
            }

            $recordSets = $this->getDbRecords();

            foreach ($recordSets as $set) {
                $set->executeRecords(self::$sharedConn);
            }
        }

        $this->conn = self::$sharedConn;
        $this->sqlLoggerStack = new \Doctrine\DBAL\Logging\DebugStack();
        $this->conn->getConfiguration()->setSQLLogger($this->sqlLoggerStack);
    }

    public static function tearDownAfterClass()
    {
        // Ensure the connection is reset between class-runs
        self::resetSharedConn();
    }

    protected function setUpDbSchema(DbSchema $schema)
    {
        $invoiceTable = $schema->createTable('invoice');
        $invoiceTable->addColumn('id', 'integer');
        $invoiceTable->addColumn('status', 'integer');
        $invoiceTable->addColumn('label', 'string');
        $invoiceTable->addColumn('customer', 'integer');
        $invoiceTable->setPrimaryKey(array('id'));

        $customerTable = $schema->createTable('customer');
        $customerTable->addColumn('id', 'integer');
        $customerTable->addColumn('name', 'string');
        $customerTable->addColumn('birthday', 'date');
        $customerTable->setPrimaryKey(array('id'));
    }

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        return array();
    }

    /**
     * Returns the string for the WhereBuilder
     *
     * @return string
     */
    protected function getQuery()
    {
    }

    /**
     * Configure fields of the WhereBuilder.
     *
     * @param WhereBuilder $whereBuilder
     */
    protected function configureWhereBuilder(WhereBuilder $whereBuilder)
    {
    }

    /**
     * @param SearchCondition $condition
     * @param array           $ids
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function assertRecordsAreFound(SearchCondition $condition, array $ids)
    {
        $whereBuilder = $this->getDbalFactory()->createWhereBuilder($this->conn, $condition);
        $this->configureWhereBuilder($whereBuilder);

        $whereClause = $whereBuilder->getWhereClause();
        $statement = $this->conn->query($this->getQuery().$whereClause);

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $idRows = array_map(
            function ($value) {
                return $value['id'];
            },
            $rows
        );

        sort($ids);
        sort($idRows);

        $this->assertEquals(
            $ids,
            array_merge(array(), array_unique($idRows)),
            sprintf("Found these records instead: \n%s\nWith WHERE-clause: %s", print_r($rows, true), $whereClause)
        );
    }

    protected function assertQueryIsExecutable($conditionOrWhere)
    {
        if ($conditionOrWhere instanceof SearchCondition) {
            $whereBuilder = $this->getDbalFactory()->createWhereBuilder($this->conn, $conditionOrWhere);
            $this->configureWhereBuilder($whereBuilder);
        } else {
            $whereBuilder = $conditionOrWhere;
        }

        $whereClause = $whereBuilder->getWhereClause();

        $this->assertNotNull($this->conn->query($this->getQuery().$whereClause));
    }

    protected function onNotSuccessfulTest(\Exception $e)
    {
        if ($e instanceof \PHPUnit_Framework_AssertionFailedError) {
            throw $e;
        }

        if(isset($this->sqlLoggerStack->queries) && count($this->sqlLoggerStack->queries)) {
            $queries = "";
            $i = count($this->sqlLoggerStack->queries);

            foreach (array_reverse($this->sqlLoggerStack->queries) as $query) {
                $params = array_map(
                    function ($p) {
                        if (is_object($p)) {
                            return get_class($p);
                        } else {
                            return "'".var_export($p, true)."'";
                        }
                    },
                    $query['params'] ?: array()
                );

                $queries .= ($i+1).". SQL: '".$query['sql']."' Params: ".implode(", ", $params).PHP_EOL;
                $i--;
            }

            $trace = $e->getTrace();
            $traceMsg = '';

            foreach($trace as $part) {
                if(isset($part['file'])) {
                    if(strpos($part['file'], "PHPUnit/") !== false) {
                        // Beginning with PHPUnit files we don't print the trace anymore.
                        break;
                    }

                    $traceMsg .= $part['file'].":".$part['line'].PHP_EOL;
                }
            }

            $message =
                "[".get_class($e)."] ".
                $e->getMessage().
                PHP_EOL.PHP_EOL.
                "With queries:".PHP_EOL.
                $queries.PHP_EOL.
                "Trace:".PHP_EOL.
                $traceMsg;

            throw new \Exception($message, (int) $e->getCode(), $e);
        }

        throw $e;
    }
}
