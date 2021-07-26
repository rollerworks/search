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
use Rollerworks\Component\Search\Doctrine\Orm\ConditionGenerator;
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

/**
 * @internal
 */
final class DqlConditionGeneratorTest extends OrmTestCase
{
    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = parent::getFieldSet(false);
        $fieldSet->add('status', ChoiceType::class, ['choices' => ['concept' => 0, 'published' => 1, 'paid' => 2]]);
        $fieldSet->add('customer_first_name', TextType::class);

        return $build ? $fieldSet->getFieldSet('invoice') : $fieldSet;
    }

    private function getConditionGenerator(SearchCondition $condition, ?QueryBuilder $qb = null, bool $noMapping = false)
    {
        if ($qb === null) {
            $qb = $this->em->createQueryBuilder()
                ->select('I')
                ->from(ECommerceInvoice::class, 'I')
                ->join('I.customer', 'C');
        }

        $conditionGenerator = $this->getOrmFactory()->createConditionGenerator($qb, $condition);

        if (! $noMapping) {
            $conditionGenerator->setDefaultEntity(self::INVOICE_CLASS, 'I');
            $conditionGenerator->setField('id', 'id', null, null, 'smallint');
            $conditionGenerator->setField('@id', 'id');
            $conditionGenerator->setField('status', 'status');

            $conditionGenerator->setDefaultEntity(self::CUSTOMER_CLASS, 'C');
            $conditionGenerator->setField('customer', 'id');
            $conditionGenerator->setField('@customer', 'id');
            $conditionGenerator->setField('customer_name#first_name', 'firstName');
            $conditionGenerator->setField('customer_name#last_name', 'lastName');
            $conditionGenerator->setField('customer_first_name', 'firstName');
            $conditionGenerator->setField('customer_birthday', 'birthday');
        }

        return $conditionGenerator;
    }

    /** @test */
    public function simple_query(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((C.id = :search_0 OR C.id = :search_1)))',
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

    /** @test */
    public function query_with_multiple_fields(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((C.id = :search_0 OR C.id = :search_1)) AND ((I.status = :search_2 OR I.status = :search_3)))',
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

    /** @test */
    public function empty_result(): void
    {
        $condition = new SearchCondition($this->getFieldSet(), new ValuesGroup());
        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles($conditionGenerator, '');
    }

    /** @test */
    public function excludes(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addExcludedSimpleValue(2)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles($conditionGenerator, 'WHERE (((C.id <> :search_0 AND C.id <> :search_1)))');
    }

    /** @test */
    public function includes_and_excludes(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles($conditionGenerator, 'WHERE ((C.id = :search_0 AND C.id <> :search_1))');
    }

    /** @test */
    public function with_primary_condition_and_user_condition(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->primaryCondition()
                ->field('customer')
                    ->addSimpleValue(2)
                    ->addSimpleValue(5)
                ->end()
            ->end()
            ->field('id')
                ->addSimpleValue(6)
                ->addSimpleValue(3)
            ->end()
            ->field('status')
                ->addSimpleValue(8)
                ->addSimpleValue(9)
            ->end()
            ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((C.id = :search_0 OR C.id = :search_1))) AND (((I.id = :search_2 OR I.id = :search_3)) AND ((I.status = :search_4 OR I.status = :search_5)))',
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
WHERE (((c1_.id = ? OR c1_.id = ?))) AND (((i0_.invoice_id = ? OR i0_.invoice_id = ?)) AND ((i0_.status = ? OR i0_.status = ?)))
SQL
            ,
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
                ':search_2' => [6, Type::getType('smallint')],
                ':search_3' => [3, Type::getType('smallint')],
                ':search_4' => [8, Type::getType('integer')],
                ':search_5' => [9, Type::getType('integer')],
            ]
        );
    }

    /** @test */
    public function with_primary_condition_and_no_user_condition(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->primaryCondition()
                ->field('customer')
                    ->addSimpleValue(2)
                    ->addSimpleValue(5)
                ->end()
                ->field('status')
                    ->addSimpleValue(2)
                    ->addSimpleValue(5)
                ->end()
            ->end()
            ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((C.id = :search_0 OR C.id = :search_1)) AND ((I.status = :search_2 OR I.status = :search_3)))',
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

    /** @test */
    public function sorting_single_field(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->order('@id', 'DESC')
            ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((C.id = :search_0 OR C.id = :search_1))) ORDER BY I.id DESC',
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
ORDER BY i0_.invoice_id DESC
SQL
            ,
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
            ]
        );
    }

    /** @test */
    public function sorting_multiple_fields(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->order('@customer')
            ->order('@id', 'DESC')
            ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((C.id = :search_0 OR C.id = :search_1))) ORDER BY C.id ASC, I.id DESC',
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
ORDER BY c1_.id ASC, i0_.invoice_id DESC
SQL
            ,
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
            ]
        );
    }

    /** @test */
    public function sorting_only(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->order('@id', 'DESC')
            ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'ORDER BY I.id DESC',
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
ORDER BY i0_.invoice_id DESC
SQL
        );
    }

    /** @test */
    public function sorting_with_primary_condition(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->primaryCondition()
                ->order('@id', 'DESC')
            ->end()
            ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((C.id = :search_0 OR C.id = :search_1))) ORDER BY I.id DESC',
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
ORDER BY i0_.invoice_id DESC
SQL
            ,
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
            ]
        );
    }

    /** @test */
    public function sorting_user_and_primary_condition(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->order('@customer', 'DESC')
            ->primaryCondition()
                ->order('@id', 'DESC') // Must be applied first
            ->end()
            ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((C.id = :search_0 OR C.id = :search_1))) ORDER BY I.id DESC, C.id DESC',
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
ORDER BY i0_.invoice_id DESC, c1_.id DESC
SQL
            ,
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
            ]
        );
    }

    /** @test */
    public function sorting_only_with_primary_condition(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->primaryCondition()
                ->order('@id', 'DESC')
            ->end()
            ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'ORDER BY I.id DESC',
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
ORDER BY i0_.invoice_id DESC
SQL
        );
    }

    /** @test */
    public function ranges(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE ((((C.id >= :search_0 AND C.id <= :search_1) OR (C.id >= :search_2 AND C.id <= :search_3) OR (C.id > :search_4 AND C.id <= :search_5) OR (C.id >= :search_6 AND C.id < :search_7))))'
        );
    }

    /** @test */
    public function excluded_ranges(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE ((((C.id <= :search_0 OR C.id >= :search_1) AND (C.id <= :search_2 OR C.id >= :search_3) AND (C.id < :search_4 OR C.id >= :search_5) AND (C.id <= :search_6 OR C.id > :search_7))))'
        );
    }

    /** @test */
    public function single_comparison(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '>'))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles($conditionGenerator, 'WHERE ((C.id > :search_0))');
    }

    /** @test */
    public function multiple_comparisons(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '>'))
                ->add(new Compare(10, '<'))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles($conditionGenerator, 'WHERE (((C.id > :search_0 AND C.id < :search_1)))');
    }

    /** @test */
    public function multiple_comparisons_with_groups(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE ((((C.id = :search_0 OR (C.id > :search_1 AND C.id < :search_2)))) OR ((C.id > :search_3)))'
        );
    }

    /** @test */
    public function excluding_comparisons(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '<>'))
                ->add(new Compare(5, '<>'))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles($conditionGenerator, 'WHERE ((C.id <> :search_0 AND C.id <> :search_1))');
    }

    /** @test */
    public function excluding_comparisons_with_normal(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((C.id > :search_0 AND C.id < :search_1) AND C.id <> :search_2 AND C.id <> :search_3))'
        );
    }

    /** @test */
    public function pattern_matchers(): void
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

        if ($this->conn->getDatabasePlatform()->getName() === 'postgresql') {
            $this->assertDqlCompiles(
                $conditionGenerator,
                'WHERE (((C.firstName LIKE CONCAT(\'%\', :search_0) OR C.firstName LIKE CONCAT(\'%\', :search_1) OR C.firstName LIKE CONCAT(\'%\', :search_2) OR C.firstName LIKE CONCAT(\'%\', :search_3)) AND LOWER(C.firstName) NOT LIKE LOWER(CONCAT(:search_4, \'%\'))))',
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
                $conditionGenerator,
                <<<'DQL'
WHERE (((C.firstName LIKE CONCAT('%', :search_0) OR C.firstName LIKE CONCAT('%', :search_1) OR C.firstName LIKE CONCAT('%', :search_2) OR C.firstName LIKE CONCAT('%', :search_3)) AND LOWER(C.firstName) NOT LIKE LOWER(CONCAT(:search_4, '%'))))
DQL
            );
        }
    }

    /** @test */
    public function sub_groups(): void
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

        $this->assertDqlCompiles($conditionGenerator, 'WHERE (((C.id = :search_0)) OR ((C.id = :search_1)))');
    }

    /** @test */
    public function sub_group_with_root_condition(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            "WHERE (((C.id = :search_0)) AND ((((C.firstName LIKE CONCAT('%', :search_1) OR C.lastName LIKE CONCAT('%', :search_2))))))"
        );
    }

    /** @test */
    public function or_group_root(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            "WHERE ((C.id = :search_0) OR (C.firstName LIKE CONCAT('%', :search_1)))"
        );
    }

    /** @test */
    public function sub_or_group(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            "WHERE ((((C.id = :search_0) OR (C.firstName LIKE CONCAT('%', :search_1)))))"
        );
    }

    /** @test */
    public function column_conversion(): void
    {
        $converter = $this->createMock(ColumnConversion::class);
        $converter
            ->expects(self::atLeastOnce())
            ->method('convertColumn')
            ->willReturnCallback(static function ($column, array $options, ConversionHints $hints) {
                self::assertArrayHasKey('grouping', $options);
                self::assertTrue($options['grouping']);
                self::assertEquals('C.id', $hints->column);

                return "SEARCH_CONVERSION_CAST({$column}, 'customer_type')";
            });

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_orm_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            "WHERE ((SEARCH_CONVERSION_CAST(C.id, 'customer_type') = :search_0))",
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

    /** @test */
    public function value_conversion(): void
    {
        $emConfig = $this->em->getConfiguration();
        $emConfig->addCustomStringFunction('GET_CUSTOMER_TYPE', GetCustomerTypeFunction::class);

        $converter = $this->createMock(ValueConversion::class);
        $converter
            ->expects(self::atLeastOnce())
            ->method('convertValue')
            ->willReturnCallback(static function ($value, array $options, ConversionHints $hints) {
                self::assertArrayHasKey('grouping', $options);
                self::assertTrue($options['grouping']);

                $value = $hints->createParamReferenceFor($value);

                return "get_customer_type({$value})";
            });

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_orm_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE ((C.id = get_customer_type(:search_0)))',
            'SELECT i0_.invoice_id AS invoice_id_0, i0_.label AS label_1, i0_.pubdate AS pubdate_2, i0_.status AS status_3, i0_.price_total AS price_total_4, i0_.customer AS customer_5, i0_.parent_id AS parent_id_6 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((c1_.id = get_customer_type(?)))'
        );
    }

    /** @test */
    public function apply_to_query_builder(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $qb = $this->em->createQueryBuilder();
        $qb->select('C')->from(self::CUSTOMER_CLASS, 'C');

        $conditionGenerator = $this->getConditionGenerator($condition, $qb);

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE ((C.id = :search_0))',
            '',
            [':search_0' => [2, Type::getType('integer')]]
        );
        self::assertEquals(
            'SELECT C FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer C WHERE ((C.id = :search_0))',
            $qb->getDQL()
        );
    }

    /** @test */
    public function apply_without_result(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();
        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertDqlCompiles($conditionGenerator, '');
        self::assertEquals(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I INNER JOIN I.customer C',
            $conditionGenerator->getQueryBuilder()->getDQL()
        );
    }

    /** @test */
    public function query_with_prepend_and_primary_cond(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((I.status = :search_0 OR I.status = :search_1))) AND (((C.id = :search_2 OR C.id = :search_3)))'
        );
    }

    /** @test */
    public function empty_query_with_prepend_and_primary_cond(): void
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

        $this->assertDqlCompiles(
            $conditionGenerator,
            'WHERE (((I.status = :search_0 OR I.status = :search_1)))'
        );
    }

    private function assertDqlCompiles(ConditionGenerator $conditionGenerator, string $expectedDql, string $expectedSql = '', ?array $parameters = null): void
    {
        $qb = $conditionGenerator->getQueryBuilder();
        $mainDql = $qb->getDQL();

        $conditionGenerator->apply();

        $expectedDql = $mainDql . ($expectedDql ? ' ' : '') . $expectedDql;
        $expectedDql = \preg_replace('/\s+/', ' ', \trim($expectedDql));
        $actualDql = \preg_replace('/\s+/', ' ', \trim($qb->getDQL()));

        self::assertEquals($expectedDql, $actualDql);
        self::assertQueryParametersEquals($parameters, $qb);

        try {
            $sql = $qb->getQuery()->getSQL();

            if ($expectedSql !== '') {
                $expectedSql = \preg_replace('/\s+/', ' ', \trim($expectedSql));
                $sql = \preg_replace('/\s+/', ' ', \trim($sql));

                self::assertEquals($expectedSql, $sql);
            }
        } catch (QueryException $e) {
            self::fail('Compile error: ' . $e->getMessage() . ' with Query: ' . $qb->getDQL());
        }
    }
}
