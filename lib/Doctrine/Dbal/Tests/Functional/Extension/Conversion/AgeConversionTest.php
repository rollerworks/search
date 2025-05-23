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

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema as DbSchema;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional\FunctionalDbalTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;

/**
 * @group functional
 *
 * @internal
 */
final class AgeConversionTest extends FunctionalDbalTestCase
{
    protected function setUpDbSchema(DbSchema $schema): void
    {
        $invoiceTable = $schema->createTable('site_user');
        $invoiceTable->addColumn('id', 'integer');
        $invoiceTable->addColumn('birthday', 'date_immutable');
    }

    /**
     * @return SchemaRecord[]
     */
    protected function getDbRecords()
    {
        return [
            SchemaRecord::create('site_user', ['id' => 'integer', 'birthday' => 'date_immutable'])
                ->add([1, new \DateTimeImmutable('2001-01-15', new \DateTimeZone('UTC'))])
                ->add([2, new \DateTimeImmutable('2001-05-15', new \DateTimeZone('UTC'))])
                ->add([3, new \DateTimeImmutable('2001-10-15', new \DateTimeZone('UTC'))])
                ->add([4, new \DateTimeImmutable('-5 years', new \DateTimeZone('UTC'))])
            ->end(),
        ];
    }

    protected function getQuery(): QueryBuilder
    {
        return $this->conn->createQueryBuilder()
            ->select('id')
            ->from('site_user', 'u')
        ;
    }

    protected function configureConditionGenerator(ConditionGenerator $conditionGenerator): void
    {
        $conditionGenerator->setField('birthday', 'birthday', 'u', 'date_immutable');
    }

    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder();
        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('birthday', BirthdayType::class);

        return $build ? $fieldSet->getFieldSet('user') : $fieldSet;
    }

    /** @test */
    public function with_date(): void
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('birthday')
                ->addSimpleValue(new \DateTimeImmutable('2001-01-15', new \DateTimeZone('UTC')))
                ->addSimpleValue(new \DateTimeImmutable('2001-10-15', new \DateTimeZone('UTC')))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, [1, 3]);
    }

    /** @test */
    public function with_age(): void
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
