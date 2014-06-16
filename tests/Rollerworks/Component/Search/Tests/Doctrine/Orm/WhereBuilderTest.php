<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder;
use Rollerworks\Component\Search\Tests\Fixtures\CustomerId;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesGroup;

class WhereBuilderTest extends OrmTestCase
{
    /**
     * @dataProvider provideSimpleQueryTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query
     */
    public function testDqlQuery($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $query = $this->em->createQuery($extra['query']);

        $whereBuilder = new WhereBuilder($query, $condition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[1], $whereCase);
            $expectedDql = $expectWhereCase[1];
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
            $expectedDql = $expectWhereCase;
        }

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (null !== $expectSql)));
    }

    /**
     * @dataProvider provideSimpleQueryTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query
     */
    public function testNativeQuery($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice', 'I', array('id' => 'invoice_id'));
        $rsm->addJoinedEntityFromClassMetadata('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer', 'C', 'I', 'customer', array('id' => 'customer_id'));
        $query = $this->em->createNativeQuery('SELECT I FROM invoice I JOIN I.customer C WHERE ', $rsm);

        $whereBuilder = new WhereBuilder($query, $condition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[0], $whereCase);
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
        }

        if (!$valuesEmbedding) {
            $this->assertQueryParamsEquals($queryParams, $query);
        } else {
            $this->assertCount(0, $whereBuilder->getParameters());
        }
    }

    public function testEmptyResult()
    {
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');

        $searchCondition = new SearchCondition(static::getFieldSet('invoice'), new ValuesGroup());

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $whereCase = $whereBuilder->getWhereClause();
        $this->assertEquals('', $whereCase);
        $this->assertCount(0, $query->getParameters());
    }

    public function testUpdateQuery()
    {
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C');

        $searchCondition = new SearchCondition($this->getFieldSet('invoice'), SearchConditionBuilder::create()
            ->field('invoice_customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getGroup());

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $whereBuilder->updateQuery(' WHERE ');
        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals('(((C.id IN(:invoice_customer_0))))', $whereCase);
        $this->assertQueryParamsEquals(array('invoice_customer_0' => 2), $query);
        $this->assertEquals('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE (((C.id IN(:invoice_customer_0))))', $query->getDQL());

        // Ensure the query is not updated again
        $whereBuilder->updateQuery(' WHERE ');

        $this->assertQueryParamsEquals(array('invoice_customer_0' => 2), $query);
        $this->assertEquals('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE (((C.id IN(:invoice_customer_0))))', $query->getDQL());
    }

    public function testUpdateQueryWithQueryBuilder()
    {
        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice', 'I')
            ->join('I.customer', 'C');

        $searchCondition = new SearchCondition($this->getFieldSet('invoice'), SearchConditionBuilder::create()
            ->field('invoice_customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getGroup());

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $whereBuilder->updateQuery();
        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals('(((C.id IN(:invoice_customer_0))))', $whereCase);
        $this->assertQueryParamsEquals(array('invoice_customer_0' => 2), $query);
        $this->assertEquals('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I INNER JOIN I.customer C WHERE (((C.id IN(:invoice_customer_0))))', $query->getDQL());
    }

    public function testUpdateQueryWithQueryBuilderAndExistingWhere()
    {
        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice', 'I')
            ->join('I.customer', 'C')
            ->where('I.customer = 5')
        ;

        $searchCondition = new SearchCondition($this->getFieldSet('invoice'), SearchConditionBuilder::create()
            ->field('invoice_customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getGroup());

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $whereBuilder->updateQuery();
        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals('(((C.id IN(:invoice_customer_0))))', $whereCase);
        $this->assertQueryParamsEquals(array('invoice_customer_0' => 2), $query);
        $this->assertEquals('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I INNER JOIN I.customer C WHERE I.customer = 5 AND (((C.id IN(:invoice_customer_0))))', $query->getDQL());
    }

    public function testUpdateQueryWithNoResult()
    {
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C");

        $searchCondition = new SearchCondition($this->getFieldSet('invoice'), new ValuesGroup());

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $whereCase = $whereBuilder->getWhereClause();
        $this->assertEquals('', $whereCase);
        $this->assertCount(0, $query->getParameters());

        $whereBuilder->updateQuery(' WHERE ');
        $this->assertEquals('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C', $query->getDQL());
    }

    /**
     * @dataProvider provideValueConversionTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query
     */
    public function testValueConversion($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $query = $this->em->createQuery($extra['query']);

        $whereBuilder = new WhereBuilder($query, $condition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $test = $this;
        $optionsForCustomer = isset($extra['options_for_customer']) ? $extra['options_for_customer'] : array();

        $converter = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->will($this->returnCallback(function (CustomerId $input, array $options) use ($test, $optionsForCustomer) {
                $test->assertEquals($optionsForCustomer, $options);

                return intval($input->getCustomerId());
            }))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('requiresBaseConversion')
            ->will($this->returnValue(false))
        ;

        $whereBuilder->setConverter('customer_id', $converter);

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[1], $whereCase);
            $expectedDql = $expectWhereCase[1];
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
            $expectedDql = $expectWhereCase;
        }

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (null !== $expectSql)));
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query
     */
    public function testFieldConversion($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $query = $this->em->createQuery($extra['query']);

        $whereBuilder = new WhereBuilder($query, $condition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $test = $this;
        $optionsForCustomer = isset($extra['options_for_customer']) ? $extra['options_for_customer'] : array();

        $converter = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface');
        $converter
            ->expects($condition->getValuesGroup()->hasField('invoice_customer') ? $this->atLeastOnce() : $this->never())
            ->method('convertSqlField')
            ->will($this->returnCallback(function ($column, array $options) use ($test, $optionsForCustomer) {
                $test->assertEquals($optionsForCustomer, $options);

                return "CAST($column AS customer_type)";
            }))
        ;

        $whereBuilder->setConverter('invoice_customer', $converter);

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[1], $whereCase);
            $expectedDql = $expectWhereCase[1];
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
            $expectedDql = $expectWhereCase;
        }

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (null !== $expectSql)));
    }

    /**
     * @dataProvider provideSqlValueConversionTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query
     */
    public function testSqlValueConversion($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $query = $this->em->createQuery($extra['query']);

        $whereBuilder = new WhereBuilder($query, $condition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $test = $this;

        $optionsForCustomer = isset($extra['options_for_customer']) ? $extra['options_for_customer'] : array();
        $negative = isset($extra['negative']) ? $extra['negative'] : false;
        $valueReqEmbedding = isset($extra['value_embedding']) ? $extra['value_embedding'] : false;
        $convertField = isset($extra['convert_field']) ? $extra['convert_field'] : 'customer_id';

        $converter = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface');
        $converter
            ->expects(($negative ? $this->never() : $this->atLeastOnce()))
            ->method('convertSqlValue')
            ->will($this->returnCallback(function ($input, array $options) use ($test, $optionsForCustomer) {
                $test->assertEquals($optionsForCustomer, $options);

                if ($options) {
                    return "get_customer_type($input, '" . json_encode($options) . "')";
                }

                return "get_customer_type($input)";
            }))
        ;

        $converter
            ->expects(($negative ? $this->never() : $this->atLeastOnce()))
            ->method('valueRequiresEmbedding')
            ->will($this->returnValue($valueReqEmbedding))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('requiresBaseConversion')
            ->will($this->returnValue(false))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->will($this->returnArgument(0))
        ;

        $whereBuilder->setConverter($convertField, $converter);

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[1], $whereCase);
            $expectedDql = $expectWhereCase[1];
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
            $expectedDql = $expectWhereCase;
        }

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (null !== $expectSql)));
    }

    /**
     * @dataProvider provideValueConversionStrategyTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query
     */
    public function testValueConversionStrategy($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $query = $this->em->createQuery($extra['query']);

        $whereBuilder = new WhereBuilder($query, $condition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\User' => 'u')
        );

        $test = $this;

        $expectedOptions = isset($extra['options']) ? $extra['options'] : array();
        $negative = isset($extra['negative']) ? $extra['negative'] : false;
        $valueReqEmbedding = isset($extra['value_embedding']) ? $extra['value_embedding'] : false;

        $converter = $this->getMock('Rollerworks\Component\Search\Tests\Doctrine\Dbal\SqlValueConversionStrategyInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('getConversionStrategy')
            ->will($this->returnCallback(function ($value) {
                   if (!$value instanceof \DateTime && !is_int($value)) {
                       throw new \InvalidArgumentException('Only integer/string and DateTime are accepted.');
                   }

                   if ($value instanceof \DateTime) {
                       return 2;
                   }

                   return 1;
            }))
        ;

        $converter
            ->expects(($negative ? $this->never() : $this->atLeastOnce()))
            ->method('convertSqlValue')
            ->will($this->returnCallback(function ($input, array $options, array $hints) use ($test, $expectedOptions, $valueReqEmbedding) {
               $test->assertArrayHasKey('conversion_strategy', $hints);
               $test->assertArrayHasKey('connection', $hints);
               $test->assertEquals($expectedOptions, $options);

               if (2 === $hints['conversion_strategy']) {
                   if ($hints['value_embedded'] || $valueReqEmbedding) {
                       return "CAST(".$hints['connection']->quote($input)." AS DATE)";
                   }

                   return "CAST($input AS DATE)";
               }

               $test->assertEquals(1, $hints['conversion_strategy']);

               return $input;
            }))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->will($this->returnCallback(function ($input, array $options, array $hints) use ($test, $expectedOptions, $valueReqEmbedding) {
               $test->assertArrayHasKey('conversion_strategy', $hints);
               $test->assertArrayHasKey('value_embedded', $hints);
               $test->assertEquals($expectedOptions, $options);

               if ($input instanceof \DateTime) {
                   $test->assertEquals(2, $hints['conversion_strategy']);
               } else {
                   $test->assertEquals(1, $hints['conversion_strategy']);
               }

               if ($input instanceof \DateTime && ($hints['value_embedded'] || $valueReqEmbedding)) {
                   $input = $input->format('Y-m-d');
               }

               return $input;
            }))
        ;

        $converter
            ->expects($negative ? $this->never() : $this->atLeastOnce())
            ->method('valueRequiresEmbedding')
            ->will($this->returnCallback(function ($input, array $options, array $hints) use ($test, $expectedOptions, $valueReqEmbedding) {
               $test->assertArrayHasKey('conversion_strategy', $hints);
               $test->assertEquals($expectedOptions, $options);

               if ($input instanceof \DateTime || !is_integer($input)) {
                   $test->assertEquals(2, $hints['conversion_strategy']);
               } else {
                   $test->assertEquals(1, $hints['conversion_strategy']);
               }

               return $valueReqEmbedding;
            }))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('requiresBaseConversion')
            ->will($this->returnValue(false))
        ;

        $whereBuilder->setConverter('user_birthday', $converter);

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[1], $whereCase);
            $expectedDql = $expectWhereCase[1];
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
            $expectedDql = $expectWhereCase;
        }

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (null !== $expectSql)));
    }

    /**
     * @dataProvider provideFieldConversionStrategyTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query
     */
    public function testFieldConversionStrategy($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $query = $this->em->createQuery($extra['query']);

        $whereBuilder = new WhereBuilder($query, $condition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\User' => 'u')
        );

        $test = $this;

        $expectedOptions = isset($extra['options']) ? $extra['options'] : array();
        $negative = isset($extra['negative']) ? $extra['negative'] : false;
        $valueReqEmbedding = isset($extra['value_embedding']) ? $extra['value_embedding'] : false;

        $converter = $this->getMock('Rollerworks\Component\Search\Tests\Doctrine\Dbal\SqlFieldConversionStrategyInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('getConversionStrategy')
            ->will($this->returnCallback(function ($value) {
                   if (!is_string($value) && !is_int($value)) {
                       throw new \InvalidArgumentException('Only integer and string are accepted.');
                   }

                   if (is_int($value)) {
                       return 2;
                   }

                   return 1;
            }))
        ;

        $converter
            ->expects(($negative ? $this->never() : $this->atLeastOnce()))
            ->method('convertSqlField')
            ->will($this->returnCallback(function ($column, array $options, array $hints) use ($test, $expectedOptions, $valueReqEmbedding) {
               $test->assertArrayHasKey('conversion_strategy', $hints);
               $test->assertArrayHasKey('connection', $hints);
               $test->assertEquals($expectedOptions, $options);

               if (2 === $hints['conversion_strategy']) {
                   return "search_conversion_age($column)";
               }

               $test->assertEquals(1, $hints['conversion_strategy']);

               return $column;
            }))
        ;

        $whereBuilder->setConverter('user_birthday', $converter);

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[1], $whereCase);
            $expectedDql = $expectWhereCase[1];
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
            $expectedDql = $expectWhereCase;
        }

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (null !== $expectSql)));
    }

    public function testDoctrineAlias()
    {
        $config = $this->em->getConfiguration();
        $config->addEntityNamespace('ECommerce', 'Rollerworks\Component\Search\Tests\Fixtures\Entity');

        $query = $this->em->createQuery('SELECT I FROM ECommerce:ECommerceInvoice I JOIN I.customer C WHERE ');

        $searchCondition = new SearchCondition($this->getFieldSet('invoice'), SearchConditionBuilder::create()
            ->field('invoice_customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getGroup());

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'ECommerce:ECommerceInvoice' => 'I',
            'ECommerce:ECommerceCustomer' => 'C')
        );

        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals('(((C.id IN(:invoice_customer_0))))', $whereCase);
        $this->assertQueryParamsEquals(array('invoice_customer_0' => 2), $query);
        $this->assertDqlCompiles($query, $whereBuilder);
    }

    /**
     * @param Query        $query
     * @param WhereBuilder $whereBuilder
     * @param boolean      $return
     * @param boolean      $prepend
     *
     * @return string
     */
    protected function assertDqlCompiles(Query $query, WhereBuilder $whereBuilder, $return = false, $prepend = true)
    {
        $whereClause = $whereBuilder->getWhereClause();

        if ('' === $whereClause) {
            return '';
        }

        $query->setHint($whereBuilder->getQueryHintName(), $whereBuilder->getQueryHintValue());

        if ($prepend) {
            $dql = $query->getDQL().$whereClause;
            $query->setDQL($dql);
        }

        try {
            if ($return) {
                return $query->getSQL();
            }

            $query->getSQL();
        } catch (QueryException $e) {
            $this->fail('compile error:'.$e->getMessage().' with Query: '.$query->getDQL());
        }
    }
}
