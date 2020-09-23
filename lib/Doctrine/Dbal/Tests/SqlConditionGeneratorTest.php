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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
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

final class SqlConditionGeneratorTest extends DbalTestCase
{
    private function getConditionGenerator(SearchCondition $condition, Connection $connection = null)
    {
        $conditionGenerator = $this->getDbalFactory()->createConditionGenerator(
            $connection ?: $this->getConnectionMock(),
            $condition
        );

        $conditionGenerator->setField('customer', 'customer', 'I', 'integer');
        $conditionGenerator->setField('customer_name', 'name', 'C', 'string');
        $conditionGenerator->setField('customer_birthday', 'birthday', 'C', 'date');
        $conditionGenerator->setField('status', 'status', 'I', 'integer');
        $conditionGenerator->setField('label', 'label', 'I', 'string');

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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((I.customer = :search_0 OR I.customer = :search_1)))',
            [':search_0' => [2, Type::getType('integer')], ':search_1' => [5, Type::getType('integer')]]
        );
    }

    private function assertGeneratedQueryEquals(ConditionGenerator $conditionGenerator, string $query, array $params): void
    {
        self::assertEquals($query, $conditionGenerator->getWhereClause());
        self::assertEquals($params, $conditionGenerator->getParameters()->toArray());
    }

    public function testQueryWithPrepend()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals(
            'WHERE (((I.customer = :search_0 OR I.customer = :search_1)))',
            $conditionGenerator->getWhereClause('WHERE ')
        );
    }

    public function testEmptyQueryWithPrepend()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertEquals('', $conditionGenerator->getWhereClause('WHERE '));
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

        $this->assertEquals(
            'WHERE (((I.status = :search_0 OR I.status = :search_1))) AND (((I.customer = :search_2 OR I.customer = :search_3)))',
            $conditionGenerator->getWhereClause('WHERE ')
        );
    }

    public function testEmptyQueryWithPrependAndPrimaryCond()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
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

        $this->assertEquals('WHERE (((I.status = :search_0 OR I.status = :search_1)))', $conditionGenerator->getWhereClause('WHERE '));
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((I.customer = :search_0 OR I.customer = :search_1)) AND ((I.status = :search_2 OR I.status = :search_3)))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
                ':search_2' => [2, Type::getType('integer')],
                ':search_3' => [5, Type::getType('integer')],
            ]
        );
    }

    public function testQueryWithCombinedField()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);
        $conditionGenerator->setField('customer#1', 'id', null, 'integer');
        $conditionGenerator->setField('customer#2', 'number2', null, 'integer');

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((id = :search_0 OR id = :search_1 OR number2 = :search_2 OR number2 = :search_3)))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
                ':search_2' => [2, Type::getType('integer')],
                ':search_3' => [5, Type::getType('integer')],
            ]
        );
    }

    public function testQueryWithCombinedFieldAndCustomAlias()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);
        $conditionGenerator->setField('customer#1', 'id', null, 'integer');
        $conditionGenerator->setField('customer#2', 'number2', 'C', 'string');

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((id = :search_0 OR id = :search_1 OR C.number2 = :search_2 OR C.number2 = :search_3)))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
                ':search_2' => [2, Type::getType('string')],
                ':search_3' => [5, Type::getType('string')],
            ]
        );
    }

    public function testEmptyResult()
    {
        $connection = $this->getConnectionMock();
        $condition = new SearchCondition($this->getFieldSet(), new ValuesGroup());
        $conditionGenerator = $this->getConditionGenerator($condition, $connection);

        $this->assertEquals('', $conditionGenerator->getWhereClause());
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((I.customer <> :search_0 AND I.customer <> :search_1)))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
            ]
        );
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '((I.customer = :search_0 AND I.customer <> :search_1))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
            ]
        );
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '((((I.customer >= :search_0 AND I.customer <= :search_1) OR (I.customer >= :search_2 AND I.customer <= :search_3) OR '.
            '(I.customer > :search_4 AND I.customer <= :search_5) OR (I.customer >= :search_6 AND I.customer < :search_7))))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
                ':search_2' => [10, Type::getType('integer')],
                ':search_3' => [20, Type::getType('integer')],
                ':search_4' => [60, Type::getType('integer')],
                ':search_5' => [70, Type::getType('integer')],
                ':search_6' => [100, Type::getType('integer')],
                ':search_7' => [150, Type::getType('integer')],
            ]
        );
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '((((I.customer <= :search_0 OR I.customer >= :search_1) AND (I.customer <= :search_2 OR '.
            'I.customer >= :search_3) AND (I.customer < :search_4 OR I.customer >= :search_5) AND '.
            '(I.customer <= :search_6 OR I.customer > :search_7))))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
                ':search_2' => [10, Type::getType('integer')],
                ':search_3' => [20, Type::getType('integer')],
                ':search_4' => [60, Type::getType('integer')],
                ':search_5' => [70, Type::getType('integer')],
                ':search_6' => [100, Type::getType('integer')],
                ':search_7' => [150, Type::getType('integer')],
            ]
        );
    }

    public function testSingleComparison()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '>'))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '((I.customer > :search_0))',
            [
                ':search_0' => [2, Type::getType('integer')],
            ]
        );
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((I.customer > :search_0 AND I.customer < :search_1)))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [10, Type::getType('integer')],
            ]
        );
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '((((I.customer = :search_0 OR (I.customer > :search_1 AND I.customer < :search_2)))) OR ((I.customer > :search_3)))',
            [
                ':search_0' => [20, Type::getType('integer')],
                ':search_1' => [2, Type::getType('integer')],
                ':search_2' => [10, Type::getType('integer')],
                ':search_3' => [30, Type::getType('integer')],
            ]
        );
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '((I.customer <> :search_0 AND I.customer <> :search_1))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
            ]
        );
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((I.customer > :search_0 AND I.customer < :search_1) AND I.customer <> :search_2 AND I.customer <> :search_3))',
            [
                ':search_0' => [30, Type::getType('integer')],
                ':search_1' => [50, Type::getType('integer')],
                ':search_2' => [35, Type::getType('integer')],
                ':search_3' => [45, Type::getType('integer')],
            ]
        );
    }

    public function testPatternMatchers()
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
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            "(((C.name LIKE '%' || :search_0 OR C.name LIKE '%' || :search_1 OR C.name = :search_2 OR LOWER(C.name) = LOWER(:search_3)) AND (LOWER(C.name) NOT LIKE LOWER(:search_4 || '%') AND C.name <> :search_5 AND LOWER(C.name) <> LOWER(:search_6))))",
            [
                ':search_0' => ['foo', Type::getType('text')],
                ':search_1' => ['fo\\\'o', Type::getType('text')],
                ':search_2' => ['My name', Type::getType('text')],
                ':search_3' => ['Spider', Type::getType('text')],
                ':search_4' => ['bar', Type::getType('text')],
                ':search_5' => ['Last', Type::getType('text')],
                ':search_6' => ['Piggy', Type::getType('text')],
            ]
        );
    }

    public function testSubGroups()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->field('customer')->addSimpleValue(2)->end()
            ->end()
            ->group()
                ->field('customer')->addSimpleValue(3)->end()
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((I.customer = :search_0)) OR ((I.customer = :search_1)))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [3, Type::getType('integer')],
            ]
        );
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

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            "(((I.customer = :search_0)) AND (((C.name LIKE '%' || :search_1))))",
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => ['foo', Type::getType('text')],
            ]
        );
    }

    public function testOrGroupRoot()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet(), ValuesGroup::GROUP_LOGICAL_OR)
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
            ->field('customer_name')
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            "((I.customer = :search_0) OR (C.name LIKE '%' || :search_1))",
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => ['foo', Type::getType('text')],
            ]
        );
    }

    public function testSubOrGroup()
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
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            "((((I.customer = :search_0) OR (C.name LIKE '%' || :search_1))))",
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => ['foo', Type::getType('text')],
            ]
        );
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

                self::assertEquals('I', $hints->field->alias);
                self::assertEquals('I.customer', $hints->column);

                return "CAST($column AS customer_type)";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_dbal_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((CAST(I.customer AS customer_type) = :search_0 OR CAST(I.customer AS customer_type) = :search_1)))',
            [
                ':search_0' => [2, Type::getType('integer')],
                ':search_1' => [5, Type::getType('integer')],
            ]
        );
    }

    public function testValueConversion()
    {
        $converter = $this->createMock(ValueConversion::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->willReturnCallback(function ($value, array $options, ConversionHints $hints) {
                self::assertArrayHasKey('grouping', $options);
                self::assertTrue($options['grouping']);

                $value = $hints->createParamReferenceFor($value);

                return "get_customer_type($value)";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, ['grouping' => true, 'doctrine_dbal_conversion' => $converter]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((I.customer = get_customer_type(:search_0) OR I.customer = get_customer_type(:search_1))))',
            [
                ':search_0' => [2, null],
                ':search_1' => [5, null],
            ]
        );
    }

    public function testConversionStrategyValue()
    {
        $converter = $this->createMock(ValueConversion::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->willReturnCallback(function ($value, array $passedOptions, ConversionHints $hints) {
                self::assertArrayHasKey('pattern', $passedOptions);
                self::assertEquals('dd-MM-yy', $passedOptions['pattern']);

                if ($value instanceof \DateTimeImmutable) {
                    return 'CAST('.$hints->createParamReferenceFor($value->format('Y-m-d'), Type::getType('string')).' AS AGE)';
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
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((C.birthday = :search_0 OR C.birthday = CAST(:search_1 AS AGE))))',
            [
                ':search_0' => [18, Type::getType('integer')],
                ':search_1' => ['2001-01-15', Type::getType('string')],
            ]
        );
    }

    public function testConversionStrategyColumn()
    {
        $converter = $this->createMock(ColumnConversion::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertColumn')
            ->willReturnCallback(function ($column, array $options, ConversionHints $hints) {
                if (\is_int($hints->originalValue)) {
                    return "search_conversion_age($column)";
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
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);
        $conditionGenerator->setField('customer_birthday', 'birthday', 'C', 'string');

        $this->assertGeneratedQueryEquals(
            $conditionGenerator,
            '(((search_conversion_age(C.birthday) = :search_0 OR C.birthday = :search_1)))',
            [
                ':search_0' => [18, Type::getType('string')],
                ':search_1' => ['2001-01-15', Type::getType('string')],
            ]
        );
    }

    public function testLazyConversionLoading()
    {
        $converter = $this->createMock(ColumnConversion::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertColumn')
            ->willReturnCallback(function ($column, array $options, ConversionHints $hints) {
                self::assertArrayHasKey('grouping', $options);
                self::assertTrue($options['grouping']);
                self::assertEquals('I', $hints->field->alias);
                self::assertEquals('I.customer', $hints->column);

                return "CAST($column AS customer_type)";
            })
        ;

        $fieldSetBuilder = $this->getFieldSet(false);
        $fieldSetBuilder->add('customer', IntegerType::class, [
            'grouping' => true,
            'doctrine_dbal_conversion' => function () use ($converter) {
                return $converter;
            },
        ]);

        $condition = SearchConditionBuilder::create($fieldSetBuilder->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $conditionGenerator = $this->getConditionGenerator($condition);
        self::assertEquals('(((CAST(I.customer AS customer_type) = :search_0 OR CAST(I.customer AS customer_type) = :search_1)))', $conditionGenerator->getWhereClause());
    }
}
