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
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\ChildCountType;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional\FunctionalDbalTestCase;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\SchemaRecord;

/**
 * @group functional
 */
final class ChildCountTypeTest extends FunctionalDbalTestCase
{
    protected function setUpDbSchema(DbSchema $schema)
    {
        $userTable = $schema->createTable('site_user');
        $userTable->addColumn('id', 'integer');
        $userTable->addColumn('birthday', 'date');

        $userTable = $schema->createTable('user_contact');
        $userTable->addColumn('id', 'integer');
        $userTable->addColumn('user_id', 'integer');
        $userTable->addColumn('name', 'string');
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
            SchemaRecord::create('user_contact', ['id' => 'integer', 'user_id' => 'integer', 'name' => 'string'])
                ->add([1, 1, 'Doctor'])
                ->add([2, 1, 'Leroy'])
                ->add([3, 2, 'Peter'])
            ->end(),
        ];
    }

    protected function getQuery()
    {
        return 'SELECT id FROM site_user AS u WHERE ';
    }

    protected function configureWhereBuilder(WhereBuilder $whereBuilder)
    {
        $whereBuilder->setField('contact_count', 'id', 'u', 'integer');
    }

    protected function getFieldSet(bool $build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder();
        $fieldSet->add('id', IntegerType::class);
        $fieldSet->add('contact_count', ChildCountType::class, [
            'table_name' => 'user_contact',
            'table_column' => 'user_id',
        ]);

        return $build ? $fieldSet->getFieldSet('user') : $fieldSet;
    }

    public function testMatchesCount()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('contact_count')
                ->addSimpleValue(2)
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, [1]);
    }
}
