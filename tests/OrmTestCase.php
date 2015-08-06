<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Tests\TestUtil;
use Rollerworks\Component\Search\Doctrine\Orm\AbstractWhereBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\DbalTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;

class OrmTestCase extends DbalTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @var \Doctrine\DBAL\Logging\DebugStack
     */
    protected $sqlLoggerStack;

    /**
     * Shared connection when a TestCase is run alone (outside of it's functional suite).
     *
     * @var \Doctrine\DBAL\Connection
     */
    private static $sharedConn;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private static $sharedEm;

    /**
     * @var string|null
     */
    protected $query;

    protected function setUp()
    {
        parent::setUp();

        if (!isset(self::$sharedConn)) {
            $GLOBALS['db_event_subscribers'] = 'Rollerworks\Component\Search\Doctrine\Dbal\EventSubscriber\SqliteConnectionSubscriber';

            $config = Setup::createAnnotationMetadataConfiguration([__DIR__.'/Fixtures/Entity'], true, null, null, false);
            $config->addCustomStringFunction(
                'RW_SEARCH_FIELD_CONVERSION',
                'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlFieldConversion'
            );

            $config->addCustomStringFunction(
                'RW_SEARCH_VALUE_CONVERSION',
                'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlValueConversion'
            );

            $config->addCustomStringFunction(
                'RW_SEARCH_MATCH',
                'Rollerworks\Component\Search\Doctrine\Orm\Functions\ValueMatch'
            );

            self::$sharedConn = TestUtil::getConnection();
            self::$sharedEm = EntityManager::create(self::$sharedConn, $config);

            $schemaTool = new SchemaTool(self::$sharedEm);
            $schemaTool->updateSchema(self::$sharedEm->getMetadataFactory()->getAllMetadata(), false);

            $recordSets = $this->getDbRecords();

            foreach ($recordSets as $set) {
                $set->executeRecords(self::$sharedConn);
            }
        }

        $this->conn = self::$sharedConn;
        $this->em = self::$sharedEm;

        // Clear the cache between runs
        $this->em->getConfiguration()->getQueryCacheImpl()->flushAll();

        $this->sqlLoggerStack = new \Doctrine\DBAL\Logging\DebugStack();
        $this->conn->getConfiguration()->setSQLLogger($this->sqlLoggerStack);
    }

    protected static function resetSharedConn()
    {
        if (self::$sharedConn) {
            self::$sharedConn->close();
            self::$sharedConn = null;
            self::$sharedEm = null;
        }
    }

    public static function tearDownAfterClass()
    {
        // Ensure the connection is reset between class-runs
        self::resetSharedConn();
    }

    protected function getFieldSet($build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder('invoice');

        $invoiceClass = 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice';
        $invoiceRowClass = 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoiceRow';
        $customerClass = 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer';

        $fieldSet->add('id', 'integer', [], false, $invoiceClass, 'id');
        $fieldSet->add('label', 'invoice_label', [], false, $invoiceClass, 'label');
        $fieldSet->add('status', 'integer', [], false, $invoiceClass, 'status');
        $fieldSet->add('credit_parent', 'integer', [], false, $invoiceClass, 'parent');

        $fieldSet->add('row_label', 'text', [], false, $invoiceRowClass, 'label');

        $fieldSet->add('customer', 'integer', [], false, $customerClass, 'id');
        $fieldSet->add('customer_name', 'text', [], false, $customerClass, 'name');
        $fieldSet->add('customer_birthday', 'birthday', ['format' => 'yyyy-MM-dd'], false, $customerClass, 'birthday');

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    protected function getOrmFactory()
    {
        return new DoctrineOrmFactory($this->getMock('Doctrine\Common\Cache\Cache'));
    }

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        return [];
    }

    /**
     * Returns the string for the WhereBuilder.
     *
     * @return Query|NativeQuery
     */
    protected function getQuery()
    {
    }

    /**
     * Configure fields of the WhereBuilder.
     *
     * @param AbstractWhereBuilder $whereBuilder
     */
    protected function configureWhereBuilder(AbstractWhereBuilder $whereBuilder)
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
        $query = $this->getQuery();

        $whereBuilder = $this->getOrmFactory()->createWhereBuilder($query, $condition);
        $this->configureWhereBuilder($whereBuilder);

        $whereClause = $whereBuilder->getWhereClause();
        $whereBuilder->updateQuery();

        $rows = $query->getArrayResult();
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
            array_merge([], array_unique($idRows)),
            sprintf("Found these records instead: \n%s\nWith WHERE-clause: %s", print_r($rows, true), $whereClause)
        );
    }

    protected function onNotSuccessfulTest(\Exception $e)
    {
        if ($e instanceof \PHPUnit_Framework_AssertionFailedError) {
            throw $e;
        }

        if (isset($this->sqlLoggerStack->queries) && count($this->sqlLoggerStack->queries)) {
            $queries = '';
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
                    $query['params'] ?: []
                );

                $queries .= ($i + 1).". SQL: '".$query['sql']."' Params: ".implode(', ', $params).PHP_EOL;
                --$i;
            }

            $trace = $e->getTrace();
            $traceMsg = '';

            foreach ($trace as $part) {
                if (isset($part['file'])) {
                    if (strpos($part['file'], 'PHPUnit/') !== false) {
                        // Beginning with PHPUnit files we don't print the trace anymore.
                        break;
                    }

                    $traceMsg .= $part['file'].':'.$part['line'].PHP_EOL;
                }
            }

            $message =
                '['.get_class($e).'] '.
                $e->getMessage().
                PHP_EOL.PHP_EOL.
                'With queries:'.PHP_EOL.
                $queries.PHP_EOL.
                'Trace:'.PHP_EOL.
                $traceMsg;

            throw new \Exception($message, (int) $e->getCode(), $e);
        }

        throw $e;
    }
}
