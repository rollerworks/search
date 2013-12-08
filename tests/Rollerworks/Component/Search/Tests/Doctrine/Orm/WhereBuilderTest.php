<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder;
use Rollerworks\Component\Search\Tests\Fixtures\CustomerId;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesGroup;

class WhereBuilderTest extends OrmTestCase
{
    /**
     * @dataProvider provideDqlQueryTests
     *
     * @param ValuesGroup $condition
     * @param string      $expectedDql
     * @param array       $queryParams
     * @param string      $expectSql
     */
    public function testDqlQuery($condition, $expectedDql, $queryParams, $expectSql = '')
    {
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');

        $searchCondition = new SearchCondition($this->getFieldSet('invoice'), $condition);

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (!empty($expectSql))));
    }

    public function testEmptyResult()
    {
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ");

        $searchCondition = new SearchCondition($this->getFieldSet('invoice'), new ValuesGroup());

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
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C");

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
     * @param ValuesGroup $condition
     * @param string      $expectedDql
     * @param array       $queryParams
     * @param array       $optionsForCustomer
     */
    public function testValueConversion($condition, $expectedDql, $queryParams, $optionsForCustomer = array())
    {
        $query = $this->em->createQuery("SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ");

        $searchCondition = new SearchCondition($this->getFieldSet('customer'), $condition);

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $test = $this;

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

        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertDqlCompiles($query, $whereBuilder);
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param ValuesGroup $condition
     * @param string      $expectedDql
     * @param array       $queryParams
     * @param array       $optionsForCustomer
     */
    public function testFieldConversion($condition, $expectedDql, $queryParams, $optionsForCustomer = array(), $expectSql = '')
    {
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ");

        $searchCondition = new SearchCondition($this->getFieldSet('invoice'), $condition);

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice' => 'I',
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $test = $this;

        $converter = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface');
        $converter
            ->expects($condition->hasField('invoice_customer') ? $this->atLeastOnce() : $this->never())
            ->method('convertSqlField')
            ->will($this->returnCallback(function ($column, array $options) use ($test, $optionsForCustomer) {
                $test->assertEquals($optionsForCustomer, $options);

                return "CAST($column AS customer_type)";
            }))
        ;

        $whereBuilder->setConverter('invoice_customer', $converter);

        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (!empty($expectSql))));
    }

    /**
     * @dataProvider provideSqlValueConversionTests
     *
     * @param ValuesGroup $condition
     * @param string      $expectedDql
     * @param array       $queryParams
     * @param string      $expectSql
     * @param array       $optionsForCustomer
     * @param boolean     $valueRequiresEmbedding
     */
    public function testSqlValueConversion($condition, $expectedDql, array $queryParams, $expectSql, $optionsForCustomer = array(), $valueRequiresEmbedding = false, $negative = false)
    {
        $query = $this->em->createQuery('SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ');

        $searchCondition = new SearchCondition($this->getFieldSet('customer', $optionsForCustomer), $condition);

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer' => 'C')
        );

        $test = $this;

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
            ->expects(($negative ?  $this->never() : $this->atLeastOnce()))
            ->method('valueRequiresEmbedding')
            ->will($this->returnValue($valueRequiresEmbedding))
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

        $whereBuilder->setConverter('customer_id', $converter);

        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (!empty($expectSql))));
    }

    /**
     * @dataProvider provideConversionStrategyTests
     *
     * @param ValuesGroup $condition
     * @param string      $expectedDql
     * @param array       $queryParams
     * @param string      $expectSql
     */
    public function testConversionStrategy($condition, $expectedDql, array $queryParams, $expectSql)
    {
        $query = $this->em->createQuery('SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ');

        $searchCondition = new SearchCondition($this->getFieldSet('user'), $condition);

        $whereBuilder = new WhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMappings(array(
            'Rollerworks\Component\Search\Tests\Fixtures\Entity\User' => 'u')
        );

        $test = $this;

        $converter = $this->getMock('Rollerworks\Component\Search\Tests\Doctrine\Orm\SqlValueConversionStrategyInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('getConversionStrategy')
            ->will($this->returnCallback(function ($value){
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
            ->expects($this->atLeastOnce())
            ->method('convertSqlValue')
            ->will($this->returnCallback(function ($input, array $options, array $hints) use ($test) {
               $test->assertArrayHasKey('conversion_strategy', $hints);

               if (2 === $hints['conversion_strategy']) {
                   return "CAST($input AS DATE)";
               }

               $test->assertEquals(1, $hints['conversion_strategy']);

               return $input;
            }))
        ;

        // return "to_char('YYYY', age($fieldName))";

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->will($this->returnCallback(function ($input, array $options, array $hints) use ($test) {
               $test->assertArrayHasKey('conversion_strategy', $hints);

               if ($input instanceof \DateTime) {
                   $test->assertEquals(2, $hints['conversion_strategy']);
               } else {
                   $test->assertEquals(1, $hints['conversion_strategy']);
               }

               return $input;
            }))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('valueRequiresEmbedding')
            ->will($this->returnValue(false))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('requiresBaseConversion')
            ->will($this->returnValue(false))
        ;

        $whereBuilder->setConverter('user_birthday', $converter);

        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlCompiles($query, $whereBuilder, (!empty($expectSql))));
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

    public static function provideDqlQueryTests()
    {
        return array(
            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addSingleValue(new SingleValue(2))
                        ->addSingleValue(new SingleValue(5))
                    ->end()
                ->getGroup(),
                '(((C.id IN(:invoice_customer_0, :invoice_customer_1))))',
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 5
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')->addComparison(new Compare(2, '>'))->end()
                ->getGroup(),
                '(((C.id > :invoice_customer_0)))',
                array('invoice_customer_0' => 2)
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')->addExcludedValue(new SingleValue(2))->end()
                ->getGroup(),
                '(((C.id NOT IN(:invoice_customer_0))))',
                array('invoice_customer_0' => 2)
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')->addRange(new Range(2, 5))->end()
                ->getGroup(),
                '((((C.id >= :invoice_customer_0 AND C.id <= :invoice_customer_1))))',
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 5,
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5))
                        ->addRange(new Range(10, 20))
                    ->end()
                ->getGroup(),
                '((((C.id >= :invoice_customer_0 AND C.id <= :invoice_customer_1) OR (C.id >= :invoice_customer_2 AND C.id <= :invoice_customer_3))))',
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 5,
                    'invoice_customer_2' => 10,
                    'invoice_customer_3' => 20,
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addExcludedRange(new Range(2, 5))
                        ->addExcludedRange(new Range(10, 20))
                    ->end()
                ->getGroup(),
                '((((C.id <= :invoice_customer_0 OR C.id >= :invoice_customer_1) AND (C.id <= :invoice_customer_2 OR C.id >= :invoice_customer_3))))',
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 5,
                    'invoice_customer_2' => 10,
                    'invoice_customer_3' => 20,
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5))
                        ->addExcludedRange(new Range(10, 20))
                    ->end()
                ->getGroup(),
                '((((C.id >= :invoice_customer_0 AND C.id <= :invoice_customer_1)) AND ((C.id <= :invoice_customer_2 OR C.id >= :invoice_customer_3))))',
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 5,
                    'invoice_customer_2' => 10,
                    'invoice_customer_3' => 20,
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5, false))
                        ->addExcludedRange(new Range(10, 20, false))
                    ->end()
                ->getGroup(),
                '((((C.id > :invoice_customer_0 AND C.id <= :invoice_customer_1)) AND ((C.id < :invoice_customer_2 OR C.id >= :invoice_customer_3))))',
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 5,
                    'invoice_customer_2' => 10,
                    'invoice_customer_3' => 20,
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5, true, false))
                        ->addExcludedRange(new Range(10, 20, true, false))
                    ->end()
                ->getGroup(),
                '((((C.id >= :invoice_customer_0 AND C.id < :invoice_customer_1)) AND ((C.id <= :invoice_customer_2 OR C.id > :invoice_customer_3))))',
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 5,
                    'invoice_customer_2' => 10,
                    'invoice_customer_3' => 20,
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                        ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                        ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
                    ->end()
                ->getGroup(),
                "(((RW_SEARCH_MATCH(C.id, :invoice_customer_0, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(C.id, :invoice_customer_1, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(C.id, :invoice_customer_2, 'regex', false) = 1 OR RW_SEARCH_MATCH(C.id, :invoice_customer_3, 'regex', true) = 1) AND (RW_SEARCH_MATCH(C.id, :invoice_customer_4, 'ends_with', true) <> 1)))",
                array(
                    'invoice_customer_0' => 'foo',
                    'invoice_customer_1' => 'fo\\\'o',
                    'invoice_customer_2' => '(foo|bar)',
                    'invoice_customer_3' => '(doctor|who)',
                    'invoice_customer_4' => 'bar',
                ),
                "SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((CASE WHEN c1_.id LIKE 'foo' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN c1_.id LIKE 'fo\\'o' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(foo|bar)', c1_.id, '') = 0 THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', c1_.id, 'ui') = 0 THEN 1 ELSE 0 END) = 1) AND ((CASE WHEN LOWER(c1_.id) LIKE LOWER('bar') ESCAPE '\\\\' THEN 1 ELSE 0 END) <> 1)))"
            ),

            array(
                SearchConditionBuilder::create()
                    ->group()
                        ->field('invoice_customer')->addSingleValue(new SingleValue(2))->end()
                    ->end()
                    ->group()
                        ->field('invoice_customer')->addSingleValue(new SingleValue(3))->end()
                    ->end()
                ->getGroup(),
                '((((C.id IN(:invoice_customer_0)))) OR (((C.id IN(:invoice_customer_1)))))',
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 3,
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')->addSingleValue(new SingleValue(5))->end()
                    ->group()
                        ->field('invoice_customer')->addSingleValue(new SingleValue(2))->end()
                    ->end()
                    ->group()
                        ->field('invoice_customer')->addSingleValue(new SingleValue(3))->end()
                    ->end()
                ->getGroup(),
                '((((C.id IN(:invoice_customer_0)))) AND ((((C.id IN(:invoice_customer_1)))) OR (((C.id IN(:invoice_customer_2))))))',
                array(
                    'invoice_customer_0' => 5,
                    'invoice_customer_1' => 2,
                    'invoice_customer_2' => 3,
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->group()
                        ->group()
                            ->field('invoice_customer')->addSingleValue(new SingleValue(2))->end()
                        ->end()
                        ->group()
                            ->field('invoice_customer')->addSingleValue(new SingleValue(3))->end()
                        ->end()
                    ->end()
                ->getGroup(),
                '(((((C.id IN(:invoice_customer_0)))) OR (((C.id IN(:invoice_customer_1))))))',
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 3,
                )
            ),
        );
    }

    public static function provideValueConversionTests()
    {
        return array(
            array(
                SearchConditionBuilder::create()
                    ->field('customer_id')->addSingleValue(new SingleValue(new CustomerId(2)))->end()
                ->getGroup(),
                '(((C.id IN(:customer_id_0))))',
                array(
                    'customer_id_0' => 2,
                )
            ),
        );
    }

    public static function provideFieldConversionTests()
    {
        $tests = array(
            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getGroup(),
                "(((RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null) IN(:invoice_customer_0))))",
                array('invoice_customer_0' => 2)
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_label')
                        ->addSingleValue(new SingleValue('F2012-4242'))
                    ->end()
                ->getGroup(),
                "(((I.label IN(:invoice_label_0))))",
                array('invoice_label_0' => 'F2012-4242')
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5))
                    ->end()
                ->getGroup(),
                "((((RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null) >= :invoice_customer_0 AND RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null) <= :invoice_customer_1))))",
                array(
                    'invoice_customer_0' => 2,
                    'invoice_customer_1' => 5
                )
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('invoice_customer')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                        ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                        ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
                    ->end()
                ->getGroup(),
                "(((RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_0, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_1, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_2, 'regex', false) = 1 OR RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_3, 'regex', true) = 1) AND (RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_4, 'ends_with', true) <> 1)))",
                array(
                    'invoice_customer_0' => 'foo',
                    'invoice_customer_1' => 'fo\\\'o',
                    'invoice_customer_2' => '(foo|bar)',
                    'invoice_customer_3' => '(doctor|who)',
                    'invoice_customer_4' => 'bar',
                ),
                array(),
                "SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((CASE WHEN CAST(c1_.id AS customer_type) LIKE 'foo' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN CAST(c1_.id AS customer_type) LIKE 'fo\\'o' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(foo|bar)', CAST(c1_.id AS customer_type), '') = 0 THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', CAST(c1_.id AS customer_type), 'ui') = 0 THEN 1 ELSE 0 END) = 1) AND ((CASE WHEN LOWER(CAST(c1_.id AS customer_type)) LIKE LOWER('bar') ESCAPE '\\\\' THEN 1 ELSE 0 END) <> 1)))",
            ),
        );

        return $tests;
    }

    public static function provideSqlValueConversionTests()
    {
        return array(
            array(
                SearchConditionBuilder::create()
                    ->field('customer_id')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getGroup(),
                "(((C.id = RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false))))",
                array('customer_id_0' => 2),
                'SELECT c0_.id AS id0 FROM customers c0_ WHERE (((c0_.id = get_customer_type(?))))',
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('customer_id')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getGroup(),
                "(((C.id = RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, true))))",
                array('customer_id_0' => 2),
                'SELECT c0_.id AS id0 FROM customers c0_ WHERE (((c0_.id = get_customer_type(2))))',
                array(),
                true
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('customer_id')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getGroup(),
                "(((C.id = RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false))))",
                array('customer_id_0' => 2),
                "SELECT c0_.id AS id0 FROM customers c0_ WHERE (((c0_.id = get_customer_type(?, '{\"foo\":\"bar\"}'))))",
                array('foo' => 'bar'),
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('customer_id')
                        ->addExcludedValue(new SingleValue(2))
                    ->end()
                ->getGroup(),
                "(((C.id <> RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false))))",
                array('customer_id_0' => 2),
                'SELECT c0_.id AS id0 FROM customers c0_ WHERE (((c0_.id <> get_customer_type(?))))',
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('customer_id')
                        ->addRange(new Range(2, 5))
                    ->end()
                ->getGroup(),
                "((((C.id >= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false) AND C.id <= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_1, null, false)))))",
                array('customer_id_0' => 2, 'customer_id_1' => 5),
                'SELECT c0_.id AS id0 FROM customers c0_ WHERE ((((c0_.id >= get_customer_type(?) AND c0_.id <= get_customer_type(?)))))',
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('customer_id')
                        ->addExcludedRange(new Range(2, 5))
                    ->end()
                ->getGroup(),
                "((((C.id <= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false) OR C.id >= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_1, null, false)))))",
                array('customer_id_0' => 2, 'customer_id_1' => 5),
                'SELECT c0_.id AS id0 FROM customers c0_ WHERE ((((c0_.id <= get_customer_type(?) OR c0_.id >= get_customer_type(?)))))',
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('customer_id')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                        ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                        ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
                    ->end()
                ->getGroup(),
                // This should not contain any SQL-value conversions
                "(((RW_SEARCH_MATCH(C.id, :customer_id_0, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(C.id, :customer_id_1, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(C.id, :customer_id_2, 'regex', false) = 1 OR RW_SEARCH_MATCH(C.id, :customer_id_3, 'regex', true) = 1) AND (RW_SEARCH_MATCH(C.id, :customer_id_4, 'ends_with', true) <> 1)))",
                array(
                    'customer_id_0' => 'foo',
                    'customer_id_1' => 'fo\\\'o',
                    'customer_id_2' => '(foo|bar)',
                    'customer_id_3' => '(doctor|who)',
                    'customer_id_4' => 'bar',
                ),
                "SELECT c0_.id AS id0 FROM customers c0_ WHERE ((((CASE WHEN c0_.id LIKE 'foo' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN c0_.id LIKE 'fo\'o' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(foo|bar)', c0_.id, '') = 0 THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', c0_.id, 'ui') = 0 THEN 1 ELSE 0 END) = 1) AND ((CASE WHEN LOWER(c0_.id) LIKE LOWER('bar') ESCAPE '\\\\' THEN 1 ELSE 0 END) <> 1)))",
                array(),
                false,
                true
            ),
        );
    }

    public static function provideConversionStrategyTests()
    {
        return array(
            array(
                SearchConditionBuilder::create()
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getGroup(),
                "(((u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_0, 1, false))))",
                array('user_birthday_0' => 2),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((c0_.birthday = ?)))"
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(new \DateTime('1990-05-30')))
                    ->end()
                ->getGroup(),
                "(((u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_0, 2, false))))",
                array('user_birthday_0' => new \DateTime('1990-05-30')),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((c0_.birthday = CAST(? AS DATE))))"
            ),

            array(
                SearchConditionBuilder::create()
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(2))
                        ->addSingleValue(new SingleValue(new \DateTime('1990-05-30')))
                    ->end()
                ->getGroup(),
                "(((u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_0, 1, false) OR u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_1, 2, false))))",
                array(
                    'user_birthday_0' => 2,
                    'user_birthday_1' => new \DateTime('1990-05-30'),
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((c0_.birthday = ? OR c0_.birthday = CAST(? AS DATE))))"
            ),
        );
    }

    protected function getFieldSet($name = 'invoice', $optionsForCustomer = array())
    {
        if ('invoice' == $name) {
            $fieldSet = new FieldSet('invoice');

            $fieldLabel = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldLabel->expects($this->any())->method('acceptRanges')->will($this->returnValue(true));
            $fieldLabel->expects($this->any())->method('acceptCompares')->will($this->returnValue(true));
            $fieldLabel->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice'));
            $fieldLabel->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('label'));
            $fieldLabel->expects($this->any())->method('getOptions')->will($this->returnValue($optionsForCustomer));
            $fieldSet->set('invoice_label', $fieldLabel);

            $fieldCustomer = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldCustomer->expects($this->any())->method('acceptRanges')->will($this->returnValue(true));
            $fieldCustomer->expects($this->any())->method('acceptCompares')->will($this->returnValue(true));
            $fieldCustomer->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice'));
            $fieldCustomer->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('customer'));
            $fieldCustomer->expects($this->any())->method('getOptions')->will($this->returnValue(array()));
            $fieldSet->set('invoice_customer', $fieldCustomer);

            $fieldStatus = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldStatus->expects($this->any())->method('acceptRanges')->will($this->returnValue(false));
            $fieldStatus->expects($this->any())->method('acceptCompares')->will($this->returnValue(false));
            $fieldStatus->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice'));
            $fieldStatus->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('status'));
            $fieldStatus->expects($this->any())->method('getOptions')->will($this->returnValue(array()));
            $fieldSet->set('invoice_status', $fieldStatus);

            return $fieldSet;
        }

        if ('customer' == $name) {
            $fieldSet = new FieldSet('customer');

            $fieldCustomer = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldCustomer->expects($this->any())->method('acceptRanges')->will($this->returnValue(true));
            $fieldCustomer->expects($this->any())->method('acceptCompares')->will($this->returnValue(true));
            $fieldCustomer->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer'));
            $fieldCustomer->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('id'));
            $fieldCustomer->expects($this->any())->method('getOptions')->will($this->returnValue($optionsForCustomer));
            $fieldSet->set('customer_id', $fieldCustomer);

            return $fieldSet;
        }

        if ('user' == $name) {
            $fieldSet = new FieldSet('user');

            $fieldCustomer = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldCustomer->expects($this->any())->method('acceptRanges')->will($this->returnValue(true));
            $fieldCustomer->expects($this->any())->method('acceptCompares')->will($this->returnValue(true));
            $fieldCustomer->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\User'));
            $fieldCustomer->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('id'));
            $fieldCustomer->expects($this->any())->method('getOptions')->will($this->returnValue(array()));
            $fieldSet->set('user_id', $fieldCustomer);

            $fieldBirthday = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldBirthday->expects($this->any())->method('acceptRanges')->will($this->returnValue(true));
            $fieldBirthday->expects($this->any())->method('acceptCompares')->will($this->returnValue(true));
            $fieldBirthday->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\User'));
            $fieldBirthday->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('birthday'));
            $fieldBirthday->expects($this->any())->method('getOptions')->will($this->returnValue(array()));
            $fieldSet->set('user_birthday', $fieldBirthday);

            return $fieldSet;
        }

        if ('invoice_with_customer' == $name) {
            $fieldSet = new FieldSet('invoice_with_customer');

            $fieldLabel = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldLabel->expects($this->any())->method('acceptRanges')->will($this->returnValue(true));
            $fieldLabel->expects($this->any())->method('acceptCompares')->will($this->returnValue(true));
            $fieldLabel->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice'));
            $fieldLabel->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('label'));
            $fieldLabel->expects($this->any())->method('getOptions')->will($this->returnValue(array()));
            $fieldSet->set('invoice_label', $fieldLabel);

            $fieldCustomer = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldCustomer->expects($this->any())->method('acceptRanges')->will($this->returnValue(true));
            $fieldCustomer->expects($this->any())->method('acceptCompares')->will($this->returnValue(true));
            $fieldCustomer->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice'));
            $fieldCustomer->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('customer'));
            $fieldCustomer->expects($this->any())->method('getOptions')->will($this->returnValue($optionsForCustomer));
            $fieldSet->set('invoice_customer', $fieldCustomer);

            $fieldStatus = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldStatus->expects($this->any())->method('acceptRanges')->will($this->returnValue(false));
            $fieldStatus->expects($this->any())->method('acceptCompares')->will($this->returnValue(false));
            $fieldStatus->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice'));
            $fieldStatus->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('status'));
            $fieldStatus->expects($this->any())->method('getOptions')->will($this->returnValue(array()));
            $fieldSet->set('invoice_status', $fieldStatus);

            $fieldCustomerId = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
            $fieldCustomerId->expects($this->any())->method('acceptRanges')->will($this->returnValue(true));
            $fieldCustomerId->expects($this->any())->method('acceptCompares')->will($this->returnValue(true));
            $fieldCustomerId->expects($this->any())->method('getModelRefClass')->will($this->returnValue('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer'));
            $fieldCustomerId->expects($this->any())->method('getModelRefProperty')->will($this->returnValue('id'));
            $fieldCustomerId->expects($this->any())->method('getOptions')->will($this->returnValue(array()));
            $fieldSet->set('customer_id', $fieldCustomerId);
        }
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
            $dql = $query->getDQL() . $whereClause;
            $query->setDQL($dql);
        }

        try {
            if ($return) {
                return $query->getSQL();
            }

            $query->getSQL();
        } catch (QueryException $e) {
            $this->fail('compile error:' . $e->getMessage() . ' with Query: ' . $query->getDQL());
        }
    }
}
