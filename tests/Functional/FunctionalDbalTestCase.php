<?php

/**
 * PhpStorm.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional;

use Doctrine\Tests\TestUtil;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\DbalTestCase;

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

    protected function resetSharedConn()
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

            $schema = new \Doctrine\DBAL\Schema\Schema();

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

            $platform = self::$sharedConn->getDatabasePlatform();
            $queries = $schema->toSql($platform);

            foreach ($queries as $query) {
                self::$sharedConn->exec($query);
            }
        }

        $this->conn = self::$sharedConn;
        $this->sqlLoggerStack = new \Doctrine\DBAL\Logging\DebugStack();
        $this->conn->getConfiguration()->setSQLLogger($this->sqlLoggerStack);
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
