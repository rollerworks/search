<?php

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
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\FieldAliasResolver\NoopAliasResolver;
use Rollerworks\Component\Search\Input\FilterQueryInput;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Ensures the expected results are actually found.
 *
 * Uses the FilterQuery input-processor for a readable condition
 * and ensures the input values are properly formatted.
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
 */
final class WhereBuilderResultsTest extends FunctionalDbalTestCase
{
    /**
     * @var FilterQueryInput
     */
    private $inputProcessor;

    protected function setUp()
    {
        parent::setUp();

        $this->inputProcessor = new FilterQueryInput(new NoopAliasResolver());
    }

    protected function setUpDbSchema(DbSchema $schema)
    {
        $customerTable = $schema->createTable('customer');
        $customerTable->addOption('collate', 'utf8_bin');
        $customerTable->addColumn('id', 'integer');
        $customerTable->addColumn('first_name', 'string');
        $customerTable->addColumn('last_name', 'string');
        $customerTable->addColumn('birthday', 'date');
        $customerTable->addColumn('regdate', 'date');
        $customerTable->setPrimaryKey(array('id'));

        $invoiceTable = $schema->createTable('invoice');
        $invoiceTable->addOption('collate', 'utf8_bin');
        $invoiceTable->addColumn('id', 'integer');
        $invoiceTable->addColumn('customer', 'integer');
        $invoiceTable->addColumn('label', 'string', array('notnull' => false));
        $invoiceTable->addColumn('pub_date', 'date', array('notnull' => false));
        $invoiceTable->addColumn('status', 'integer');
        $invoiceTable->addColumn('price_total', 'decimal', array('scale' => 2));
        $invoiceTable->setPrimaryKey(array('id'));
        $invoiceTable->addUniqueIndex(array('label'));

        $invoiceDetailsTable = $schema->createTable('invoice_details');
        $invoiceDetailsTable->addOption('collate', 'utf8_bin');
        $invoiceDetailsTable->addColumn('id', 'integer');
        $invoiceDetailsTable->addColumn('invoice', 'integer');
        $invoiceDetailsTable->addColumn('label', 'string');
        $invoiceDetailsTable->addColumn('quantity', 'integer');
        $invoiceDetailsTable->addColumn('price', 'decimal', array('scale' => 2));
        $invoiceDetailsTable->addColumn('total', 'decimal', array('scale' => 2));
        $invoiceDetailsTable->setPrimaryKey(array('id'));
    }

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        $date = function ($input) {
            return new \DateTime($input, new \DateTimeZone('UTC'));
        };

