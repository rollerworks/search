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

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * NativeWhereBuilderTest.
 *
 * This doesn't do extensive query tests as this handled by the QueryPlatform,
 * and is already tested in DBAL package.
 */
class NativeWhereBuilderTest extends OrmTestCase
{
    private function getWhereBuilder(SearchCondition $condition)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice',
            'I'
        );

        $rsm->addJoinedEntityFromClassMetadata(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer',
            'C',
            'I',
            'customer',
            ['id' => 'customer_id']
        );

        $querySmt = $this->em->createNativeQuery(
            'SELECT I FROM Invoice I JOIN customer AS C ON I.customer = C.id',
            $rsm
        );

        $whereBuilder = $this->getOrmFactory()->createWhereBuilder($querySmt, $condition);
        $whereBuilder->setEntityMapping('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice', 'I');
        $whereBuilder->setEntityMapping('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer', 'C');

        return $whereBuilder;
    }

    public function testSimpleQuery()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('((C.id IN(2, 5)))', $whereBuilder->getWhereClause());
    }

    public function testSimpleQueryWithJoinReference()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('credit_parent')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('((I.invoice_id IN(2, 5)))', $whereBuilder->getWhereClause());
    }

    public function testEmptyResult()
    {
        $condition = new SearchCondition($this->getFieldSet(), new ValuesGroup());
        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('', $whereBuilder->getWhereClause());
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param string $expectWhereCase
     * @param array  $options
     */
    public function testFieldConversion($expectWhereCase, array $options = [])
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);
        $test = $this;

        $converter = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlField')
            ->will($this->returnCallback(function ($column, array $options) use ($test, $options) {
                $test->assertEquals($options, $options);

                return "CAST($column AS customer_type)";
            }))
        ;

        $whereBuilder->setConverter('customer', $converter);

        $this->assertEquals($expectWhereCase, $whereBuilder->getWhereClause());
    }

    public function testSqlValueConversion()
    {
        $fieldSet = $this->getFieldSet();
        $condition = SearchConditionBuilder::create($fieldSet)
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getSearchCondition();

        $options = $fieldSet->get('customer')->getOptions();
        $whereBuilder = $this->getWhereBuilder($condition);
        $test = $this;

        $converter = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlValue')
            ->will($this->returnCallback(function ($input, array $passedOptions) use ($test, $options) {
                $test->assertEquals($options, $passedOptions);

                return "get_customer_type($input)";
            }))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('requiresBaseConversion')
            ->will($this->returnValue(false))
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->will($this->returnArgument(0))
        ;

        $whereBuilder->setConverter('customer', $converter);

        $this->assertEquals(
            "((C.id = get_customer_type(2)))",
            $whereBuilder->getWhereClause()
        );
    }

    public function testConversionStrategy()
    {
        $date = new \DateTime('2001-01-15', new \DateTimeZone('UTC'));

        $fieldSet = $this->getFieldSet();
        $condition = SearchConditionBuilder::create($fieldSet)
            ->field('customer_birthday')
                ->addSingleValue(new SingleValue(18))
                ->addSingleValue(new SingleValue($date, '2001-01-15'))
            ->end()
        ->getSearchCondition();

        $options = $fieldSet->get('customer_birthday')->getOptions();
        $whereBuilder = $this->getWhereBuilder($condition);
        $test = $this;

        $converter = $this->getMock('Rollerworks\Component\Search\Tests\Doctrine\Dbal\SqlConversionStrategyInterface');
        $converter
            ->expects($this->atLeastOnce())
            ->method('getConversionStrategy')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        if (!$value instanceof \DateTime && !is_int($value)) {
                            throw new \InvalidArgumentException('Only integer/string and DateTime are accepted.');
                        }

                        if ($value instanceof \DateTime) {
                            return 2;
                        }

                        return 1;
                    }
                )
            )
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlField')
            ->will(
                $this->returnCallback(
                    function ($column, array $passedOptions, ConversionHints $hints) use ($test, $options) {
                        $test->assertEquals($options, $passedOptions);

                        if (2 === $hints->conversionStrategy) {
                            return "search_conversion_age($column)";
                        }

                        $test->assertEquals(1, $hints->conversionStrategy);

                        return $column;
                    }
                )
            )
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertSqlValue')
            ->will(
                $this->returnCallback(
                    function ($input, array $passedOptions, ConversionHints $hints) use ($test, $options) {
                        $test->assertEquals($options, $passedOptions);

                        if (2 === $hints->conversionStrategy) {
                            return "CAST(".$hints->connection->quote($input)." AS DATE)";
                        }

                        $test->assertEquals(1, $hints->conversionStrategy);

                        return $input;
                    }
                )
            )
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('convertValue')
            ->will(
                $this->returnCallback(
                    function ($input, array $passedOptions, ConversionHints $hints) use ($test, $options) {
                        $test->assertEquals($options, $passedOptions);

                        if ($input instanceof \DateTime) {
                            $test->assertEquals(2, $hints->conversionStrategy);
                        } else {
                            $test->assertEquals(1, $hints->conversionStrategy);
                        }

                        if ($input instanceof \DateTime) {
                            $input = $input->format('Y-m-d');
                        }

                        return $input;
                    }
                )
            )
        ;

        $converter
            ->expects($this->atLeastOnce())
            ->method('requiresBaseConversion')
            ->will($this->returnValue(false))
        ;

        $whereBuilder->setConverter('customer_birthday', $converter);

        $this->assertEquals(
            "(((C.birthday = 18 OR search_conversion_age(C.birthday) = CAST('2001-01-15' AS DATE))))",
            $whereBuilder->getWhereClause()
        );
    }

    public static function provideFieldConversionTests()
    {
        return [
            [
                "((CAST(C.id AS customer_type) IN(2)))",
            ],
            [
                "((CAST(C.id AS customer_type) IN(2)))",
                ['active' => true],
            ],
        ];
    }

    public function testUpdateQuery()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $whereCase = $whereBuilder->getWhereClause();
        $whereBuilder->updateQuery();

        $this->assertEquals('((C.id IN(2)))', $whereCase);
        $this->assertEquals(
            "SELECT I FROM Invoice I JOIN customer AS C ON I.customer = C.id WHERE ((C.id IN(2)))",
            $whereBuilder->getQuery()->getSQL()
        );
    }

    public function testUpdateQueryWithNoResult()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();
        $whereBuilder = $this->getWhereBuilder($condition);

        $whereCase = $whereBuilder->getWhereClause();
        $whereBuilder->updateQuery();

        $this->assertEquals('', $whereCase);
        $this->assertEquals(
            'SELECT I FROM Invoice I JOIN customer AS C ON I.customer = C.id',
            $whereBuilder->getQuery()->getSQL()
        );
    }
}
