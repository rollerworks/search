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

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional;

use Carbon\CarbonInterval;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema as DbSchema;
use Doctrine\DBAL\Types\Types;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Functional ConditionGeneratorTest, ensures queries are executable.
 * This tests does not ensure the correct result is returned,
 * this handled by another test-class.
 *
 * @group functional
 *
 * @internal
 */
final class SqlConditionGeneratorTest extends FunctionalDbalTestCase
{
    protected function setUpDbSchema(DbSchema $schema): void
    {
        $invoiceTable = $schema->createTable('invoice');
        $invoiceTable->addColumn('id', 'integer', ['notNull' => false]);
        $invoiceTable->addColumn('status', 'integer', ['notNull' => false]);
        $invoiceTable->addColumn('label', 'string', ['notNull' => false, 'length' => 255]);
        $invoiceTable->addColumn('customer', 'integer', ['notNull' => false]);
        $invoiceTable->setPrimaryKey(['id']);

        $customerTable = $schema->createTable('customer');
        $customerTable->addColumn('id', 'integer', ['notNull' => false, 'length' => 255]);
        $customerTable->addColumn('name', 'string', ['notNull' => false, 'length' => 255]);
        $customerTable->addColumn('birthday', 'date_immutable', ['notNull' => false]);
        $customerTable->setPrimaryKey(['id']);
    }

    protected function getQuery(): QueryBuilder
    {
        return $this->conn->createQueryBuilder()
            ->select('i.*', 'c.*')
            ->from('invoice', 'i')
            ->join('i', 'customer', 'c', 'c.id = i.customer')
        ;
    }

    /**
     * Configure fields of the ConditionGenerator.
     */
    protected function configureConditionGenerator(ConditionGenerator $conditionGenerator): void
    {
        $conditionGenerator->setField('customer', 'customer', 'i', 'integer');
        $conditionGenerator->setField('customer_name', 'name', 'c', 'string');
        $conditionGenerator->setField('customer_birthday', 'birthday', 'c', 'string'); // don't use date as this breaks the binding
        $conditionGenerator->setField('status', 'status', 'i', 'integer');
        $conditionGenerator->setField('label', 'label', 'i', 'string');
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

        $this->assertQueryIsExecutable($condition);
    }

    /** @test */
    public function query_with_embedded_values(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition()
        ;

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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
                ->end()
            ->end()
            ->group()
                ->field('customer')
                    ->add(new Compare(30, '>'))
                ->end()
            ->end()
        ->getSearchCondition()
        ;

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
    }

    /** @test */
    public function excluding_comparisons_with_normal(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '<>'))
                ->add(new Compare(5, '<>'))
                ->add(new Compare(30, '>'))
            ->end()
        ->getSearchCondition()
        ;

        $this->assertQueryIsExecutable($condition);
    }

    /** @test */
    public function pattern_matchers(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer_name')
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
            ->end()
        ->getSearchCondition()
        ;

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
    }

    /** @test */
    public function column_conversion(): void
    {
        $type = $this->conn->getDatabasePlatform() instanceof AbstractMySQLPlatform ? 'SIGNED' : 'INTEGER';
        $converter = $this->createMock(ColumnConversion::class);
        $converter
            ->expects(self::atLeastOnce())
            ->method('convertColumn')
            ->willReturnCallback(static fn ($column) => "CAST({$column} AS {$type})")
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

        $this->assertQueryIsExecutable($condition);
    }

    /** @test */
    public function value_conversion(): void
    {
        $type = $this->conn->getDatabasePlatform() instanceof AbstractMySQLPlatform ? 'SIGNED' : 'INTEGER';
        $converter = $this->createMock(ValueConversion::class);
        $converter
            ->expects(self::atLeastOnce())
            ->method('convertValue')
            ->willReturnCallback(static fn ($input) => "CAST({$input} AS {$type})")
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

        $this->assertQueryIsExecutable($condition);
    }

    /** @test */
    public function conversion_strategy(): void
    {
        $date = new \DateTimeImmutable('2001-01-15', new \DateTimeZone('UTC'));

        $fieldSet = $this->getFieldSet(false);
        $fieldSet->add('customer_birthday', BirthdayType::class);

        $fieldSet = $fieldSet->getFieldSet();

        $condition = SearchConditionBuilder::create($fieldSet)
            ->field('customer_birthday')
                ->addSimpleValue(18)
                ->addSimpleValue($date)
            ->end()
        ->getSearchCondition()
        ;

        $this->assertQueryIsExecutable($condition);
    }

    /** @test */
    public function conversion_strategy2(): void
    {
        $date = new \DateTimeImmutable('2001-01-15', new \DateTimeZone('UTC'));

        $fieldSet = $this->getFieldSet(false);
        $fieldSet->add('customer_birthday', DateTimeType::class, ['allow_relative' => true]);

        $fieldSet = $fieldSet->getFieldSet();

        $condition = SearchConditionBuilder::create($fieldSet)
            ->field('customer_birthday')
                ->addSimpleValue(CarbonInterval::fromString('1 year 2 weeks 8 seconds'))
                ->addSimpleValue(CarbonInterval::fromString('1 year 2 weeks 8 seconds')->invert())
                ->addSimpleValue($date)
                ->add(new Range(CarbonInterval::fromString('1 year'), CarbonInterval::fromString('10 year')))
            ->end()
        ->getSearchCondition()
        ;

        if ($this->conn->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $this->assertQueryIsExecutable(
                $condition,
                ' WHERE (((c.birthday = NOW() + CAST(:search_0 AS interval) OR c.birthday = NOW() - CAST(:search_1 AS interval) OR c.birthday = :search_2 OR (c.birthday >= NOW() + CAST(:search_3 AS interval) AND c.birthday <= NOW() + CAST(:search_4 AS interval)))))',
                [
                    'search_0' => ['1 year 2 weeks 8 seconds', 'string'],
                    'search_1' => ['1 year 2 weeks 8 seconds', 'string'],
                    'search_2' => [$date, Types::DATETIME_IMMUTABLE],
                    'search_3' => ['1 year', 'string'],
                    'search_4' => ['10 years', 'string'],
                ]
            );
        } elseif ($this->conn->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            $this->assertQueryIsExecutable(
                $condition,
                ' WHERE (((c.birthday = NOW() + INTERVAL 1 YEAR + INTERVAL 2 WEEK + INTERVAL 8 SECOND OR c.birthday = NOW() - INTERVAL 1 YEAR - INTERVAL 2 WEEK - INTERVAL 8 SECOND OR c.birthday = :search_0 OR (c.birthday >= NOW() + INTERVAL 1 YEAR AND c.birthday <= NOW() + INTERVAL 10 YEAR))))',
                [
                    'search_0' => [$date, Types::DATETIME_IMMUTABLE],
                ]
            );
        }
    }
}