        return array(
            SchemaRecord::create(
                'customer',
                array(
                    'id' => 'integer',
                    'first_name' => 'string',
                    'last_name' => 'string',
                    'birthday' => 'date',
                    'regdate' => 'date',
                )
            )
            ->records()
                ->add(array(1, 'Peter', 'Pang', $date('1980-11-20'), $date('2005-11-20')))
                ->add(array(2, 'Leroy', 'Jenkins', $date('2000-05-15'), $date('2005-05-20')))
                ->add(array(3, 'Doctor', 'Who', $date('2005-12-10'), $date('2005-02-20')))
                ->add(array(4, 'Spider', 'Pig', $date('2012-06-10'), $date('2012-07-20')))
            ->end(),

            // Two invoices are paid, one is a concept and three are unpaid
            SchemaRecord::create(
                'invoice',
                array(
                    'id' => 'integer',
                    'customer' => 'integer',
                    'label' => 'string',
                    'pub_date' => 'date',
                    'status' => 'integer',
                    'price_total' => 'decimal',
                )
            )
            ->records()
                ->add(array(1, 1, '2010-001', $date('2010-05-10'), 2, '100.00')) // 'Peter', 'Pang'
                ->add(array(2, 2, '2010-002', $date('2010-05-10'), 2, '90.00')) // 'Leroy', 'Jenkins'
                ->add(array(3, 2, null, null, 0, '10.00')) // concept - 'Leroy', 'Jenkins'
                // unpaid //
                ->add(array(4, 2, '2015-001', $date('2015-05-10'), 1, '50.00')) // 'Leroy', 'Jenkins'
                ->add(array(5, 3, '2015-002', $date('2015-05-01'), 1, '215.00')) // 'Doctor', 'Who'
                ->add(array(6, 4, '2015-003', $date('2015-05-05'), 1, '55.00')) // 'Spider', 'Pig'
            ->end(),

            SchemaRecord::create(
                'invoice_details',
                array(
                    'id' => 'integer',
                    'invoice' => 'integer',
                    'label' => 'string',
                    'quantity' => 'integer',
                    'price' => 'decimal',
                    'total' => 'decimal',
                )
            )
            ->records()
                // invoice 1
                ->add(array(1, 1, 'Electric Guitar', 1, '200.00', '100.00'))
                // invoice 2
                ->add(array(2, 2, 'Sword', 1, '15.00', '15.00'))
                ->add(array(3, 2, 'Shield', 1, '20.00', '20.00'))
                ->add(array(4, 2, 'Armor', 1, '55.00', '55.00'))
                // invoice 3
                ->add(array(5, 3, 'Sword', 1, '10.00', '10.00'))
                // invoice 4
                ->add(array(6, 4, 'Armor repair kit', 2, '50.00', '100.00'))
                // invoice 5
                ->add(array(7, 5, 'TARDIS Chameleon circuit', 1, '15.00', '15.00'))
                ->add(array(8, 5, 'Sonic Screwdriver', 10, '20.00', '200.00'))
                // invoice 6
                ->add(array(9, 6, 'Web shooter', 1, '10.00', '10.00'))
                ->add(array(10, 6, 'Cape', 1, '10.00', '10.00'))
                ->add(array(11, 6, 'Cape repair manual', 1, '10.00', '10.00'))
                ->add(array(12, 6, 'Hoof polish', 3, '10.00', '30.00'))
            ->end(),
        );
    }

    protected function getQuery()
    {
        return <<<SQL
SELECT
    *, i.id AS id
FROM
    invoice AS i
JOIN
    customer AS c ON i.customer = c.id
LEFT JOIN
    invoice_details AS ir ON ir.invoice = i.id
WHERE

SQL;
    }

    protected function configureWhereBuilder(WhereBuilder $whereBuilder)
    {
        // Customer (by invoice relation)
        $whereBuilder->setField('customer-first-name', 'first_name', 'string', 'c');
        $whereBuilder->setField('customer-last-name', 'last_name', 'string', 'c');
        $whereBuilder->setField('customer-birthday', 'birthday', 'date', 'c');
        $whereBuilder->setField('customer-regdate', 'regdate', 'date', 'c');

        // Invoice
        $whereBuilder->setField('id', 'id', 'integer', 'i');
        $whereBuilder->setField('customer', 'customer', 'integer', 'i');
        $whereBuilder->setField('label', 'label', 'string', 'i');
        $whereBuilder->setField('pub-date', 'pub_date', 'date', 'i');
        $whereBuilder->setField('status', 'status', 'integer', 'i');
        $whereBuilder->setField('total', 'price_total', 'decimal', 'i');

        // Invoice Details
        $whereBuilder->setField('row-label', 'label', 'string', 'ir');
        $whereBuilder->setField('row-quantity', 'quantity', 'integer', 'ir');
        $whereBuilder->setField('row-price', 'price', 'decimal', 'ir');
        $whereBuilder->setField('row-total', 'total', 'decimal', 'ir');
    }

    protected function getFieldSet($build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder('invoice');

        // Customer (by invoice relation)
        $fieldSet->add('customer-first-name', 'text');
        $fieldSet->add('customer-last-name', 'text');
        $fieldSet->add('customer-birthday', 'birthday', array('format' => 'yyyy-MM-dd'));
        $fieldSet->add('customer-regdate', 'date', array('format' => 'yyyy-MM-dd'));

        // Invoice
        $fieldSet->add('id', 'integer');
        $fieldSet->add('customer', 'integer');
        $fieldSet->add('label', 'text');
        $fieldSet->add('pub-date', 'date', array('format' => 'yyyy-MM-dd'));
        $fieldSet->add('status', 'choice', array('label_as_value' => true, 'choices' => array(0 => 'concept', 1 => 'published', 2 => 'paid')));
        $fieldSet->add('total', 'money');

        // Invoice Details
        $fieldSet->add('row-label', 'text');
        $fieldSet->add('row-quantity', 'integer');
        $fieldSet->add('row-price', 'money');
        $fieldSet->add('row-total', 'money');

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    /**
     * @test
     */
    public function it_finds_with_id()
    {
        $this->makeTest('id: 1, 5;', array(1, 5));
    }

    /**
     * @test
     */
    public function it_finds_with_range_and_excluding()
    {
        $this->makeTest('id: 1-7, !2;', array(1, 3, 4, 5, 6));
    }

    /**
     * @test
     */
    public function it_finds_by_customer_birthday()
    {
        $this->makeTest('customer-birthday: "2000-05-15";', range(2, 4));
    }

    /**
     * @test
     */
    public function it_finds_by_customer_birthdays()
    {
        $this->makeTest('customer-birthday: "2000-05-15", "1980-06-10";', array(2, 3, 4));
    }

    /**
     * @test
     */
    public function it_finds_with_or_group()
    {
        $this->makeTest('* customer-birthday: "1980-11-20"; pub-date: "2015-05-01";', array(1, 5));
    }

    /**
     * @test
     */
    public function it_finds_pubDate_limited_by_price()
    {
        $this->makeTest('pub-date: "2015-05-10"; total: "50.00"', array(4));
    }

    /**
     * @test
     */
    public function it_finds_by_customer_and_status()
    {
        $this->makeTest('customer: 2; status: concept;', array(3));
    }

    /**
     * @test
     */
    public function it_finds_by_customer_and_status_and_total()
    {
        $this->makeTest('customer: 2; status: paid; total: "90.00";', array(2));
    }

    /**
     * @test
     */
    public function it_finds_by_customer_and_status_or_price()
    {
        $this->makeTest('customer: 2; *(status: paid; total: "50.00";)', array(2, 4));
    }

    /**
     * @test
     */
    public function it_finds_by_status_and_label_or_quantity_limited_by_price()
    {
        // Note there is no row with quantity 5, which is resolved as its in an OR'ed group
        $this->makeTest('status: published; *(row-quantity: 5; row-label: ~*"repair"; (row-price: "50.00"));', array(4));
    }

    /**
     * @test
     */
    public function it_finds_by_excluding_regex_pattern()
    {
        $this->makeTest('status: published; row-label: ~*"repair", ~!?"Armor";', array(6));
    }

    /**
     * @test
     */
    public function it_finds_by_excluding_equals_pattern()
    {
        $this->makeTest('row-label: ~=Armor, ~=sword;', [2]); // Invoice 3 doesn't match as "sword" is lowercase
        $this->makeTest('row-price: "15.00"; row-label: ~!=Sword;', [5]);

        // Lowercase
        $this->makeTest('row-label: ~=Armor, ~i=sword;', [2, 3]);
    }

    private function makeTest($input, array $expectedRows)
    {
        $config = new ProcessorConfig($this->getFieldSet());

        try {
            $condition = $this->inputProcessor->process($config, $input);
            $this->assertRecordsAreFound($condition, $expectedRows);
        } catch (InvalidSearchConditionException $e) {
            $this->fail(
                $e->getMessage()."\n".
                $this->renderSearchErrors($e->getCondition()->getValuesGroup())
            );
        }
    }

    private function renderSearchErrors(ValuesGroup $group, $nestingLevel = 0)
    {
        if (!$group->hasErrors(true)) {
            return '';
        }

        $fields = $group->getFields();
        $output = '';

        foreach ($fields as $fieldName => $values) {
            $errors = $values->getErrors();

            if ($values->hasErrors()) {
                $output .= str_repeat(' ', $nestingLevel * 2).$fieldName.' has the following errors: '."\n";

                foreach ($errors as $valueError) {
                    $output .= str_repeat(' ', $nestingLevel * 2).' - '.$valueError->getMessage()."\n";
                }
            }

            foreach ($group->getGroups() as $subGroup) {
                $output .= $this->renderSearchErrors($subGroup, ++$nestingLevel)."\n";
            }
        }

        return $output;
    }
}
