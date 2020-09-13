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

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Orm\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Orm\DqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\Tests\Fixtures\GetCustomerTypeFunction;
use Rollerworks\Component\Search\Doctrine\Orm\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\Type\ChoiceType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\SearchPrimaryCondition;
use Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice;
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
        if ($query === null) {
            $query = $this->em->createQuery('SELECT I FROM '.ECommerceInvoice::class.' I JOIN I.customer C');
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

        $this->assertEquals('(((C.id = :search_0 OR C.id = :search_1)))', $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles(
            $conditionGenerator,
            <<<'SQL'
SELECT
    i0_.invoice_id AS invoice_id_0,
    i0_.label AS label_1,
    i0_.pubdate AS pubdate_2,
    i0_.status AS status_3,
    i0_.price_total AS price_total_4,
    i0_.customer AS customer_5,
    i0_.parent_id AS parent_id_6
FROM invoices i0_
         INNER JOIN customers c1_ ON i0_.customer = c1_.id
WHERE (((c1_.id = ? OR c1_.id = ?)))
SQL
,
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
            ]
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

        $this->assertEquals('(((C.id = :search_0 OR C.id = :search_1)) AND ((I.status = :search_2 OR I.status = :search_3)))', $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles(
            $conditionGenerator,
            <<<'SQL'
SELECT
    i0_.invoice_id AS invoice_id_0,
    i0_.label AS label_1,
    i0_.pubdate AS pubdate_2,
    i0_.status AS status_3,
    i0_.price_total AS price_total_4,
    i0_.customer AS customer_5,
    i0_.parent_id AS parent_id_6
FROM
    invoices i0_
        INNER JOIN customers c1_ ON i0_.customer = c1_.id
WHERE (((c1_.id = ? OR c1_.id = ?)) AND ((i0_.status = ? OR i0_.status = ?)))
SQL
,
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
                ':search_2' => [2, Type::getType('integer')],
                ':search_3' => [5, Type::getType('integer')],
            ]
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

        $this->assertEquals('(((C.id <> :search_0 AND C.id <> :search_1)))', $conditionGenerator->getWhereClause());
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

        $this->assertEquals('((C.id = :search_0 AND C.id <> :search_1))', $conditionGenerator->getWhereClause());
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
            '((((C.id >= :search_0 AND C.id <= :search_1) OR (C.id >= :search_2 AND C.id <= :search_3) OR (C.id > :search_4 AND C.id <= :search_5) OR (C.id >= :search_6 AND C.id < :search_7))))',
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
            '((((C.id <= :search_0 OR C.id >= :search_1) AND (C.id <= :search_2 OR C.id >= :search_3) AND (C.id < :search_4 OR C.id >= :search_5) AND (C.id <= :search_6 OR C.id > :search_7))))',
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

        $this->assertEquals('((C.id > :search_0))', $conditionGenerator->getWhereClause());
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
            '(((C.id > :search_0 AND C.id < :search_1)))',
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
            '((((C.id = :search_0 OR (C.id > :search_1 AND C.id < :search_2)))) OR ((C.id > :search_3)))',
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
            '((C.id <> :search_0 AND C.id <> :search_1))',
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
            '(((C.id > :search_0 AND C.id < :search_1) AND C.id <> :search_2 AND C.id <> :search_3))',
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
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            "(((C.firstName LIKE CONCAT('%', :search_0) OR C.firstName LIKE CONCAT('%', :search_1) OR C.firstName LIKE CONCAT('%', :search_2) OR C.firstName LIKE CONCAT('%', :search_3)) AND LOWER(C.firstName) NOT LIKE LOWER(CONCAT(:search_4, '%'))))",
            $conditionGenerator->getWhereClause()
        );

        if ($this->conn->getDatabasePlatform()->getName() === 'postgresql') {
            $this->assertDqlCompiles(
                $conditionGenerator,
                <<<'SQL'
SELECT
    i0_.invoice_id AS invoice_id_0,
    i0_.label AS label_1,
    i0_.pubdate AS pubdate_2,
    i0_.status AS status_3,
    i0_.price_total AS price_total_4,
    i0_.customer AS customer_5,
    i0_.parent_id AS parent_id_6
FROM
    invoices i0_
        INNER JOIN customers c1_ ON i0_.customer = c1_.id
WHERE (((c1_.first_name LIKE '%' || ? OR c1_.first_name LIKE '%' || ? OR c1_.first_name LIKE '%' || ? OR
         c1_.first_name LIKE '%' || ?) AND LOWER(c1_.first_name) NOT LIKE LOWER(? || '%')))
SQL
            );
        } else {
            $this->assertDqlCompiles(
                $conditionGenerator
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
            '(((C.id = :search_0)) OR ((C.id = :search_1)))',
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
            "(((C.id = :search_0)) AND ((((C.firstName LIKE CONCAT('%', :search_1) OR C.lastName LIKE CONCAT('%', :search_2))))))",
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
            "((C.id = :search_0) OR (C.firstName LIKE CONCAT('%', :search_1)))",
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
            "((((C.id = :search_0) OR (C.firstName LIKE CONCAT('%', :search_1)))))",
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
                self::assertArrayHasKey('grouping', $options);
                self::assertTrue($options['grouping']);
                self::assertEquals('C.id', $hints->column);

                return "SEARCH_CONVERSION_CAST($column, 'customer_type')";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_orm_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        self::assertEquals("((SEARCH_CONVERSION_CAST(C.id, 'customer_type') = :search_0))", $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles(
            $conditionGenerator,
            <<<'SQL'
SELECT
    i0_.invoice_id AS invoice_id_0, i0_.label AS label_1, i0_.pubdate AS pubdate_2, i0_.status AS status_3,
    i0_.price_total AS price_total_4, i0_.customer AS customer_5, i0_.parent_id AS parent_id_6
FROM
    invoices i0_
        INNER JOIN customers c1_ ON i0_.customer = c1_.id
WHERE ((CAST(c1_.id AS customer_type) = ?))
SQL
        );
    }

    public function testValueConversion()
    {
        $emConfig = $this->em->getConfiguration();
        $emConfig->addCustomStringFunction('GET_CUSTOMER_TYPE', GetCustomerTypeFunction::class);

        $converter = $this->createMock(ValueConversion::class);
        $converter
            ->expects(self::atLeastOnce())
            ->method('convertValue')
            ->willReturnCallback(function ($value, array $options, ConversionHints $hints) {
                self::assertArrayHasKey('grouping', $options);
                self::assertTrue($options['grouping']);

                $value = $hints->createParamReferenceFor($value);

                return "get_customer_type($value)";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_orm_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals('((C.id = get_customer_type(:search_0)))', $conditionGenerator->getWhereClause());
        $this->assertDqlCompiles(
            $conditionGenerator,
            'SELECT i0_.invoice_id AS invoice_id_0, i0_.label AS label_1, i0_.pubdate AS pubdate_2, i0_.status AS status_3, i0_.price_total AS price_total_4, i0_.customer AS customer_5, i0_.parent_id AS parent_id_6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id = get_customer_type(?)))'
        );
    }

    public function testUpdateQueryWithQueryBuilder()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $qb = $this->em->createQueryBuilder();
        $qb->select('C')->from(self::CUSTOMER_CLASS, 'C');

        $conditionGenerator = $this->getConditionGenerator($condition, $qb);

        $whereCase = $conditionGenerator->getWhereClause();

        $this->assertDqlCompiles($conditionGenerator, '', [':search_0' => [2, Type::getType('integer')]]);
        $this->assertEquals('((C.id = :search_0))', $whereCase);
        $this->assertEquals('SELECT C FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer C WHERE ((C.id = :search_0))', $qb->getDQL());
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

    public function testQueryWithPrependAndPrimaryCond()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $condition->setPrimaryCondition(
            new SearchPrimaryCondition(
                SearchConditionBuilder::create($this->getFieldSet())
                    ->field('status')
                        ->addSimpleValue(1)
                        ->addSimpleValue(2)
                    ->end()
                ->getSearchCondition()
                ->getValuesGroup()
            )
        );

        $conditionGenerator = $this->getConditionGenerator($condition);

        $whereCase = $conditionGenerator->getWhereClause('WHERE ');
        $conditionGenerator->updateQuery();

        $this->assertEquals('WHERE (((I.status = :search_0 OR I.status = :search_1))) AND (((C.id = :search_2 OR C.id = :search_3)))', $whereCase);
        $this->assertEquals(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE (((I.status = :search_0 OR I.status = :search_1))) AND (((C.id = :search_2 OR C.id = :search_3)))',
            $conditionGenerator->getQuery()->getDQL()
        );
    }

    public function testEmptyQueryWithPrependAndPrimaryCond()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id2')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $condition->setPrimaryCondition(
            new SearchPrimaryCondition(
                SearchConditionBuilder::create($this->getFieldSet())
                    ->field('status')
                        ->addSimpleValue(1)
                        ->addSimpleValue(2)
                    ->end()
                ->getSearchCondition()
                ->getValuesGroup()
            )
        );

        $conditionGenerator = $this->getConditionGenerator($condition);
        $conditionGenerator->updateQuery();

        $this->assertEquals('WHERE (((I.status = :search_0 OR I.status = :search_1)))', $conditionGenerator->getWhereClause('WHERE '));
        $this->assertEquals(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE (((I.status = :search_0 OR I.status = :search_1)))',
            $conditionGenerator->getQuery()->getDQL()
        );
    }

    private function assertDqlCompiles(DqlConditionGenerator $conditionGenerator, string $expectedSql = '', ?array $parameters = null)
    {
        $conditionGenerator->updateQuery();

        if ($parameters !== null) {
            self::assertEquals($parameters, $conditionGenerator->getParameters()->toArray());
        }

        try {
            $query = $conditionGenerator->getQuery();

            if ($query instanceof QueryBuilder) {
                $query = $query->getQuery();
            }

            $sql = $query->getSQL();

            if ($expectedSql !== '') {
                $expectedSql = preg_replace('/\s+/', ' ', trim($expectedSql));
                $sql = preg_replace('/\s+/', ' ', trim($sql));

                self::assertEquals($expectedSql, $sql);
            }
        } catch (QueryException $e) {
            $this->fail('Compile error: '.$e->getMessage().' with Query: '.$conditionGenerator->getQuery()->getDQL());
        }
    }
}
