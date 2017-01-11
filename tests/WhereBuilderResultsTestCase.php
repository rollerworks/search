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

use Rollerworks\Component\Search\Doctrine\Orm\AbstractWhereBuilder;
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
 * * invoices
 * * invoice_rows
 * * customers
 *
 * For simplicity this example doesn't do tax calculation.
 *
 * Complex structure tests are not done here as these tests are more about
 * "doesn't error".
 */
class WhereBuilderResultsTestCase extends OrmTestCase
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

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        $date = function ($input) {
            return new \DateTime($input, new \DateTimeZone('UTC'));
        };

        return [
            SchemaRecord::create(
                'customers',
                [
                    'id' => 'integer',
                    'first_name' => 'string',
                    'last_name' => 'string',
                    'birthday' => 'date',
                ]
            )
            ->records()
                ->add([1, 'Peter', 'Pang', $date('1980-11-20')])
                ->add([2, 'Leroy', 'Jenkins', $date('2000-05-15')])
                ->add([3, 'Doctor', 'Who', $date('2005-12-10')])
                ->add([4, 'Spider', 'Pig', $date('2012-06-10')])
            ->end(),

            // Two invoices are paid, one is a concept and three are unpaid
            SchemaRecord::create(
                'invoices',
                [
                    'invoice_id' => 'integer',
                    'customer' => 'integer',
                    'label' => 'string',
                    'pubdate' => 'date',
                    'status' => 'integer',
                ]
            )
            ->records()
                ->add([1, 1, '2010-001', $date('2010-05-10'), 2]) // 'Peter', 'Pang'
                ->add([2, 2, '2010-002', $date('2010-05-10'), 2]) // 'Leroy', 'Jenkins'
                ->add([3, 2, null, null, 0]) // concept - 'Leroy', 'Jenkins'
                // unpaid //
                ->add([4, 2, '2015-001', $date('2015-05-10'), 1]) // 'Leroy', 'Jenkins'
                ->add([5, 3, '2015-002', $date('2015-05-01'), 1]) // 'Doctor', 'Who'
                ->add([6, 4, '2015-003', $date('2015-05-05'), 1]) // 'Spider', 'Pig'
            ->end(),

            SchemaRecord::create(
                'invoice_rows',
                [
                    'id' => 'integer',
                    'invoice' => 'integer',
                    'label' => 'string',
                    'price' => 'decimal',
                ]
            )
            ->records()
                // invoice 1
                ->add([1, 1, 'Electric Guitar', '200.00'])
                // invoice 2
                ->add([2, 2, 'Sword', '15.00'])
                ->add([3, 2, 'Shield', '20.00'])
                ->add([4, 2, 'Armor', '55.00'])
                // invoice 3
                ->add([5, 3, 'Sword', '10.00'])
                // invoice 4
                ->add([6, 4, 'Armor repair kit', '50.00'])
                // invoice 5
                ->add([7, 5, 'TARDIS Chameleon circuit', '15.00'])
                ->add([8, 5, 'Sonic Screwdriver', '20.00'])
                // invoice 6
                ->add([9, 6, 'Web shooter', '10.00'])
                ->add([10, 6, 'Cape', '10.00'])
                ->add([11, 6, 'Cape repair manual', '10.00'])
                ->add([12, 6, 'Hoof polish', '10.00'])
            ->end(),
        ];
    }

    protected function configureWhereBuilder(AbstractWhereBuilder $whereBuilder)
    {
        $whereBuilder->setEntityMapping(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice',
            'I'
        );
        $whereBuilder->setEntityMapping(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer',
            'C'
        );
        $whereBuilder->setEntityMapping(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoiceRow',
            'R'
        );
        $whereBuilder->setField('credit_parent', 'IP', null, 'id');
        $whereBuilder->setCombinedField('customer-name', [
            ['property' => 'firstName', 'type' => 'string', 'alias' => 'C'],
            ['property' => 'lastName', 'alias' => 'C'],
        ]);
    }

    /**
     * @test
     */
    public function it_finds_with_id()
    {
        $this->makeTest('id: 1, 5;', [1, 5]);
    }

    /**
     * @test
     */
    public function it_finds_with_combined_field()
    {
        $this->makeTest('customer-name: Pang, Leroy;', [1, 2, 3, 4]);
    }

    /**
     * @test
     */
    public function it_finds_with_range_and_excluding()
    {
        $this->makeTest('id: 1-7, !2;', [1, 3, 4, 5, 6]);
    }

    /**
     * @test
     */
    public function it_finds_by_customer_birthday()
    {
        $this->makeTest('customer_birthday: "2000-05-15";', range(2, 4));
    }

    /**
     * @test
     */
    public function it_finds_by_customer_birthdays()
    {
        $this->makeTest('customer_birthday: "2000-05-15", "1980-06-10";', [2, 3, 4]);
    }

    /**
     * @test
     */
    public function it_finds_by_excluding_regex_pattern()
    {
        $this->makeTest('status: 1; row_label: ~*"repair", ~!?"Armor";', [6]);
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
