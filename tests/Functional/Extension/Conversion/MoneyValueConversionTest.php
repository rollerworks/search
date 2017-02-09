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

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional\Extension\Conversion;

use Doctrine\DBAL\Schema\Schema as DbSchema;
use Money\Money;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\MoneyType;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional\FunctionalDbalTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;
use Rollerworks\Component\Search\Value\Range;

/**
 * @group functional
 */
final class MoneyValueConversionTest extends FunctionalDbalTestCase
{
    protected function setUpDbSchema(DbSchema $schema)
    {
        $invoiceTable = $schema->createTable('product');
        $invoiceTable->addColumn('id', 'integer');
        $invoiceTable->addColumn('price', 'string');
        $invoiceTable->addColumn('total', 'decimal', ['scale' => 2]);
    }

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        return [
            SchemaRecord::create('product', ['id' => 'integer', 'price' => 'string', 'total' => 'decimal'])
                ->add([1, 'EUR 50.00', '80.00'])
                ->add([2, 'EUR 30.00', '80.00'])
                // --
                ->add([3, 'EUR 66.00', '90.00'])
                ->add([4, 'EUR 70.00', '100.00'])
            ->end(),
        ];
    }

    protected function getQuery()
    {
        return 'SELECT id FROM product AS p WHERE ';
    }

    protected function configureWhereBuilder(ConditionGenerator $whereBuilder)
    {
        $whereBuilder->setField('price', 'price', 'p', 'string');
        $whereBuilder->setField('total', 'total', 'p', 'decimal');
    }

    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder();
        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('price', MoneyType::class);
        $fieldSet->add('total', MoneyType::class);

        return $build ? $fieldSet->getFieldSet('product') : $fieldSet;
    }

    public function testWithNumericColumn()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('total')
                ->addSimpleValue(new MoneyValue(Money::EUR('9000')))
                ->addSimpleValue(new MoneyValue(Money::EUR('10000')))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, [3, 4]);
    }

    public function testWithVarcharColumn()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('price')
                ->addSimpleValue(new MoneyValue(Money::EUR('5000')))
                ->addSimpleValue(new MoneyValue(Money::EUR('3000')))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, [1, 2]);
    }

    public function testWithVarcharColumnAndRange()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('price')
                ->add(new Range(new MoneyValue(Money::EUR('3000')), new MoneyValue(Money::EUR('5000')), true, true))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, [1, 2]);
    }
}
