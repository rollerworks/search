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

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema as DbSchema;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\Extension\Core\Type\ChoiceType;
use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\MoneyType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\Input\StringQueryInput;
use Rollerworks\Component\Search\SearchPrimaryCondition;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;

/**
 * Ensures the expected results are actually found.
 *
 * Uses the StringQuery input-processor for a readable condition
 * and ensures the input values are properly transformed.
 *
 * This example uses a 'classic' invoice system
 * with three tables:
 *
 * * invoice
 * * invoice_details
 * * customer
 *
 * For simplicity this example doesn't do tax calculation.
 *
 * @group functional
 *
 * @internal
 */
final class SqlConditionGeneratorResultsTest extends FunctionalDbalTestCase
{
    /**
     * @var StringQueryInput
     */
    private $inputProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inputProcessor = new StringQueryInput();
    }

    protected function setUpDbSchema(DbSchema $schema): void
    {
        $customerTable = $schema->createTable('customer');
        $customerTable->addOption('collation', 'utf8_bin');
        $customerTable->addColumn('id', 'integer');
        $customerTable->addColumn('first_name', 'string', ['length' => 255]);
        $customerTable->addColumn('last_name', 'string', ['length' => 255]);
        $customerTable->addColumn('birthday', 'date_immutable');
        $customerTable->addColumn('regdate', 'date_immutable');
        $customerTable->setPrimaryKey(['id']);

        $invoiceTable = $schema->createTable('invoice');
        $invoiceTable->addOption('collation', 'utf8_bin');
        $invoiceTable->addColumn('id', 'integer');
        $invoiceTable->addColumn('customer', 'integer');
        $invoiceTable->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $invoiceTable->addColumn('pub_date', 'date_immutable', ['notnull' => false]);
        $invoiceTable->addColumn('status', 'integer');
        $invoiceTable->addColumn('price_total', 'decimal', ['scale' => 2, 'precision' => 10]);
        $invoiceTable->setPrimaryKey(['id']);
        $invoiceTable->addUniqueIndex(['label']);

        $invoiceDetailsTable = $schema->createTable('invoice_details');
        $invoiceDetailsTable->addOption('collation', 'utf8_bin');
        $invoiceDetailsTable->addColumn('id', 'integer');
        $invoiceDetailsTable->addColumn('invoice', 'integer');
        $invoiceDetailsTable->addColumn('label', 'string', ['length' => 255]);
        $invoiceDetailsTable->addColumn('quantity', 'integer');
        $invoiceDetailsTable->addColumn('price', 'decimal', ['scale' => 2, 'precision' => 10]);
        $invoiceDetailsTable->addColumn('total', 'decimal', ['scale' => 2, 'precision' => 10]);
        $invoiceDetailsTable->setPrimaryKey(['id']);
    }

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        $date = static fn (string $input) => new \DateTimeImmutable($input, new \DateTimeZone('UTC'));

        return [
            SchemaRecord::create(
                'customer',
                [
                    'id' => 'integer',
                    'first_name' => 'string',
                    'last_name' => 'string',
                    'birthday' => 'date_immutable',
                    'regdate' => 'date_immutable',
                ]
            )
            ->records()
                ->add([1, 'Peter', 'Pang', $date('1980-11-20'), $date('2005-11-20')])
                ->add([2, 'Leroy', 'Jenkins', $date('2000-05-15'), $date('2005-05-20')])
                ->add([3, 'Doctor', 'Who', $date('2005-12-10'), $date('2005-02-20')])
                ->add([4, 'Spider', 'Pig', $date('2012-06-10'), $date('2012-07-20')])
                ->add([5, 'Nicklas', 'Cake', $date('1964-01-07'), $date('2006-09-31')])
            ->end(),

            // Two invoices are paid, one is a concept and three are unpaid, one is overdue
            SchemaRecord::create(
                'invoice',
                [
                    'id' => 'integer',
                    'customer' => 'integer',
                    'label' => 'string',
                    'pub_date' => 'date_immutable',
                    'status' => 'integer',
                    'price_total' => 'decimal',
                ]
            )
            ->records()
                ->add([1, 1, '2010-001', $date('2010-05-10'), 2, '100.00']) // 'Peter', 'Pang'
                ->add([2, 2, '2010-002', $date('2010-05-10'), 2, '90.00']) // 'Leroy', 'Jenkins'
                ->add([3, 2, null, null, 0, '10.00']) // concept - 'Leroy', 'Jenkins'
                // unpaid //
                ->add([4, 2, '2015-001', $date('2015-05-10'), 1, '50.00']) // 'Leroy', 'Jenkins'
                ->add([5, 3, '2015-002', $date('2015-05-01'), 1, '215.00']) // 'Doctor', 'Who'
                ->add([6, 4, '2015-003', $date('2015-05-05'), 1, '55.00']) // 'Spider', 'Pig'
                // Overdue, with relative dates
                ->add([7, 5, '2019-001', $date('-7 days'), 3, '80.00']) // 'Nicklas', 'Cake'
                ->add([8, 5, '2020-019', $date('+15 days'), 3, '70.00']) // 'Nicklas', 'Cake'
                ->add([9, 5, '2021-005', $date('+1 year'), 3, '70.00']) // 'Nicklas', 'Cake'
            ->end(),

            SchemaRecord::create(
                'invoice_details',
                [
                    'id' => 'integer',
                    'invoice' => 'integer',
                    'label' => 'string',
                    'quantity' => 'integer',
                    'price' => 'decimal',
                    'total' => 'decimal',
                ]
            )
            ->records()
                // invoice 1
                ->add([1, 1, 'Electric Guitar', 1, '200.00', '100.00'])
                // invoice 2
                ->add([2, 2, 'Sword', 1, '15.00', '15.00'])
                ->add([3, 2, 'Shield', 1, '20.00', '20.00'])
                ->add([4, 2, 'Armor', 1, '55.00', '55.00'])
                // invoice 3
                ->add([5, 3, 'Sword', 1, '10.00', '10.00'])
                // invoice 4
                ->add([6, 4, 'Armor repair kit', 2, '50.00', '100.00'])
                // invoice 5
                ->add([7, 5, 'TARDIS Chameleon circuit', 1, '15.00', '15.00'])
                ->add([8, 5, 'Sonic Screwdriver', 10, '20.00', '200.00'])
                // invoice 6
                ->add([9, 6, 'Web shooter', 1, '10.00', '10.00'])
                ->add([10, 6, 'Cape', 1, '10.00', '10.00'])
                ->add([11, 6, 'Cape repair manual', 1, '10.00', '10.00'])
                ->add([12, 6, 'Hoof polish', 3, '10.00', '30.00'])
                // invoice 7
                ->add([13, 7, 'Bee repellent', 1, '666.00', '666.00'])
                ->add([14, 7, 'Badge', 1, '10.00', '10.00'])
                ->add([15, 8, 'Wicker Beads', 5, '3.00', '15.00'])
                ->add([16, 8, 'A Pair of Cloths', 1, '60.00', '60.00'])
            ->end(),
        ];
    }

    protected function getQuery(): QueryBuilder
    {
        return $this->conn->createQueryBuilder()
            ->select('*', 'i.id AS id')
            ->from('invoice', 'i')
            ->join('i', 'customer', 'c', 'i.customer = c.id')
            ->leftJoin('i', 'invoice_details', 'ir', 'ir.invoice = i.id')
        ;
    }

    protected function configureConditionGenerator(ConditionGenerator $conditionGenerator): void
    {
        // Customer (by invoice relation)
        $conditionGenerator->setField('customer-first-name', 'first_name', 'c', 'string');
        $conditionGenerator->setField('customer-last-name', 'last_name', 'c', 'string');
        $conditionGenerator->setField('customer-birthday', 'birthday', 'c', 'date_immutable');
        $conditionGenerator->setField('customer-regdate', 'regdate', 'c', 'date_immutable');

        $conditionGenerator->setField('customer-name#first_name', 'first_name', 'c', 'string');
        $conditionGenerator->setField('customer-name#last_name', 'last_name', 'c', 'string');

        // Invoice
        $conditionGenerator->setField('id', 'id', 'i', 'integer');
        $conditionGenerator->setField('customer', 'customer', 'i', 'integer');
        $conditionGenerator->setField('label', 'label', 'i', 'string');
        $conditionGenerator->setField('pub-date', 'pub_date', 'i', 'date_immutable');
        $conditionGenerator->setField('status', 'status', 'i', 'integer');
        $conditionGenerator->setField('total', 'price_total', 'i', 'decimal');

        // Invoice Details
        $conditionGenerator->setField('row-label', 'label', 'ir', 'string');
        $conditionGenerator->setField('row-quantity', 'quantity', 'ir', 'integer');
        $conditionGenerator->setField('row-price', 'price', 'ir', 'decimal');
        $conditionGenerator->setField('row-total', 'total', 'ir', 'decimal');
    }

    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder();

        // Customer (by invoice relation)
        $fieldSet->add('customer-first-name', TextType::class);
        $fieldSet->add('customer-last-name', TextType::class);
        $fieldSet->add('customer-name', TextType::class);
        $fieldSet->add('customer-birthday', BirthdayType::class, ['pattern' => 'yyyy-MM-dd']);
        $fieldSet->add('customer-regdate', DateType::class, ['pattern' => 'yyyy-MM-dd']);

        // Invoice
        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('customer', IntegerType::class);
        $fieldSet->add('label', TextType::class);
        $fieldSet->add('pub-date', DateTimeType::class, ['pattern' => 'yyyy-MM-dd', 'allow_relative' => true]);
        $fieldSet->add('status', ChoiceType::class, ['choices' => ['concept' => 0, 'published' => 1, 'paid' => 2, 'overdue' => 3]]);
        $fieldSet->add('total', MoneyType::class);

        // Invoice Details
        $fieldSet->add('row-label', TextType::class);
        $fieldSet->add('row-quantity', IntegerType::class);
        $fieldSet->add('row-price', MoneyType::class);
        $fieldSet->add('row-total', MoneyType::class);

        return $build ? $fieldSet->getFieldSet('invoice') : $fieldSet;
    }

    /** @test */
    public function it_finds_with_id(): void
    {
        $this->makeTest('id: 1, 5;', [1, 5]);
    }

    /** @test */
    public function it_finds_with_combined_field(): void
    {
        $this->makeTest('customer-name: Pang, Leroy;', [1, 2, 3, 4]);
    }

    /** @test */
    public function it_finds_with_range_and_excluding(): void
    {
        $this->makeTest('id: 1~7[, !2;', [1, 3, 4, 5, 6]);
    }

    /** @test */
    public function it_finds_by_customer_birthday(): void
    {
        $this->makeTest('customer-birthday: "2000-05-15";', range(2, 4));
    }

    /** @test */
    public function it_finds_by_customer_birthdays(): void
    {
        $this->makeTest('customer-birthday: "2000-05-15", "1980-06-10";', [2, 3, 4]);
    }

    /** @test */
    public function it_finds_by_date_relative(): void
    {
        $this->makeTest('pub-date: >"7 days";', [8, 9]);
        $this->makeTest('pub-date: >"16 days";', [9]);
        $this->makeTest('pub-date: "14 days" ~ "6 months";', [8]);
        $this->makeTest('pub-date: >2015-05-10, <"-7 days";', [7]);
        $this->makeTest('pub-date: >2015-05-10, <"-8 days";', []);
        $this->makeTest('pub-date: >2015-05-10, >"-1 years", <"6 months";', [7, 8]);
    }

    /** @test */
    public function it_finds_with_or_group(): void
    {
        $this->makeTest('* customer-birthday: "1980-11-20"; pub-date: "2015-05-01";', [1, 5]);
    }

    /** @test */
    public function it_finds_pub_date_limited_by_price(): void
    {
        $this->makeTest('pub-date: "2015-05-10"; total: "50.00"', [4]);
    }

    /** @test */
    public function it_finds_by_customer_and_status(): void
    {
        $this->makeTest('customer: 2; status: concept;', [3]);
    }

    /** @test */
    public function it_finds_by_customer_and_status_and_total(): void
    {
        $this->makeTest('customer: 2; status: paid; total: "90.00";', [2]);
    }

    /** @test */
    public function it_finds_by_customer_and_status_and_total_with_primary_cond(): void
    {
        $this->makeTestWithPrimaryCond('customer: 2;', 'customer: 3;', []);
        $this->makeTestWithPrimaryCond('customer: 2;', 'status: paid; customer: 3;', []);
        $this->makeTestWithPrimaryCond('customer: 2;', 'status: paid;', [2]);
    }

    /** @test */
    public function it_finds_by_customer_and_status_or_price(): void
    {
        $this->makeTest('customer: 2; *(status: paid; total: "50.00";)', [2, 4]);
    }

    /** @test */
    public function it_finds_by_status_and_label_or_quantity_limited_by_price(): void
    {
        // Note there is no row with quantity 5, which is resolved as its in an OR'ed group
        $this->makeTest('status: published; *(row-quantity: 5; row-label: ~*"repair"; (row-price: "50.00"));', [4]);
    }

    /** @test */
    public function it_finds_by_excluding_equals_pattern(): void
    {
        $this->makeTest('row-label: ~=Armor, ~=sword;', [2]); // Invoice 3 doesn't match as "sword" is lowercase
        $this->makeTest('row-price: "15.00"; row-label: ~!=Sword;', [5]);

        // Lowercase
        $this->makeTest('row-label: ~=Armor, ~i=sword;', [2, 3]);
    }

    private function makeTest($input, array $expectedRows): void
    {
        $config = new ProcessorConfig($this->getFieldSet());

        try {
            $condition = $this->inputProcessor->process($config, $input);
            $this->assertRecordsAreFound($condition, $expectedRows);
        } catch (\Exception $e) {
            self::detectSystemException($e);

            if (\function_exists('dump')) {
                dump($e);
            } else {
                echo 'Please install symfony/var-dumper as dev-requirement to get a readable structure.' . \PHP_EOL;

                // Don't use var-dump or print-r as this crashes php...
                echo $e::class . '::' . $e;
            }

            self::fail('Condition contains errors.');
        }
    }

    private function makeTestWithPrimaryCond($primaryCondition, $input, array $expectedRows): void
    {
        $config = new ProcessorConfig($this->getFieldSet());

        try {
            $condition = $this->inputProcessor->process($config, $input);
            $condition->setPrimaryCondition(new SearchPrimaryCondition($this->inputProcessor->process($config, $primaryCondition)->getValuesGroup()));
            $this->assertRecordsAreFound($condition, $expectedRows);
        } catch (\Exception $e) {
            self::detectSystemException($e);

            if (\function_exists('dump')) {
                dump($e);
            } else {
                echo 'Please install symfony/var-dumper as dev-requirement to get a readable structure.' . \PHP_EOL;

                // Don't use var-dump or print-r as this crashes php...
                echo $e::class . '::' . $e;
            }

            self::fail('Condition contains errors.');
        }
    }
}
