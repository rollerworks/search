<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Tests\Fixtures\CustomerId;
use Rollerworks\Component\Search\ValuesGroup;

class WhereBuilderTest extends DbalTestCase
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
    public function testSimpleQuery($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $connection = $this->getConnectionMock();

        if (!isset($extra['dbal_mapping'])) {
            $extra['dbal_mapping'] = array(
                'invoice_customer' => array('customer', 'integer', 'I')
            );
        }

        $whereBuilder = new WhereBuilder($connection, $condition);

        foreach ($extra['dbal_mapping'] as $field => $mapping) {
            $whereBuilder->setField($field, $mapping[0], $mapping[1], $mapping[2]);
        }

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[0], $whereCase);
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
        }

        if (!$valuesEmbedding) {
            $this->asserParamsEquals($queryParams, $whereBuilder);
        } else {
            $this->assertCount(0, $whereBuilder->getParameters());
            $this->assertCount(0, $whereBuilder->getParameterTypes());
        }

        // dummy to stop detection of none used-param
        if (null !== $expectSql) {
            $this->assertNotNull($expectSql);
        }
    }

    public function testEmptyResult()
    {
        $connection = $this->getConnectionMock();

        $condition = new SearchCondition($this->getFieldSet('invoice'), new ValuesGroup());
        $whereBuilder = new WhereBuilder($connection, $condition);
        $whereBuilder->setField('invoice_customer', 'customer', 'integer', 'I');

        $whereCase = $whereBuilder->getWhereClause();
        $this->assertEquals('', $whereCase);
        $this->assertCount(0, $whereBuilder->getParameters());
        $this->assertCount(0, $whereBuilder->getParameterTypes());
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
        $connection = $this->getConnectionMock();

        if (!isset($extra['dbal_mapping'])) {
            $extra['dbal_mapping'] = array(
                'customer_id' => array('id', 'integer', 'C')
            );
        }

        $whereBuilder = new WhereBuilder($connection, $condition);

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

        foreach ($extra['dbal_mapping'] as $field => $mapping) {
            $whereBuilder->setField($field, $mapping[0], $mapping[1], $mapping[2]);
        }

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[0], $whereCase);
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
        }

        if (!$valuesEmbedding) {
            $this->asserParamsEquals($queryParams, $whereBuilder);
        } else {
            $this->assertCount(0, $whereBuilder->getParameters());
            $this->assertCount(0, $whereBuilder->getParameterTypes());
        }

        // dummy to stop detection of none used-param
        if (null !== $expectSql) {
            $this->assertNotNull($expectSql);
        }
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
        $connection = $this->getConnectionMock();

        if (!isset($extra['dbal_mapping'])) {
            $extra['dbal_mapping'] = array(
                'invoice_customer' => array('customer', 'integer', 'I')
            );
        }

        $whereBuilder = new WhereBuilder($connection, $condition);

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

        foreach ($extra['dbal_mapping'] as $field => $mapping) {
            $whereBuilder->setField($field, $mapping[0], $mapping[1], $mapping[2]);
        }

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[0], $whereCase);
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
        }

        if (!$valuesEmbedding) {
            $this->asserParamsEquals($queryParams, $whereBuilder);
        } else {
            $this->assertCount(0, $whereBuilder->getParameters());
            $this->assertCount(0, $whereBuilder->getParameterTypes());
        }

        // dummy to stop detection of none used-param
        if (null !== $expectSql) {
            $this->assertNotNull($expectSql);
        }
    }

    /**
     * @dataProvider provideSqlValueConversionTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query, negative
     */
    public function testSqlValueConversion($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $connection = $this->getConnectionMock();

        if (!isset($extra['dbal_mapping'])) {
            $extra['dbal_mapping'] = array(
                'customer_id' => array('id', 'integer', 'C')
            );
        }

        $whereBuilder = new WhereBuilder($connection, $condition);

        $test = $this;

        $optionsForCustomer = isset($extra['options_for_customer']) ? $extra['options_for_customer'] : array();
        $negative = isset($extra['negative']) ? $extra['negative'] : false;
        $valueReqEmbedding = isset($extra['value_embedding']) ? $extra['value_embedding'] : false;
        $ignoreParameters = isset($extra['ignore_parameters_dbal']) ? $extra['ignore_parameters_dbal'] : array();
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
            ->expects(($negative || $valuesEmbedding ? $this->never() : $this->atLeastOnce()))
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

        foreach ($extra['dbal_mapping'] as $field => $mapping) {
            $whereBuilder->setField($field, $mapping[0], $mapping[1], $mapping[2]);
        }

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[0], $whereCase);
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
        }

        if (!$valuesEmbedding) {
            $this->asserParamsEquals($queryParams, $whereBuilder, $ignoreParameters);
        } else {
            $this->assertCount(0, $whereBuilder->getParameters());
            $this->assertCount(0, $whereBuilder->getParameterTypes());
        }

        // dummy to stop detection of none used-param
        if (null !== $expectSql) {
            $this->assertNotNull($expectSql);
        }
    }

    /**
     * @dataProvider provideValueConversionStrategyTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query, negative
     */
    public function testValueConversionStrategy($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $connection = $this->getConnectionMock();

        if (!isset($extra['dbal_mapping'])) {
            $extra['dbal_mapping'] = array(
                // this is actually not recommended, but field conversion is tested later
                'user_birthday' => array('birthday', 'integer', 'u')
            );
        }

        $whereBuilder = new WhereBuilder($connection, $condition);

        $test = $this;

        $expectedOptions = isset($extra['options']) ? $extra['options'] : array();
        $negative = isset($extra['negative']) ? $extra['negative'] : false;
        $valueReqEmbedding = isset($extra['value_embedding']) ? $extra['value_embedding'] : false;
        $ignoreParameters = isset($extra['ignore_parameters_dbal']) ? $extra['ignore_parameters_dbal'] : array();

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
            ->expects($negative || $valuesEmbedding ? $this->never() : $this->atLeastOnce())
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

        foreach ($extra['dbal_mapping'] as $field => $mapping) {
            $whereBuilder->setField($field, $mapping[0], $mapping[1], $mapping[2]);
        }

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[0], $whereCase);
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
        }

        if (!$valuesEmbedding) {
            $this->asserParamsEquals($queryParams, $whereBuilder, $ignoreParameters);
        } else {
            $this->assertCount(0, $whereBuilder->getParameters());
            $this->assertCount(0, $whereBuilder->getParameterTypes());
        }

        // dummy to stop detection of none used-param
        if (null !== $expectSql) {
            $this->assertNotNull($expectSql);
        }
    }

    /**
     * @dataProvider provideFieldConversionStrategyTests
     *
     * @param SearchCondition $condition
     * @param array|string    $expectWhereCase [native, dql] or string if only result is good enough
     * @param array           $queryParams     [[type, value]]
     * @param string          $expectSql
     * @param boolean         $valuesEmbedding
     * @param array           $extra           Extra options like dbal_mapping, query, negative
     */
    public function testFieldConversionStrategy($condition, $expectWhereCase, array $queryParams = array(), $expectSql = null, $valuesEmbedding = false, array $extra = array())
    {
        $connection = $this->getConnectionMock();

        if (!isset($extra['dbal_mapping'])) {
            $extra['dbal_mapping'] = array(
                // this is actually not recommended, but field conversion is tested later
                'user_birthday' => array('birthday', 'integer', 'u')
            );
        }

        $whereBuilder = new WhereBuilder($connection, $condition);

        $test = $this;

        $expectedOptions = isset($extra['options']) ? $extra['options'] : array();
        $negative = isset($extra['negative']) ? $extra['negative'] : false;
        $valueReqEmbedding = isset($extra['value_embedding']) ? $extra['value_embedding'] : false;
        $ignoreParameters = isset($extra['ignore_parameters_dbal']) ? $extra['ignore_parameters_dbal'] : array();

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

        foreach ($extra['dbal_mapping'] as $field => $mapping) {
            $whereBuilder->setField($field, $mapping[0], $mapping[1], $mapping[2]);
        }

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        if (is_array($expectWhereCase)) {
            $this->assertEquals($expectWhereCase[0], $whereCase);
        } else {
            $this->assertEquals($expectWhereCase, $whereCase);
        }

        if (!$valuesEmbedding) {
            $this->asserParamsEquals($queryParams, $whereBuilder, $ignoreParameters);
        } else {
            $this->assertCount(0, $whereBuilder->getParameters());
            $this->assertCount(0, $whereBuilder->getParameterTypes());
        }

        // dummy to stop detection of none used-param
        if (null !== $expectSql) {
            $this->assertNotNull($expectSql);
        }
    }
}
