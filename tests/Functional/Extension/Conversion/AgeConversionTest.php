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
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional\FunctionalDbalTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;

/**
 * @group functional
 */
final class AgeConversionTest extends FunctionalDbalTestCase
{
    protected function setUpDbSchema(DbSchema $schema)
    {
        $invoiceTable = $schema->createTable('site_user');
        $invoiceTable->addColumn('id', 'integer');
        $invoiceTable->addColumn('birthday', 'date');
    }

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        return [
            SchemaRecord::create('site_user', ['id' => 'integer', 'birthday' => 'date'])
                ->add([1, new \DateTime('2001-01-15', new \DateTimeZone('UTC'))])
                ->add([2, new \DateTime('2001-05-15', new \DateTimeZone('UTC'))])
                ->add([3, new \DateTime('2001-10-15', new \DateTimeZone('UTC'))])
                ->add([4, new \DateTime('-5 years', new \DateTimeZone('UTC'))])
            ->end(),
        ];
    }

    protected function getQuery()
    {
        return 'SELECT id FROM site_user AS u WHERE ';
    }

    protected function configureWhereBuilder(WhereBuilder $whereBuilder)
    {
        $whereBuilder->setField('birthday', 'birthday', 'date', 'u');
    }

    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder();
        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('birthday', BirthdayType::class);

        return $build ? $fieldSet->getFieldSet('user') : $fieldSet;
    }

    public function testWithDate()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('birthday')
                ->addSimpleValue(new \DateTime('2001-01-15', new \DateTimeZone('UTC')))
                ->addSimpleValue(new \DateTime('2001-10-15', new \DateTimeZone('UTC')))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, [1, 3]);
    }

    public function testWithAge()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('birthday')
                ->addSimpleValue(5)
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, [4]);
    }
}
