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

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\InvoiceNumber;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesGroup;

class WhereBuilderTest extends OrmTestCase
{
    private function getWhereBuilder(SearchCondition $condition, Query $query = null, $noMapping = false)
    {
        if (null === $query) {
            $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C');
        }

        $whereBuilder = $this->getOrmFactory()->createWhereBuilder($query, $condition);

        if (!$noMapping) {
            $whereBuilder->setEntityMapping('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice', 'I');
            $whereBuilder->setEntityMapping('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer', 'C');
        }

        return $whereBuilder;
    }

    public function testSimpleQuery()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('((C.id IN(2, 5)))', $whereBuilder->getWhereClause());
        $this->assertDqlCompiles(
            $whereBuilder,
            'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id IN (2, 5)))'
        );
    }

    public function testQueryWithMultipleFields()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
            ->field('status')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('((C.id IN(2, 5)) AND (I.status IN(2, 5)))', $whereBuilder->getWhereClause());
        $this->assertDqlCompiles(
            $whereBuilder,
            'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id IN (2, 5)) AND (i0_.status IN (2, 5)))'
        );
    }

    public function testEmptyResult()
    {
        $condition = new SearchCondition($this->getFieldSet(), new ValuesGroup());
        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('', $whereBuilder->getWhereClause());
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testExcludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addExcludedValue(new SingleValue(2))
                ->addExcludedValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('((C.id NOT IN(2, 5)))', $whereBuilder->getWhereClause());
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testIncludesAndExcludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
                ->addExcludedValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('((C.id IN(2) AND C.id NOT IN(5)))', $whereBuilder->getWhereClause());
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testRanges()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addRange(new Range(2, 5))
                ->addRange(new Range(10, 20))
                ->addRange(new Range(60, 70, false))
                ->addRange(new Range(100, 150, true, false))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '((((C.id >= 2 AND C.id <= 5) OR (C.id >= 10 AND C.id <= 20) OR '.
            '(C.id > 60 AND C.id <= 70) OR (C.id >= 100 AND C.id < 150))))',
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testExcludedRanges()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addExcludedRange(new Range(2, 5))
                ->addExcludedRange(new Range(10, 20))
                ->addExcludedRange(new Range(60, 70, false))
                ->addExcludedRange(new Range(100, 150, true, false))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '((((C.id <= 2 OR C.id >= 5) AND (C.id <= 10 OR C.id >= 20) AND '.
            '(C.id < 60 OR C.id >= 70) AND (C.id <= 100 OR C.id > 150))))',
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testSingleComparison()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addComparison(new Compare(2, '>'))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('((C.id > 2))', $whereBuilder->getWhereClause());
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testMultipleComparisons()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addComparison(new Compare(2, '>'))
                ->addComparison(new Compare(10, '<'))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '(((C.id > 2 AND C.id < 10)))',
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testMultipleComparisonsWithGroups()
    {
        // Use two subgroups here as the comparisons are AND to each other
        // but applying them in the head group would ignore subgroups
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->field('customer')
                    ->addComparison(new Compare(2, '>'))
                    ->addComparison(new Compare(10, '<'))
                    ->addSingleValue(new SingleValue(20))
                ->end()
            ->end()
            ->group()
                ->field('customer')
                    ->addComparison(new Compare(30, '>'))
                ->end()
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '((((C.id IN(20) OR (C.id > 2 AND C.id < 10)))) OR ((C.id > 30)))',
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testExcludingComparisons()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addComparison(new Compare(2, '<>'))
                ->addComparison(new Compare(5, '<>'))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '((C.id <> 2 AND C.id <> 5))',
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testExcludingComparisonsWithNormal()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addComparison(new Compare(35, '<>'))
                ->addComparison(new Compare(45, '<>'))
                ->addComparison(new Compare(30, '>'))
                ->addComparison(new Compare(50, '<'))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '(((C.id > 30 AND C.id < 50) AND C.id <> 35 AND C.id <> 45))',
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testPatternMatchers()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer_name')
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            "(((C.name LIKE '%foo' ESCAPE '\\' OR C.name LIKE '%fo\\''o' ESCAPE '\\' OR RW_SEARCH_MATCH(C.name, '(foo|bar)', false) = 1 OR RW_SEARCH_MATCH(C.name, '(doctor|who)', true) = 1) AND LOWER(C.name) NOT LIKE LOWER('bar%') ESCAPE '\\'))",
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles(
            $whereBuilder,
            "SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.name LIKE '%foo' ESCAPE '\\' OR c1_.name LIKE '%fo\\''o' ESCAPE '\\' OR (CASE WHEN RW_REGEXP('(foo|bar)', c1_.name, 'u') THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', c1_.name, 'ui') THEN 1 ELSE 0 END) = 1) AND LOWER(c1_.name) NOT LIKE LOWER('bar%') ESCAPE '\\'))"
        );
    }

    public function testSubGroups()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->field('customer')->addSingleValue(new SingleValue(2))->end()
            ->end()
            ->group()
                ->field('customer')->addSingleValue(new SingleValue(3))->end()
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '(((C.id IN(2))) OR ((C.id IN(3))))',
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testSubGroupWithRootCondition()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
            ->group()
                ->field('customer_name')
                    ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->end()
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            "(((C.id IN(2))) AND (((C.name LIKE '%foo' ESCAPE '\\'))))",
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testOrGroupRoot()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet(), ValuesGroup::GROUP_LOGICAL_OR)
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
            ->field('customer_name')
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            "((C.id IN(2)) OR (C.name LIKE '%foo' ESCAPE '\\'))",
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testSubOrGroup()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->group(ValuesGroup::GROUP_LOGICAL_OR)
                    ->field('customer')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                    ->field('customer_name')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                    ->end()
                ->end()
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            "((((C.id IN(2)) OR (C.name LIKE '%foo' ESCAPE '\\'))))",
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testValueConversion()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('label')
                ->addSingleValue(new SingleValue(InvoiceNumber::createFromString('2015-001')))
                ->addSingleValue(new SingleValue(InvoiceNumber::createFromString('2015-005')))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals("((I.label IN('2015-0001', '2015-0005')))", $whereBuilder->getWhereClause());
        $this->assertDqlCompiles(
            $whereBuilder,
            "SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((i0_.label IN ('2015-0001', '2015-0005')))"
        );
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param string $expectWhereCase
     * @param array  $options
     * @param string $expectedSql
     */
    public function testFieldConversion($expectWhereCase, array $options = [], $expectedSql)
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);
        $test = $this;

        $converter = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlField')
            ->will($this->returnCallback(function ($column, array $options) use ($test, $options) {
                $test->assertEquals($options, $options);

                return "CAST($column AS customer_type)";
            }))
        ;

        $whereBuilder->setConverter('customer', $converter);

        $this->assertEquals($expectWhereCase, $whereBuilder->getWhereClause());
        $this->assertDqlCompiles($whereBuilder, $expectedSql);
    }

    public function testSqlValueConversion()
    {
        $fieldSet = $this->getFieldSet();
        $condition = SearchConditionBuilder::create($fieldSet)
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getSearchCondition();

        $options = $fieldSet->get('customer')->getOptions();
        $whereBuilder = $this->getWhereBuilder($condition);
        $test = $this;

        $converter = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlValue')
            ->will($this->returnCallback(function ($input, array $passedOptions) use ($test, $options) {
                $test->assertEquals($options, $passedOptions);

                return "get_customer_type($input)";
            }))
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

        $whereBuilder->setConverter('customer', $converter);

        $this->assertEquals("((C.id = RW_SEARCH_VALUE_CONVERSION('customer', C.id, 1, 0)))", $whereBuilder->getWhereClause());
        $this->assertDqlCompiles(
            $whereBuilder,
            'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id = get_customer_type(2)))'
        );
    }

    public function testConversionStrategy()
    {
        $date = new \DateTime('2001-01-15', new \DateTimeZone('UTC'));

        $fieldSet = $this->getFieldSet();
        $condition = SearchConditionBuilder::create($fieldSet)
            ->field('customer_birthday')
                ->addSingleValue(new SingleValue(18))
                ->addSingleValue(new SingleValue($date, '2001-01-15'))
            ->end()
        ->getSearchCondition();

        $options = $fieldSet->get('customer_birthday')->getOptions();
        $whereBuilder = $this->getWhereBuilder($condition);
        $test = $this;

        $converter = $this->getMock('Rollerworks\Component\Search\Tests\Doctrine\Dbal\SqlConversionStrategyInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('getConversionStrategy')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        if (!$value instanceof \DateTime && !is_int($value)) {
                            throw new \InvalidArgumentException('Only integer/string and DateTime are accepted.');
                        }

                        if ($value instanceof \DateTime) {
                            return 2;
                        }

                        return 1;
                    }
                )
            )
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlField')
            ->will(
                $this->returnCallback(
                    function ($column, array $passedOptions, ConversionHints $hints) use ($test, $options) {
                        $test->assertEquals($options, $passedOptions);

                        if (2 === $hints->conversionStrategy) {
                            return "search_conversion_age($column)";
                        }

                        $test->assertEquals(1, $hints->conversionStrategy);

                        return $column;
                    }
                )
            )
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlValue')
            ->will(
                $this->returnCallback(
                    function ($input, array $passedOptions, ConversionHints $hints) use ($test, $options) {
                        $test->assertEquals($options, $passedOptions);

                        if (2 === $hints->conversionStrategy) {
                            return 'CAST('.$hints->connection->quote($input).' AS DATE)';
                        }

                        $test->assertEquals(1, $hints->conversionStrategy);

                        return $input;
                    }
                )
            )
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->will(
                $this->returnCallback(
                    function ($input, array $passedOptions, ConversionHints $hints) use ($test, $options) {
                        $test->assertEquals($options, $passedOptions);

                        if ($input instanceof \DateTime) {
                            $test->assertEquals(2, $hints->conversionStrategy);
                        } else {
                            $test->assertEquals(1, $hints->conversionStrategy);
                        }

                        if ($input instanceof \DateTime) {
                            $input = $input->format('Y-m-d');
                        }

                        return $input;
                    }
                )
            )
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('requiresBaseConversion')
            ->will($this->returnValue(false))
        ;

        $whereBuilder->setConverter('customer_birthday', $converter);

        $this->assertEquals(
            "(((RW_SEARCH_FIELD_CONVERSION('customer_birthday', C.birthday, 1) = RW_SEARCH_VALUE_CONVERSION('customer_birthday', RW_SEARCH_FIELD_CONVERSION('customer_birthday', C.birthday, 1), 1, 1) OR RW_SEARCH_FIELD_CONVERSION('customer_birthday', C.birthday, 2) = RW_SEARCH_VALUE_CONVERSION('customer_birthday', RW_SEARCH_FIELD_CONVERSION('customer_birthday', C.birthday, 2), 2, 2))))",
            $whereBuilder->getWhereClause()
        );
        $this->assertDqlCompiles($whereBuilder, "SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.birthday = 18 OR search_conversion_age(c1_.birthday) = CAST('2001-01-15' AS DATE))))");
    }

    public static function provideFieldConversionTests()
    {
        return [
            [
                "((RW_SEARCH_FIELD_CONVERSION('customer', C.id, 0) IN(2)))",
                [],
                'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((CAST(c1_.id AS customer_type) IN (2)))',
            ],
            [
                "((RW_SEARCH_FIELD_CONVERSION('customer', C.id, 0) IN(2)))",
                ['active' => true],
                'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((CAST(c1_.id AS customer_type) IN (2)))',
            ],
        ];
    }

    public function testUpdateQuery()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $whereCase = $whereBuilder->getWhereClause();
        $whereBuilder->updateQuery(' WHERE ');

        $this->assertEquals('((C.id IN(2)))', $whereCase);
        $this->assertEquals(
            "SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ((C.id IN(2)))",
            $whereBuilder->getQuery()->getDQL()
        );
        $this->assertDqlCompiles(
            $whereBuilder,
            'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id IN (2)))',
            false
        );
    }

    public function testUpdateQueryWithNoResult()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();
        $whereBuilder = $this->getWhereBuilder($condition);

        $whereCase = $whereBuilder->getWhereClause();
        $whereBuilder->updateQuery(' WHERE ');

        $this->assertEquals('', $whereCase);
        $this->assertEquals(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C',
            $whereBuilder->getQuery()->getDQL()
        );
        $this->assertDqlCompiles($whereBuilder);
    }

    public function testDoctrineAlias()
    {
        $config = $this->em->getConfiguration();
        $config->addEntityNamespace('ECommerce', 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity');

        $query = $this->em->createQuery('SELECT I FROM ECommerce:ECommerceInvoice I JOIN I.customer C');

        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition, $query, true);
        $whereBuilder->setEntityMappings([
            'ECommerce:ECommerceInvoice' => 'I',
            'ECommerce:ECommerceCustomer' => 'C',
        ]);

        $whereCase = $whereBuilder->getWhereClause();

        $this->assertEquals('((C.id IN(2)))', $whereCase);
        $this->assertDqlCompiles($whereBuilder, 'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4, i0_.parent_id AS parent_id5 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id IN (2)))');
    }

    /**
     * @param WhereBuilder $whereBuilder
     * @param string       $expectedSql
     */
    private function assertDqlCompiles(WhereBuilder $whereBuilder, $expectedSql = '', $updateQuery = true)
    {
        if ($updateQuery) {
            $whereBuilder->updateQuery();
        }

        try {
            $sql = $whereBuilder->getQuery()->getSQL();

            if ('' !== $expectedSql) {
                // In Doctrine ORM 2.5 the column-alias naming has changed,
                // as we need to be compatible with older versions we simple remove
                // the underscore between the name and alias incrementer
                $sql = preg_replace('/ AS ([\w\d]+)_(\d+)/i', ' AS $1$2', $sql);

                $this->assertEquals($expectedSql, $sql);
            }
        } catch (QueryException $e) {
            $this->fail('Compile error: '.$e->getMessage().' with Query: '.$whereBuilder->getQuery()->getDQL());
        }
    }
}
