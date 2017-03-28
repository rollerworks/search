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

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Prophecy\Argument;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;
use Rollerworks\Component\Search\Doctrine\Orm\DqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\SqlConversionInfo;
use Rollerworks\Component\Search\Extension\Core\Type\ChoiceType;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesGroup;

final class DqlConditionGeneratorTest extends OrmTestCase
{
    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = parent::getFieldSet(false);
        $fieldSet->add('status', ChoiceType::class, ['choices' => ['concept' => 0, 'published' => 1, 'paid' => 2]]);
        $fieldSet->add('customer_first_name', TextType::class);

        return $build ? $fieldSet->getFieldSet('invoice') : $fieldSet;
    }

    private function getConditionGenerator(SearchCondition $condition, $query = null, $noMapping = false)
    {
        if (null === $query) {
            $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C');
        }

        $conditionGenerator = $this->getOrmFactory()->createConditionGenerator($query, $condition);

        if (!$noMapping) {
            $conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
            $conditionGenerator->setField('id', 'id', null, null, 'smallint');
            $conditionGenerator->setField('status', 'status');

            $conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
            $conditionGenerator->setField('customer', 'id');
            $conditionGenerator->setField('customer_name#first_name', 'firstName');
            $conditionGenerator->setField('customer_name#last_name', 'lastName');
            $conditionGenerator->setField('customer_first_name', 'firstName');
            $conditionGenerator->setField('customer_birthday', 'birthday');
        }

        return $conditionGenerator;
    }

    public function testSimpleQuery()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals('((C.id IN(2, 5)))', $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles(
            $conditionGenerator,
            'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id IN (2, 5)))'
        );
    }

    public function testQueryWithMultipleFields()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->field('status')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals('((C.id IN(2, 5)) AND (I.status IN(2, 5)))', $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles(
            $conditionGenerator,
            'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id IN (2, 5)) AND (i0_.status IN (2, 5)))'
        );
    }

    public function testEmptyResult()
    {
        $condition = new SearchCondition($this->getFieldSet(), new ValuesGroup());
        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals('', $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testExcludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addExcludedSimpleValue(2)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals('((C.id NOT IN(2, 5)))', $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testIncludesAndExcludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals('((C.id IN(2) AND C.id NOT IN(5)))', $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testRanges()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Range(2, 5))
                ->add(new Range(10, 20))
                ->add(new Range(60, 70, false))
                ->add(new Range(100, 150, true, false))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            '((((C.id >= 2 AND C.id <= 5) OR (C.id >= 10 AND C.id <= 20) OR '.
            '(C.id > 60 AND C.id <= 70) OR (C.id >= 100 AND C.id < 150))))',
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testExcludedRanges()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new ExcludedRange(2, 5))
                ->add(new ExcludedRange(10, 20))
                ->add(new ExcludedRange(60, 70, false))
                ->add(new ExcludedRange(100, 150, true, false))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            '((((C.id <= 2 OR C.id >= 5) AND (C.id <= 10 OR C.id >= 20) AND '.
            '(C.id < 60 OR C.id >= 70) AND (C.id <= 100 OR C.id > 150))))',
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testSingleComparison()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '>'))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals('((C.id > 2))', $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testMultipleComparisons()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '>'))
                ->add(new Compare(10, '<'))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            '(((C.id > 2 AND C.id < 10)))',
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testMultipleComparisonsWithGroups()
    {
        // Use two subgroups here as the comparisons are AND to each other
        // but applying them in the head group would ignore subgroups
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->field('customer')
                    ->add(new Compare(2, '>'))
                    ->add(new Compare(10, '<'))
                    ->addSimpleValue(20)
                ->end()
            ->end()
            ->group()
                ->field('customer')
                    ->add(new Compare(30, '>'))
                ->end()
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            '((((C.id IN(20) OR (C.id > 2 AND C.id < 10)))) OR ((C.id > 30)))',
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testExcludingComparisons()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '<>'))
                ->add(new Compare(5, '<>'))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            '((C.id <> 2 AND C.id <> 5))',
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testExcludingComparisonsWithNormal()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(35, '<>'))
                ->add(new Compare(45, '<>'))
                ->add(new Compare(30, '>'))
                ->add(new Compare(50, '<'))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            '(((C.id > 30 AND C.id < 50) AND C.id <> 35 AND C.id <> 45))',
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testPatternMatchers()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer_first_name')
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('fo\'o', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('fo\'\'o', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                ->add(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                ->add(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            "(((C.firstName LIKE '%foo' ESCAPE '\\' OR C.firstName LIKE '%fo\\''o' ESCAPE '\\' OR C.firstName LIKE '%fo''o' ESCAPE '\\' OR C.firstName LIKE '%fo''''o' ESCAPE '\\' OR RW_SEARCH_MATCH(C.firstName, '(foo|bar)', false) = 1 OR RW_SEARCH_MATCH(C.firstName, '(doctor|who)', true) = 1) AND LOWER(C.firstName) NOT LIKE LOWER('bar%') ESCAPE '\\'))",
            $conditionGenerator->getWhereClause()
        );

        if ('sqlite' === $this->conn->getDatabasePlatform()->getName()) {
            $this->assertDqlCompiles(
                $conditionGenerator,
                "SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.first_name LIKE '%foo' ESCAPE '\\' OR c1_.first_name LIKE '%fo\\''o' ESCAPE '\\' OR c1_.first_name LIKE '%fo''o' ESCAPE '\\' OR c1_.first_name LIKE '%fo''''o' ESCAPE '\\' OR (CASE WHEN RW_REGEXP('(foo|bar)', c1_.first_name, 'u') THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', c1_.first_name, 'ui') THEN 1 ELSE 0 END) = 1) AND LOWER(c1_.first_name) NOT LIKE LOWER('bar%') ESCAPE '\\'))"
            );
        } else {
            $rexP = 'postgresql' === $this->conn->getDatabasePlatform()->getName() ? '~' : 'REGEXP';
            $rexPInsensitive = 'postgresql' === $this->conn->getDatabasePlatform()->getName() ? '~*' : 'REGEXP BINARY';

            $this->assertDqlCompiles(
                $conditionGenerator,
                'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.first_name LIKE '.$this->conn->quote('%foo').' ESCAPE '.$this->conn->quote('\\').' OR c1_.first_name LIKE '.$this->conn->quote("%fo\\'o").' ESCAPE '.$this->conn->quote('\\').' OR c1_.first_name LIKE '.$this->conn->quote("%fo'o").' ESCAPE '.$this->conn->quote('\\').' OR c1_.first_name LIKE '.$this->conn->quote("%fo''o").' ESCAPE '.$this->conn->quote('\\').' OR (CASE WHEN c1_.first_name '.$rexP.' '.$this->conn->quote('(foo|bar)').' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN c1_.first_name '.$rexPInsensitive.' '.$this->conn->quote('(doctor|who)').' THEN 1 ELSE 0 END) = 1) AND LOWER(c1_.first_name) NOT LIKE LOWER('.$this->conn->quote('bar%').') ESCAPE '.$this->conn->quote('\\').'))'
            );
        }
    }

    public function testSubGroups()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->field('customer')
                    ->addSimpleValue(2)
                ->end()
            ->end()
            ->group()
                ->field('customer')
                    ->addSimpleValue(3)
                ->end()
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            '(((C.id IN(2))) OR ((C.id IN(3))))',
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testSubGroupWithRootCondition()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
            ->group()
                ->field('customer_name')
                    ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->end()
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            "(((C.id IN(2))) AND ((((C.firstName LIKE '%foo' ESCAPE '\\' OR C.lastName LIKE '%foo' ESCAPE '\\')))))",
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testOrGroupRoot()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet(), ValuesGroup::GROUP_LOGICAL_OR)
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
            ->field('customer_first_name')
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            "((C.id IN(2)) OR (C.firstName LIKE '%foo' ESCAPE '\\'))",
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testSubOrGroup()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->group(ValuesGroup::GROUP_LOGICAL_OR)
                    ->field('customer')
                        ->addSimpleValue(2)
                    ->end()
                    ->field('customer_first_name')
                        ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                    ->end()
                ->end()
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            "((((C.id IN(2)) OR (C.firstName LIKE '%foo' ESCAPE '\\'))))",
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testColumnConversion()
    {
        $converter = $this->createMock(ColumnConversion::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertColumn')
            ->willReturnCallback(function ($column, array $options, ConversionHints $hints) {
                self::assertArraySubset(['grouping' => true], $options);
                self::assertEquals('C', $hints->field->alias); // FIXME This is wrong, but the mapping system doesn't know of final aliases until processing
                self::assertEquals('c1_.id', $hints->column);

                return "CAST($column AS customer_type)";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_dbal_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);
        self::assertEquals("((RW_SEARCH_FIELD_CONVERSION('customer', C.id, 0) IN(2)))", $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles($conditionGenerator, 'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((CAST(c1_.id AS customer_type) IN (2)))');
    }

    public function testValueConversion()
    {
        $converter = $this->createMock(ValueConversion::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->willReturnCallback(function ($value, array $options) {
                self::assertArraySubset(['grouping' => true], $options);

                return "get_customer_type($value)";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_dbal_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals("((C.id = RW_SEARCH_VALUE_CONVERSION('customer', C.id, 1, 0)))", $conditionGenerator->getWhereClause(
        ));
        $this->assertDqlCompiles(
            $conditionGenerator,
            'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id = get_customer_type(2)))'
        );
    }

    public function testConversionStrategyValue()
    {
        $converter = $this->createMock(ValueConversionStrategy::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('getConversionStrategy')
            ->willReturnCallback(function ($value) {
                if (!$value instanceof \DateTime && !is_int($value)) {
                    throw new \InvalidArgumentException('Only integer/string and DateTime are accepted.');
                }

                if ($value instanceof \DateTime) {
                    return 2;
                }

                return 1;
            })
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->willReturnCallback(function ($value, array $passedOptions, ConversionHints $hints) {
                self::assertArraySubset(['pattern' => 'dd-MM-yy'], $passedOptions);

                if ($value instanceof \DateTime) {
                    self::assertEquals(2, $hints->conversionStrategy);

                    return 'CAST('.$hints->connection->quote($value->format('Y-m-d')).' AS AGE)';
                }

                self::assertEquals(1, $hints->conversionStrategy);

                return $value;
            })
        ;

        $fieldSet = $this->getFieldSet(false);
        $fieldSet->add('customer_birthday', DateType::class, ['doctrine_dbal_conversion' => $converter, 'pattern' => 'dd-MM-yy']);

        $condition = SearchConditionBuilder::create($fieldSet->getFieldSet())
            ->field('customer_birthday')
                ->addSimpleValue(18)
                ->addSimpleValue(new \DateTime('2001-01-15', new \DateTimeZone('UTC')))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);
        self::assertEquals(
            "(((C.birthday = RW_SEARCH_VALUE_CONVERSION('customer_birthday', C.birthday, 1, 1) OR C.birthday = RW_SEARCH_VALUE_CONVERSION('customer_birthday', C.birthday, 2, 2))))",
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles(
            $conditionGenerator,
            "SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.birthday = 18 OR c1_.birthday = CAST('2001-01-15' AS AGE))))"
        );
    }

    public function testConversionStrategyColumn()
    {
        $converter = $this->createMock(ColumnConversionStrategy::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('getConversionStrategy')
            ->willReturnCallback(function ($value) {
                if (!is_string($value) && !is_int($value)) {
                    throw new \InvalidArgumentException('Only integer/string is accepted.');
                }

                if (is_string($value)) {
                    return 2;
                }

                return 1;
            })
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertColumn')
            ->willReturnCallback(function ($column, array $options, ConversionHints $hints) {
                if (2 === (int) $hints->conversionStrategy) {
                    return "search_conversion_age($column)";
                }

                self::assertEquals(1, $hints->conversionStrategy);

                return $column;
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer_birthday', TextType::class, ['doctrine_dbal_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer_birthday')
                ->addSimpleValue(18)
                ->addSimpleValue('2001-01-15')
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);
        $conditionGenerator->setField('customer_birthday', 'birthday', 'C', self::CUSTOMER_CLASS, 'string');

        self::assertEquals(
            "(((RW_SEARCH_FIELD_CONVERSION('customer_birthday', C.birthday, 1) = 18 OR RW_SEARCH_FIELD_CONVERSION('customer_birthday', C.birthday, 2) = '2001-01-15')))",
            $conditionGenerator->getWhereClause()
        );
        $this->assertDqlCompiles(
            $conditionGenerator,
            "SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.birthday = 18 OR search_conversion_age(c1_.birthday) = '2001-01-15')))"
        );
    }

    public function testUpdateQuery()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $whereCase = $conditionGenerator->getWhereClause();
        $conditionGenerator->updateQuery(' WHERE ');

        $this->assertEquals('((C.id IN(2)))', $whereCase);
        $this->assertEquals(
            "SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ((C.id IN(2)))",
            $conditionGenerator->getQuery()->getDQL()
        );
        $this->assertDqlCompiles(
            $conditionGenerator,
            'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id IN (2)))',
            false
        );
    }

    public function testUpdateQueryWithQueryBuilder()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        if (method_exists(QueryBuilder::class, 'setHint')) {
            $qb = $this->prophesize(QueryBuilder::class);
        } else {
            $qb = $this->prophesize(QueryBuilderWithHints::class);
        }

        $qb->getEntityManager()->willReturn($this->em);
        $qb->setHint('rws_conversion_hint', Argument::type(SqlConversionInfo::class))->shouldBeCalled();
        $qb->andWhere('((C.id IN(2)))')->shouldBeCalled();

        $conditionGenerator = $this->getConditionGenerator($condition, $qb->reveal());

        $whereCase = $conditionGenerator->getWhereClause();
        $conditionGenerator->updateQuery(' WHERE ');

        $this->assertEquals('((C.id IN(2)))', $whereCase);
    }

    public function testUpdateQueryWithNoResult()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();
        $conditionGenerator = $this->getConditionGenerator($condition);

        $whereCase = $conditionGenerator->getWhereClause();
        $conditionGenerator->updateQuery(' WHERE ');

        $this->assertEquals('', $whereCase);
        $this->assertEquals(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C',
            $conditionGenerator->getQuery()->getDQL()
        );
        $this->assertDqlCompiles($conditionGenerator);
    }

    public function testDoctrineAlias()
    {
        $config = $this->em->getConfiguration();
        $config->addEntityNamespace('ECommerce', 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity');

        $query = $this->em->createQuery('SELECT I FROM ECommerce:ECommerceInvoice I JOIN I.customer C');

        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition, $query, true);

        $conditionGenerator->setDefaultEntity('ECommerce:ECommerceInvoice', 'I');
        $conditionGenerator->setField('id', 'id', null, null, 'smallint');
        $conditionGenerator->setField('status', 'status');

        $conditionGenerator->setDefaultEntity('ECommerce:ECommerceCustomer', 'C');
        $conditionGenerator->setField('customer', 'id');
        $conditionGenerator->setField('customer_name#first_name', 'firstName');
        $conditionGenerator->setField('customer_name#last_name', 'lastName');
        $conditionGenerator->setField('customer_birthday', 'birthday');

        $whereCase = $conditionGenerator->getWhereClause();

        $this->assertEquals('((C.id IN(2)))', $whereCase);
        $this->assertDqlCompiles($conditionGenerator, 'SELECT i0_.invoice_id AS invoice_id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.price_total AS price_total4, i0_.customer AS customer5, i0_.parent_id AS parent_id6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id IN (2)))');
    }

    private function assertDqlCompiles(DqlConditionGenerator $conditionGenerator, string $expectedSql = '', bool $updateQuery = true)
    {
        if ($updateQuery) {
            $conditionGenerator->updateQuery();
        }

        try {
            $sql = $conditionGenerator->getQuery()->getSQL();

            if ('' !== $expectedSql) {
                // In Doctrine ORM 2.5 the column-alias naming has changed,
                // as we need to be compatible with older versions we simple remove
                // the underscore between the name and alias incrementer
                $sql = preg_replace('/ AS ([\w\d]+)_(\d+)/i', ' AS $1$2', $sql);

                $this->assertEquals($expectedSql, $sql);
            }
        } catch (QueryException $e) {
            $this->fail('Compile error: '.$e->getMessage().' with Query: '.$conditionGenerator->getQuery()->getDQL());
        }
    }
}
