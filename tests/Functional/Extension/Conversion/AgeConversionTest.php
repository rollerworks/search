<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional\Extension\Conversion;

use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Functional\FunctionalDbalTestCase;
use Rollerworks\Component\Search\Value\SingleValue;

/**
 * @group functional
 */
final class AgeConversionTest extends FunctionalDbalTestCase
{
    protected function setUp()
    {
        $this->resetSharedConn();
        parent::setUp();

        $schema = new \Doctrine\DBAL\Schema\Schema();

        $invoiceTable = $schema->createTable('site_user');
        $invoiceTable->addColumn('id', 'integer');
        $invoiceTable->addColumn('birthday', 'date');

        $platform = $this->conn->getDatabasePlatform();
        $queries = $schema->toSql($platform);

        foreach ($queries as $query) {
            $this->conn->exec($query);
        }

        $this->conn->insert(
            'site_user',
            array('id' => 1, 'birthday' => new \DateTime('2001-01-15', new \DateTimeZone('UTC'))),
            array(
                'id' => 'integer', 'birthday' => 'date'
            )
        );

        $this->conn->insert(
            'site_user',
            array('id' => 2, 'birthday' => new \DateTime('2001-05-15', new \DateTimeZone('UTC'))),
            array(
                'id' => 'integer', 'birthday' => 'date'
            )
        );

        $this->conn->insert(
            'site_user',
            array('id' => 3, 'birthday' => new \DateTime('2001-10-15', new \DateTimeZone('UTC'))),
            array(
                'id' => 'integer', 'birthday' => 'date'
            )
        );

        $this->conn->insert(
            'site_user',
            array('id' => 4, 'birthday' => new \DateTime('-5 years', new \DateTimeZone('UTC'))),
            array(
                'id' => 'integer', 'birthday' => 'date'
            )
        );
    }

    protected function getFieldSet($build = true)
    {
        $fieldSet = $this->getFactory()->createFieldSetBuilder('user');
        $fieldSet->add('id', 'integer');
        $fieldSet->add('birthday', 'birthday');

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    private function getWhereBuilder(SearchCondition $condition, Connection $connection = null)
    {
        $whereBuilder = $this->getDbalFactory()->createWhereBuilder(
            $connection ?: $this->conn,
            $condition
        );

        $whereBuilder->setField('birthday', 'birthday', 'string', 'u'); // don't use date as this breaks the binding

        return $whereBuilder;
    }

    private function assertRecordsAreFound(SearchCondition $condition, array $ids)
    {
        $whereBuilder = $this->getWhereBuilder($condition);
        $whereClause = $whereBuilder->getWhereClause();

        $statement = $this->conn->prepare("SELECT id FROM site_user AS u WHERE ".$whereClause);
        $whereBuilder->bindParameters($statement);

        $statement->execute();

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $rows = array_map(function ($value) { return $value['id']; }, $rows);

        $this->assertEquals($ids, $rows);
    }

    public function testWithDate()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('birthday')
                ->addSingleValue(new SingleValue(new \DateTime('2001-01-15', new \DateTimeZone('UTC')), '2001-01-15'))
                ->addSingleValue(new SingleValue(new \DateTime('2001-10-15', new \DateTimeZone('UTC')), '2001-10-15'))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, array(1, 3));
    }

    public function testWithAge()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('birthday')
                ->addSingleValue(new SingleValue(5))
            ->end()
            ->getSearchCondition()
        ;

        $this->assertRecordsAreFound($condition, array(4));
    }
}
