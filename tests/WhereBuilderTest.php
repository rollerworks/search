<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\InvoiceNumber;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesError;
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

        $this->assertEquals('((I.customer IN(2, 5)))', $whereBuilder->getWhereClause());
    }

    public function testQueryWithPrepend()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('WHERE ((I.customer IN(2, 5)))', $whereBuilder->getWhereClause('WHERE '));
    }

    public function testEmptyQueryWithPrepend()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('', $whereBuilder->getWhereClause('WHERE '));
    }

    public function testQueryWithMultipleFields()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
            ->field('status')
                ->addSingleValue(new SingleValue(2))
                ->addSingleValue(new SingleValue(5))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals('((I.customer IN(2, 5)) AND (I.status IN(2, 5)))', $whereBuilder->getWhereClause());
    }

    public function testEmptyResult()
    {
        $connection = $this->getConnectionMock();
        $condition = new SearchCondition($this->getFieldSet(), new ValuesGroup());
        $whereBuilder = $this->getWhereBuilder($condition, $connection);

        $this->assertEquals('', $whereBuilder->getWhereClause());
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

        $this->assertEquals('((I.customer NOT IN(2, 5)))', $whereBuilder->getWhereClause());
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

        $this->assertEquals('((I.customer IN(2) AND I.customer NOT IN(5)))', $whereBuilder->getWhereClause());
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
            '((((I.customer >= 2 AND I.customer <= 5) OR (I.customer >= 10 AND I.customer <= 20) OR '.
            '(I.customer > 60 AND I.customer <= 70) OR (I.customer >= 100 AND I.customer < 150))))',
            $whereBuilder->getWhereClause()
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
            '((((I.customer <= 2 OR I.customer >= 5) AND (I.customer <= 10 OR I.customer >= 20) AND '.
            '(I.customer < 60 OR I.customer >= 70) AND (I.customer <= 100 OR I.customer > 150))))',
            $whereBuilder->getWhereClause()
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

        $this->assertEquals('((I.customer > 2))', $whereBuilder->getWhereClause());
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
            '(((I.customer > 2 AND I.customer < 10)))',
            $whereBuilder->getWhereClause()
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
                    ->addSingleValue(new SingleValue(20))
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
            '((((I.customer IN(20) OR (I.customer > 2 AND I.customer < 10)))) OR ((I.customer > 30)))',
            $whereBuilder->getWhereClause()
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
            '((I.customer <> 2 AND I.customer <> 5))',
            $whereBuilder->getWhereClause()
        );
    }

    public function testExcludingComparisonsWithNormal()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addComparison(new Compare(35, '<>'))
                ->addComparison(new Compare(45, '<>'))
                ->addComparison(new Compare(30, '>'))
                ->addComparison(new Compare(50, '<'))
            ->end()
        ->getSearchCondition();

        $whereBuilder = $this->getWhereBuilder($condition);

        $this->assertEquals(
            '(((I.customer > 30 AND I.customer < 50) AND I.customer <> 35 AND I.customer <> 45))',
            $whereBuilder->getWhereClause()
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
            "(((C.name LIKE '%foo' ESCAPE '\\' OR C.name LIKE '%fo\\'o' ESCAPE '\\' OR ".
            "RW_REGEXP('(foo|bar)', C.name, 'u') OR RW_REGEXP('(doctor|who)', C.name, 'ui')) AND ".
            "LOWER(C.name) NOT LIKE LOWER('bar%') ESCAPE '\\'))",
            $whereBuilder->getWhereClause()
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
            '(((I.customer IN(2))) OR ((I.customer IN(3))))',
            $whereBuilder->getWhereClause()
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
            "(((I.customer IN(2))) AND (((C.name LIKE '%foo' ESCAPE '\\'))))",
            $whereBuilder->getWhereClause()
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
            "((I.customer IN(2)) OR (C.name LIKE '%foo' ESCAPE '\\'))",
            $whereBuilder->getWhereClause()
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
            "((((I.customer IN(2)) OR (C.name LIKE '%foo' ESCAPE '\\'))))",
            $whereBuilder->getWhereClause()
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

        $this->assertEquals("((I.label IN('2015-0001', '2015-0005')))", $whereBuilder->getWhereClause());
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param string $expectWhereCase
     * @param array  $options
     */
    public function testFieldConversion($expectWhereCase, array $options = array())
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
            ->will($this->returnCallback(function ($column, array $options, ConversionHints $hints) use ($test, $options) {
                $test->assertEquals($options, $options);
                $test->assertEquals('I', $hints->field->getAlias());
                $test->assertEquals('I.customer', $hints->column);

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

        $this->assertEquals("((I.customer = get_customer_type(2)))", $whereBuilder->getWhereClause());
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
        return array(
            array(
                "((CAST(I.customer AS customer_type) IN(2)))",
            ),
            array(
                "((CAST(I.customer AS customer_type) IN(2)))",
                array('active' => true),
            ),
        );
    }

    public function testConditionWithErrors()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addError(new ValuesError('singleValues[0]', 'this value is not valid'))
            ->end()
        ->getSearchCondition();

        $this->setExpectedException(
            'Rollerworks\Component\Search\Exception\BadMethodCallException',
            'Unable to generate the where-clause with a SearchCondition that contains errors.'
        );

        $this->getWhereBuilder($condition);
    }

    public function testConditionWithErrorsOnDeeperLevel()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSingleValue(new SingleValue(2))
            ->end()
            ->group()
                ->field('customer')
                    ->addError(new ValuesError('singleValues[0]', 'this value is not valid'))
                ->end()
            ->end()
        ->getSearchCondition();

        $this->setExpectedException(
            'Rollerworks\Component\Search\Exception\BadMethodCallException',
            'Unable to generate the where-clause with a SearchCondition that contains errors.'
        );

        $this->getWhereBuilder($condition);
    }
}
