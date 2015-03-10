<?php

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
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional\FunctionalDbalTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;

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
        $invoiceTable->addColumn('total', 'decimal', array('scale' => 2));
    }

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        return array(
            SchemaRecord::create('product', array('id' => 'integer', 'price' => 'string', 'total' => 'decimal'))
                ->add(array(1, 'EUR 50.00', '80.00'))
                ->add(array(2, 'EUR 30.00', '80.00'))
                // --
                ->add(array(3, 'EUR 66.00', '90.00'))
                ->add(array(4, 'EUR 70.00', '100.00'))
            ->end(),
        );
    }

    protected function getQuery()
    {
        return "SELECT id FROM product AS p WHERE ";
    }

    protected function configureWhereBuilder(WhereBuilder $whereBuilder)
    {
        $whereBuilder->setField('price', 'price', 'string', 'p');
        $whereBuilder->setField('total', 'total', 'decimal', 'p');
    }

    protected function getFieldSet($build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder('product');
        $fieldSet->add('id', 'integer');
        $fieldSet->add('price', 'money');
        $fieldSet->add('total', 'money');

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    public function testWithNumericColumn()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('total')
                ->addSingleValue(new SingleValue(new MoneyValue('EUR', '90.00'), '€ 90.00'))
                ->addSingleValue(new SingleValue(new MoneyValue('EUR', '100.00'), '€ 100.00'))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, array(3, 4));
    }

    public function testWithVarcharColumn()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('price')
                ->addSingleValue(new SingleValue(new MoneyValue('EUR', '50.00'), '€ 50.00'))
                ->addSingleValue(new SingleValue(new MoneyValue('EUR', '30.00'), '€ 30.00'))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, array(1, 2));
    }

    public function testWithVarcharColumnAndRange()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('price')
                ->addRange(new Range(new MoneyValue('EUR', '30.00'), new MoneyValue('EUR', '50.00'), true, true, '€ 30.00', '€ 50.00'))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, array(1, 2));
    }
}
