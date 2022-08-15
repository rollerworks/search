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

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\Test\QueryBuilderAssertion;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\SearchPrimaryCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @internal
 */
final class SqlConditionGeneratorTest extends DbalTestCase
{
    private function getConditionGenerator(SearchCondition $condition, ?QueryBuilder $qb = null)
    {
        $qb ??= $this->getConnectionMock()->createQueryBuilder()
            ->select('i')
            ->from('invoice', 'i')
            ->join('i', 'customer', 'c', 'c.id = i.customer')
        ;

        $conditionGenerator = $this->getDbalFactory()->createConditionGenerator(
            $qb,
            $condition
        );

        $conditionGenerator->setField('customer', 'customer', 'i', 'integer');
        $conditionGenerator->setField('customer_name', 'name', 'c', 'string');
        $conditionGenerator->setField('customer_birthday', 'birthday', 'c', 'date');
        $conditionGenerator->setField('status', 'status', 'i', 'integer');
        $conditionGenerator->setField('label', 'label', 'i', 'string');

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
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((i.customer = :search_0 OR i.customer = :search_1)))',
            [':search_0' => [2, 'integer'], ':search_1' => [5, 'integer']]
        );
    }

    /** @test */
    public function empty_query(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        QueryBuilderAssertion::assertQueryBuilderEquals($this->getConditionGenerator($condition), '');
    }

    /** @test */
    public function query_with_primary_cond(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

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

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((i.status = :search_0 OR i.status = :search_1))) AND (((i.customer = :search_2 OR i.customer = :search_3)))',
            [
                ':search_0' => [1, 'integer'],
                ':search_1' => [2, 'integer'],
                ':search_2' => [2, 'integer'],
                ':search_3' => [5, 'integer'],
            ]
        );
    }

    /** @test */
    public function empty_query_with_primary_cond(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

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

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $this->getConditionGenerator($condition),
            ' WHERE (((i.status = :search_0 OR i.status = :search_1)))',
            [
                ':search_0' => [1, 'integer'],
                ':search_1' => [2, 'integer'],
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
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((i.customer = :search_0 OR i.customer = :search_1)) AND ((i.status = :search_2 OR i.status = :search_3)))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
                ':search_2' => [2, 'integer'],
                ':search_3' => [5, 'integer'],
            ]
        );
    }

    /** @test */
    public function query_with_combined_field(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);
        $conditionGenerator->setField('customer#1', 'id', null, 'integer');
        $conditionGenerator->setField('customer#2', 'number2', null, 'integer');

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((id = :search_0 OR id = :search_1 OR number2 = :search_2 OR number2 = :search_3)))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
                ':search_2' => [2, 'integer'],
                ':search_3' => [5, 'integer'],
            ]
        );
    }

    /** @test */
    public function query_with_combined_field_and_custom_alias(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);
        $conditionGenerator->setField('customer#1', 'id', null, 'integer');
        $conditionGenerator->setField('customer#2', 'number2', 'c', 'string');

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((id = :search_0 OR id = :search_1 OR c.number2 = :search_2 OR c.number2 = :search_3)))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
                ':search_2' => [2, 'string'],
                ':search_3' => [5, 'string'],
            ]
        );
    }

    /** @test */
    public function empty_condition(): void
    {
        $condition = new SearchCondition($this->getFieldSet(), new ValuesGroup());
        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ''
        );
    }

    /** @test */
    public function excludes(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addExcludedSimpleValue(2)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((i.customer <> :search_0 AND i.customer <> :search_1)))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
            ]
        );
    }

    /** @test */
    public function includes_and_excludes(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE ((i.customer = :search_0 AND i.customer <> :search_1))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
            ]
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
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE ((((i.customer >= :search_0 AND i.customer <= :search_1) OR (i.customer >= :search_2 AND i.customer <= :search_3) OR ' .
            '(i.customer > :search_4 AND i.customer <= :search_5) OR (i.customer >= :search_6 AND i.customer < :search_7))))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
                ':search_2' => [10, 'integer'],
                ':search_3' => [20, 'integer'],
                ':search_4' => [60, 'integer'],
                ':search_5' => [70, 'integer'],
                ':search_6' => [100, 'integer'],
                ':search_7' => [150, 'integer'],
            ]
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
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE ((((i.customer <= :search_0 OR i.customer >= :search_1) AND (i.customer <= :search_2 OR ' .
            'i.customer >= :search_3) AND (i.customer < :search_4 OR i.customer >= :search_5) AND ' .
            '(i.customer <= :search_6 OR i.customer > :search_7))))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
                ':search_2' => [10, 'integer'],
                ':search_3' => [20, 'integer'],
                ':search_4' => [60, 'integer'],
                ':search_5' => [70, 'integer'],
                ':search_6' => [100, 'integer'],
                ':search_7' => [150, 'integer'],
            ]
        );
    }

    /** @test */
    public function single_comparison(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '>'))
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE ((i.customer > :search_0))',
            [
                ':search_0' => [2, 'integer'],
            ]
        );
    }

    /** @test */
    public function multiple_comparisons(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '>'))
                ->add(new Compare(10, '<'))
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((i.customer > :search_0 AND i.customer < :search_1)))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [10, 'integer'],
            ]
        );
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
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE ((((i.customer = :search_0 OR (i.customer > :search_1 AND i.customer < :search_2)))) OR ((i.customer > :search_3)))',
            [
                ':search_0' => [20, 'integer'],
                ':search_1' => [2, 'integer'],
                ':search_2' => [10, 'integer'],
                ':search_3' => [30, 'integer'],
            ]
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
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE ((i.customer <> :search_0 AND i.customer <> :search_1))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
            ]
        );
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
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((i.customer > :search_0 AND i.customer < :search_1) AND i.customer <> :search_2 AND i.customer <> :search_3))',
            [
                ':search_0' => [30, 'integer'],
                ':search_1' => [50, 'integer'],
                ':search_2' => [35, 'integer'],
                ':search_3' => [45, 'integer'],
            ]
        );
    }

    /** @test */
    public function pattern_matchers(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer_name')
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                ->add(new PatternMatch('My name', PatternMatch::PATTERN_EQUALS))
                ->add(new PatternMatch('Last', PatternMatch::PATTERN_NOT_EQUALS))
                ->add(new PatternMatch('Spider', PatternMatch::PATTERN_EQUALS, true))
                ->add(new PatternMatch('Piggy', PatternMatch::PATTERN_NOT_EQUALS, true))
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            " WHERE (((c.name LIKE '%' || :search_0 OR c.name LIKE '%' || :search_1 OR c.name = :search_2 OR LOWER(c.name) = LOWER(:search_3)) AND (LOWER(c.name) NOT LIKE LOWER(:search_4 || '%') AND c.name <> :search_5 AND LOWER(c.name) <> LOWER(:search_6))))",
            [
                ':search_0' => ['foo', 'text'],
                ':search_1' => ['fo\\\'o', 'text'],
                ':search_2' => ['My name', 'text'],
                ':search_3' => ['Spider', 'text'],
                ':search_4' => ['bar', 'text'],
                ':search_5' => ['Last', 'text'],
                ':search_6' => ['Piggy', 'text'],
            ]
        );
    }

    /** @test */
    public function sub_groups(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->field('customer')->addSimpleValue(2)->end()
            ->end()
            ->group()
                ->field('customer')->addSimpleValue(3)->end()
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((i.customer = :search_0)) OR ((i.customer = :search_1)))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [3, 'integer'],
            ]
        );
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
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            " WHERE (((i.customer = :search_0)) AND (((c.name LIKE '%' || :search_1))))",
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => ['foo', 'text'],
            ]
        );
    }

    /** @test */
    public function or_group_root(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet(), ValuesGroup::GROUP_LOGICAL_OR)
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
            ->field('customer_name')
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            " WHERE ((i.customer = :search_0) OR (c.name LIKE '%' || :search_1))",
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => ['foo', 'text'],
            ]
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
                    ->field('customer_name')
                        ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                    ->end()
                ->end()
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            " WHERE ((((i.customer = :search_0) OR (c.name LIKE '%' || :search_1))))",
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => ['foo', 'text'],
            ]
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

                self::assertEquals('i', $hints->field->alias);
                self::assertEquals('i.customer', $hints->column);

                return "CAST({$column} AS customer_type)";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_dbal_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((CAST(i.customer AS customer_type) = :search_0 OR CAST(i.customer AS customer_type) = :search_1)))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
            ]
        );
    }

    /** @test */
    public function value_conversion(): void
    {
        $converter = $this->createMock(ValueConversion::class);
        $converter
            ->expects(self::atLeastOnce())
            ->method('convertValue')
            ->willReturnCallback(static function ($value, array $options, ConversionHints $hints) {
                self::assertArrayHasKey('grouping', $options);
                self::assertTrue($options['grouping']);

                $value = $hints->createParamReferenceFor($value);

                return "get_customer_type({$value})";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_dbal_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((i.customer = get_customer_type(:search_0) OR i.customer = get_customer_type(:search_1))))',
            [
                ':search_0' => 2,
                ':search_1' => 5,
            ]
        );
    }

    /** @test */
    public function conversion_strategy_value(): void
    {
        $converter = $this->createMock(ValueConversion::class);
        $converter
            ->expects(self::atLeastOnce())
            ->method('convertValue')
            ->willReturnCallback(static function ($value, array $passedOptions, ConversionHints $hints) {
                self::assertArrayHasKey('pattern', $passedOptions);
                self::assertEquals('dd-MM-yy', $passedOptions['pattern']);

                if ($value instanceof \DateTimeImmutable) {
                    return 'CAST(' . $hints->createParamReferenceFor($value->format('Y-m-d'), Type::getType('string')) . ' AS AGE)';
                }

                return $hints->createParamReferenceFor($value, Type::getType('integer'));
            })
        ;

        $fieldSet = $this->getFieldSet(false);
        $fieldSet->add('customer_birthday', DateType::class, ['doctrine_dbal_conversion' => $converter, 'pattern' => 'dd-MM-yy']);

        $condition = SearchConditionBuilder::create($fieldSet->getFieldSet())
            ->field('customer_birthday')
                ->addSimpleValue(18)
                ->addSimpleValue(new \DateTimeImmutable('2001-01-15', new \DateTimeZone('UTC')))
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((c.birthday = :search_0 OR c.birthday = CAST(:search_1 AS AGE))))',
            [
                ':search_0' => [18, 'integer'],
                ':search_1' => ['2001-01-15', 'string'],
            ]
        );
    }

    /** @test */
    public function conversion_strategy_column(): void
    {
        $converter = $this->createMock(ColumnConversion::class);
        $converter
            ->expects(self::atLeastOnce())
            ->method('convertColumn')
            ->willReturnCallback(static function ($column, array $options, ConversionHints $hints) {
                if (\is_int($hints->originalValue)) {
                    return "search_conversion_age({$column})";
                }

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
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);
        $conditionGenerator->setField('customer_birthday', 'birthday', 'c', 'string');

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((search_conversion_age(c.birthday) = :search_0 OR c.birthday = :search_1)))',
            [
                ':search_0' => [18, 'string'],
                ':search_1' => ['2001-01-15', 'string'],
            ]
        );
    }

    /** @test */
    public function lazy_conversion_loading(): void
    {
        $converter = $this->createMock(ColumnConversion::class);
        $converter
            ->expects(self::atLeastOnce())
            ->method('convertColumn')
            ->willReturnCallback(static function ($column, array $options, ConversionHints $hints) {
                self::assertArrayHasKey('grouping', $options);
                self::assertTrue($options['grouping']);
                self::assertEquals('i', $hints->field->alias);
                self::assertEquals('i.customer', $hints->column);

                return "CAST({$column} AS customer_type)";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, [
            'grouping' => true,
            'doctrine_dbal_conversion' => static fn () => $converter,
        ]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $conditionGenerator = $this->getConditionGenerator($condition);

        QueryBuilderAssertion::assertQueryBuilderEquals(
            $conditionGenerator,
            ' WHERE (((CAST(i.customer AS customer_type) = :search_0 OR CAST(i.customer AS customer_type) = :search_1)))',
            [
                ':search_0' => [2, 'integer'],
                ':search_1' => [5, 'integer'],
            ]
        );
    }
}
