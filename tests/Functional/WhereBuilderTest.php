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

use Doctrine\DBAL\Schema\Schema as DbSchema;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\InvoiceNumber;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Functional WhereBuilderTest, ensures queries are executable.
 * This tests does not ensure the correct result is returned,
 * this handled by another test-class.
 *
 * @group functional
 */
final class WhereBuilderTest extends FunctionalDbalTestCase
{
    protected function setUpDbSchema(DbSchema $schema)
    {
        $invoiceTable = $schema->createTable('invoice');
        $invoiceTable->addColumn('id', 'integer');
        $invoiceTable->addColumn('status', 'integer');
        $invoiceTable->addColumn('label', 'string');
        $invoiceTable->addColumn('customer', 'integer');
        $invoiceTable->setPrimaryKey(['id']);

        $customerTable = $schema->createTable('customer');
        $customerTable->addColumn('id', 'integer');
        $customerTable->addColumn('name', 'string');
        $customerTable->addColumn('birthday', 'date');
        $customerTable->setPrimaryKey(['id']);
    }

    protected function getQuery()
    {
        return 'SELECT i.*, c.* FROM invoice AS i JOIN customer AS c ON (c.id = i.customer) WHERE ';
    }

    /**
     * Configure fields of the WhereBuilder.
     *
     * @param WhereBuilder $whereBuilder
     */
    protected function configureWhereBuilder(WhereBuilder $whereBuilder)
    {
        $whereBuilder->setField('customer', 'customer', 'integer', 'i');
        $whereBuilder->setField('customer_name', 'name', 'string', 'c');
        $whereBuilder->setField('customer_birthday', 'birthday', 'string', 'c'); // don't use date as this breaks the binding
        $whereBuilder->setField('status', 'status', 'integer', 'i');
        $whereBuilder->setField('label', 'label', 'string', 'i');
    }

    public function testSimpleQuery()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
    }

    public function testQueryWithEmbeddedValues()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
    }

    public function testExcludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addExcludedSimpleValue(2)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
    }

    public function testIncludesAndExcludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
    }

    public function testSingleComparison()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '>'))
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
    }

    public function testMultipleComparisons()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '>'))
                ->add(new Compare(10, '<'))
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
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
                ->end()
            ->end()
            ->group()
                ->field('customer')
                    ->add(new Compare(30, '>'))
                ->end()
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
    }

    public function testExcludingComparisons()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '<>'))
                ->add(new Compare(5, '<>'))
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
    }

    public function testExcludingComparisonsWithNormal()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->add(new Compare(2, '<>'))
                ->add(new Compare(5, '<>'))
                ->add(new Compare(30, '>'))
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
    }

    public function testPatternMatchers()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer_name')
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                ->add(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                ->add(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
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

        $this->assertQueryIsExecutable($condition);
    }

    public function testValueConversion()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('label')
                ->addSimpleValue(InvoiceNumber::createFromString('2015-001'))
                ->addSimpleValue(InvoiceNumber::createFromString('2015-005'))
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
    }

    public function testFieldConversion()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getDbalFactory()->createWhereBuilder($this->conn, $condition);
        $this->configureWhereBuilder($whereBuilder);
        $type = $this->conn->getDatabasePlatform()->getName() === 'mysql' ? 'SIGNED' : 'INTEGER';

        $converter = $this->createMock(SqlFieldConversionInterface::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlField')
            ->will($this->returnCallback(function ($column) use ($type) {
                return "CAST($column AS $type)";
            }))
        ;

        $whereBuilder->setConverter('customer', $converter);
        $this->assertQueryIsExecutable($whereBuilder);
    }

    public function testSqlValueConversion()
    {
        $fieldSet = $this->getFieldSet();
        $condition = SearchConditionBuilder::create($fieldSet)
            ->field('customer')
                ->addSimpleValue(2)
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getDbalFactory()->createWhereBuilder($this->conn, $condition);
        $this->configureWhereBuilder($whereBuilder);

        $type = $this->conn->getDatabasePlatform()->getName() === 'mysql' ? 'SIGNED' : 'INTEGER';

        $converter = $this->createMock(SqlValueConversionInterface::class);
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlValue')
            ->will($this->returnCallback(function ($input) use ($type) {
                return "CAST($input AS $type)";
            }))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->will($this->returnArgument(0))
        ;

        $whereBuilder->setConverter('customer', $converter);
        $this->assertQueryIsExecutable($whereBuilder);
    }

    public function testConversionStrategy()
    {
        $date = new \DateTime('2001-01-15', new \DateTimeZone('UTC'));

        $fieldSet = $this->getFieldSet(false);
        $fieldSet->add('customer_birthday', BirthdayType::class);

        $fieldSet = $fieldSet->getFieldSet();

        $condition = SearchConditionBuilder::create($fieldSet)
            ->field('customer_birthday')
                ->addSimpleValue(18)
                ->addSimpleValue($date)
            ->end()
        ->getSearchCondition();

        $this->assertQueryIsExecutable($condition);
    }
}
