<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\InvoiceNumber;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesGroup;

final class WhereBuilderTest extends DbalTestCase
{
    private function getWhereBuilder(SearchCondition $condition, Connection $connection = null)
    {
        $whereBuilder = $this->getDbalFactory()->createWhereBuilder(
            $connection ?: $this->getConnectionMock(),
            $condition
        );

        $whereBuilder->setField('customer', 'customer', 'integer', 'I');
        $whereBuilder->setField('customer_name', 'name', 'string', 'C');
        $whereBuilder->setField('customer_birthday', 'birthday', 'date', 'C');
        $whereBuilder->setField('status', 'status', 'integer', 'I');
        $whereBuilder->setField('label', 'label', 'string', 'I');

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

        $this->assertEquals('(((I.customer IN(:customer_0, :customer_1))))', $whereBuilder->getWhereClause());
        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_1' => array('integer', 5),
            ),
            $whereBuilder
        );
    }

    public function testEmptyResult()
    {
        $connection = $this->getConnectionMock();
        $condition = new SearchCondition($this->getFieldSet(), new ValuesGroup());
        $whereBuilder = $this->getWhereBuilder($condition, $connection);

        $this->assertEquals('', $whereBuilder->getWhereClause());
        $this->assertCount(0, $whereBuilder->getParameters());
        $this->assertCount(0, $whereBuilder->getParameterTypes());
    }

    public function testQueryWithEmbeddedValues()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('(((I.customer IN(2, 5))))', $whereBuilder->getWhereClause(true));
        $this->assertParamsEmpty($whereBuilder);
    }

    public function testExcludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addExcludedValue(new SingleValue(2))
                ->addExcludedValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('(((I.customer NOT IN(:customer_0, :customer_1))))', $whereBuilder->getWhereClause());
        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_1' => array('integer', 5),
            ),
            $whereBuilder
        );
    }

    public function testIncludesAndExcludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
                ->addExcludedValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('(((I.customer IN(:customer_0)) AND (I.customer NOT IN(:customer_1))))', $whereBuilder->getWhereClause());
        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_1' => array('integer', 5),
            ),
            $whereBuilder
        );
    }

    public function testRanges()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addRange(new Range(2, 5))
                ->addRange(new Range(10, 20))
                ->addRange(new Range(60, 70, false))
                ->addRange(new Range(100, 150, true, false))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '((((I.customer >= :customer_0 AND I.customer <= :customer_1) OR (I.customer >= :customer_2 AND '.
            'I.customer <= :customer_3) OR (I.customer > :customer_4 AND I.customer <= :customer_5) OR '.
            '(I.customer >= :customer_6 AND I.customer < :customer_7))))',
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_1' => array('integer', 5),
                'customer_2' => array('integer', 10),
                'customer_3' => array('integer', 20),
                'customer_4' => array('integer', 60),
                'customer_5' => array('integer', 70),
                'customer_6' => array('integer', 100),
                'customer_7' => array('integer', 150),
            ),
            $whereBuilder
        );
    }

    public function testExcludedRanges()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addExcludedRange(new Range(2, 5))
                ->addExcludedRange(new Range(10, 20))
                ->addExcludedRange(new Range(60, 70, false))
                ->addExcludedRange(new Range(100, 150, true, false))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '((((I.customer <= :customer_0 OR I.customer >= :customer_1) AND (I.customer <= :customer_2 OR '.
            'I.customer >= :customer_3) AND (I.customer < :customer_4 OR I.customer >= :customer_5) AND '.
            '(I.customer <= :customer_6 OR I.customer > :customer_7))))',
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_1' => array('integer', 5),
                'customer_2' => array('integer', 10),
                'customer_3' => array('integer', 20),
                'customer_4' => array('integer', 60),
                'customer_5' => array('integer', 70),
                'customer_6' => array('integer', 100),
                'customer_7' => array('integer', 150),
            ),
            $whereBuilder
        );
    }

    public function testSingleComparison()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addComparison(new Compare(2, '>'))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('(((I.customer > :customer_0)))', $whereBuilder->getWhereClause());
        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
            ),
            $whereBuilder
        );
    }

    public function testMultipleComparisons()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addComparison(new Compare(2, '>'))
                ->addComparison(new Compare(10, '<'))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '((((I.customer > :customer_0 AND I.customer < :customer_1))))',
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_1' => array('integer', 10),
            ),
            $whereBuilder
        );
    }

    public function testMultipleComparisonsWithGroups()
    {
        // Use two subgroups here as the comparisons are AND to each other
        // but applying them in the head group would ignore subgroups
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->field('customer')
                    ->addComparison(new Compare(2, '>'))
                    ->addComparison(new Compare(10, '<'))
                ->end()
            ->end()
            ->group()
                ->field('customer')
                    ->addComparison(new Compare(30, '>'))
                ->end()
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '(((((I.customer > :customer_0 AND I.customer < :customer_1)))) OR (((I.customer > :customer_2))))',
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_1' => array('integer', 10),
                'customer_2' => array('integer', 30),
            ),
            $whereBuilder
        );
    }

    public function testExcludingComparisons()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addComparison(new Compare(2, '<>'))
                ->addComparison(new Compare(5, '<>'))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '(((I.customer <> :customer_0 AND I.customer <> :customer_1)))',
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_1' => array('integer', 5),
            ),
            $whereBuilder
        );
    }

    public function testExcludingComparisonsWithNormal()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addComparison(new Compare(2, '<>'))
                ->addComparison(new Compare(5, '<>'))
                ->addComparison(new Compare(30, '>'))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '(((I.customer > :customer_0) AND (I.customer <> :customer_1 AND I.customer <> :customer_2)))',
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 30),
                'customer_1' => array('integer', 2),
                'customer_2' => array('integer', 5),
            ),
            $whereBuilder
        );
    }

    public function testPatternMatchers()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer_name')
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            "(((C.name LIKE :customer_name_0 ESCAPE '\\\\' OR C.name LIKE :customer_name_1 ESCAPE '\\\\' OR ".
            "RW_REGEXP(:customer_name_2, C.name, '') = 0 OR RW_REGEXP(:customer_name_3, C.name, 'ui') = 0) AND ".
            "(LOWER(C.name) NOT LIKE LOWER(:customer_name_4) ESCAPE '\\\\')))",
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_name_0' => array('string', 'foo'),
                'customer_name_1' => array('string', 'fo\\\'o'),
                'customer_name_2' => array('string', '(foo|bar)'),
                'customer_name_3' => array('string', '(doctor|who)'),
                'customer_name_4' => array('string', 'bar'),
            ),
            $whereBuilder
        );
    }

    public function testSubGroups()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->field('customer')->addSingleValue(new SingleValue(2))->end()
            ->end()
            ->group()
                ->field('customer')->addSingleValue(new SingleValue(3))->end()
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '((((I.customer IN(:customer_0)))) OR (((I.customer IN(:customer_1)))))',
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_1' => array('integer', 3),
            ),
            $whereBuilder
        );
    }

    public function testSubGroupWithRootCondition()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
            ->group()
                ->field('customer_name')
                    ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->end()
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            "((((I.customer IN(:customer_0)))) AND ((((C.name LIKE :customer_name_0 ESCAPE '\\\\')))))",
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_name_0' => array('string', 'foo'),
            ),
            $whereBuilder
        );
    }

    public function testOrGroupRoot()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet(), ValuesGroup::GROUP_LOGICAL_OR)
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
            ->field('customer_name')
                ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            "(((I.customer IN(:customer_0))) OR ((C.name LIKE :customer_name_0 ESCAPE '\\\\')))",
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_name_0' => array('string', 'foo'),
            ),
            $whereBuilder
        );
    }

    public function testSubOrGroup()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->group()
                ->group(ValuesGroup::GROUP_LOGICAL_OR)
                    ->field('customer')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                    ->field('customer_name')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                    ->end()
                ->end()
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            "(((((I.customer IN(:customer_0))) OR ((C.name LIKE :customer_name_0 ESCAPE '\\\\')))))",
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'customer_0' => array('integer', 2),
                'customer_name_0' => array('string', 'foo'),
            ),
            $whereBuilder
        );
    }

    public function testValueConversion()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('label')
                ->addSingleValue(new SingleValue(InvoiceNumber::createFromString('2015-001')))
                ->addSingleValue(new SingleValue(InvoiceNumber::createFromString('2015-005')))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            "(((I.label IN(:label_0, :label_1))))",
            $whereBuilder->getWhereClause()
        );

        $this->assertParamsEquals(
            array(
                'label_0' => array('string', '2015-0001'),
                'label_1' => array('string', '2015-0005'),
            ),
            $whereBuilder
        );
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param string  $expectWhereCase
     * @param boolean $valuesEmbedding
     * @param array   $options
     */
    public function testFieldConversion($expectWhereCase, $valuesEmbedding = false, array $options = array())
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

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        $this->assertEquals($expectWhereCase, $whereCase);

        if (!$valuesEmbedding) {
            $this->assertParamsEquals(array('customer_0' => array('integer', 2),), $whereBuilder);
        } else {
            $this->assertParamsEmpty($whereBuilder);
        }
    }

    /**
     * @dataProvider provideSqlValueConversionTests
     *
     * @param string  $expectWhereCase
     * @param boolean $valuesEmbedding
     * @param boolean $valueReqEmbedding
     */
    public function testSqlValueConversion($expectWhereCase, $valuesEmbedding = false, $valueReqEmbedding = false)
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
            ->expects(!$valuesEmbedding ? $this->atLeastOnce() : $this->any())
            ->method('valueRequiresEmbedding')
            ->will($this->returnValue($valueReqEmbedding))
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
        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);

        $this->assertEquals($expectWhereCase, $whereCase);

        if (!$valuesEmbedding && !$valueReqEmbedding) {
            $this->assertParamsEquals(array('customer_0' => array('integer', 2)), $whereBuilder);
        } else {
            $this->assertParamsEmpty($whereBuilder);
        }
    }

    /**
     * @dataProvider provideConversionStrategyTests
     *
     * @param string  $expectWhereCase
     * @param boolean $valuesEmbedding
     * @param boolean $valueReqEmbedding
     */
    public function testConversionStrategy($expectWhereCase, $valuesEmbedding = false, $valueReqEmbedding = false)
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
                    function ($column, array $passedOptions, array $hints) use ($test, $options, $valueReqEmbedding) {
                        $test->assertArrayHasKey('conversion_strategy', $hints);
                        $test->assertArrayHasKey('connection', $hints);
                        $test->assertEquals($options, $passedOptions);

                        if (2 === $hints['conversion_strategy']) {
                            return "search_conversion_age($column)";
                        }

                        $test->assertEquals(1, $hints['conversion_strategy']);

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
                    function ($input, array $passedOptions, array $hints) use ($test, $options, $valueReqEmbedding) {
                        $test->assertArrayHasKey('conversion_strategy', $hints);
                        $test->assertArrayHasKey('connection', $hints);
                        $test->assertEquals($options, $passedOptions);

                        if (2 === $hints['conversion_strategy']) {
                            if ($hints['value_embedded'] || $valueReqEmbedding) {
                                return "CAST(".$hints['connection']->quote($input)." AS DATE)";
                            }

                            return "CAST($input AS DATE)";
                        }

                        $test->assertEquals(1, $hints['conversion_strategy']);

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
                    function ($input, array $passedOptions, array $hints) use ($test, $options, $valueReqEmbedding) {
                        $test->assertArrayHasKey('conversion_strategy', $hints);
                        $test->assertArrayHasKey('value_embedded', $hints);
                        $test->assertEquals($options, $passedOptions);

                        if ($input instanceof \DateTime) {
                            $test->assertEquals(2, $hints['conversion_strategy']);
                        } else {
                            $test->assertEquals(1, $hints['conversion_strategy']);
                        }

                        if ($input instanceof \DateTime && ($hints['value_embedded'] || $valueReqEmbedding)) {
                            $input = $input->format('Y-m-d');
                        }

                        return $input;
                    }
                )
            )
        ;

        $converter
            ->expects(!$valuesEmbedding ? $this->atLeastOnce() : $this->any())
            ->method('valueRequiresEmbedding')
            ->will(
                $this->returnCallback(
                    function ($input, array $passedOptions, array $hints) use ($test, $options, $valueReqEmbedding) {
                        $test->assertArrayHasKey('conversion_strategy', $hints);
                        $test->assertEquals($options, $passedOptions);

                        if ($input instanceof \DateTime || !is_integer($input)) {
                            $test->assertEquals(2, $hints['conversion_strategy']);
                        } else {
                            $test->assertEquals(1, $hints['conversion_strategy']);
                        }

                        return $valueReqEmbedding;
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

        $whereCase = $whereBuilder->getWhereClause($valuesEmbedding);
        $this->assertEquals($expectWhereCase, $whereCase);

        if (!$valuesEmbedding && !$valueReqEmbedding) {
            $this->assertParamsEquals(
                array(
                    'customer_birthday_0' => array('date', '18'),
                    'customer_birthday_1' => array('date', $date),
                ),
                $whereBuilder
            );
        } else {
            $this->assertParamsEmpty($whereBuilder);
        }
    }

    public static function provideFieldConversionTests()
    {
        return array(
            array(
                "(((CAST(I.customer AS customer_type) IN(:customer_0))))",
            ),
            array(
                "(((CAST(I.customer AS customer_type) IN(2))))",
                true
            ),
            array(
                "(((CAST(I.customer AS customer_type) IN(:customer_0))))",
                false,
                array('active' => true)
            ),
        );
    }

    public static function provideSqlValueConversionTests()
    {
        return array(
            array(
                "(((I.customer = get_customer_type(:customer_0))))",
            ),
            array(
                "(((I.customer = get_customer_type(2))))",
                false,
                true,
            ),
            array(
                "(((I.customer = get_customer_type(2))))",
                true,
            ),
            array(
                "(((I.customer = get_customer_type(2))))",
                true,
                true,
            ),
        );
    }

    public static function provideConversionStrategyTests()
    {
        return array(
            array(
                "(((C.birthday = :customer_birthday_0 OR search_conversion_age(C.birthday) = CAST(:customer_birthday_1 AS DATE))))",
            ),
            array(
                "(((C.birthday = 18 OR search_conversion_age(C.birthday) = CAST('2001-01-15' AS DATE))))",
                true,
            ),
            array(
                "(((C.birthday = 18 OR search_conversion_age(C.birthday) = CAST('2001-01-15' AS DATE))))",
                false,
                true,
            ),
        );
    }
}
