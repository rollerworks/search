<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as PDOSqlite;
use Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Tests\Fixtures\CustomerId;
use Rollerworks\Component\Search\Tests\Mocks\ConnectionMock;
use Rollerworks\Component\Search\Tests\Mocks\FieldConfigMock;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;

abstract class DbalTestCase extends \PHPUnit_Framework_TestCase
{
    public static function provideSimpleQueryTests()
    {
        return array(
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addSingleValue(new SingleValue(2))
                        ->addSingleValue(new SingleValue(5))
                    ->end()
                ->getSearchCondition(),
                array(
                    '(((I.customer IN(:invoice_customer_0, :invoice_customer_1))))',
                    '(((C.id IN(:invoice_customer_0, :invoice_customer_1))))',
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 5)
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.id IN (?, ?))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addSingleValue(new SingleValue(2))
                        ->addSingleValue(new SingleValue(5))
                    ->end()
                ->getSearchCondition(),
                array(
                    '(((I.customer IN(2, 5))))',
                    '(((C.id IN(:invoice_customer_0, :invoice_customer_1))))',
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 5)
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.id IN (?, ?))))',
                true,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')->addComparison(new Compare(2, '>'))->end()
                ->getSearchCondition(),
                array(
                    '(((I.customer > :invoice_customer_0)))',
                    '(((C.id > :invoice_customer_0)))'
                ),
                array('invoice_customer_0' => array('integer', 2)),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.id > ?)))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')->addExcludedValue(new SingleValue(2))->end()
                ->getSearchCondition(),
                array(
                    '(((I.customer NOT IN(:invoice_customer_0))))',
                    '(((C.id NOT IN(:invoice_customer_0))))',
                ),
                array('invoice_customer_0' => array('integer', 2)),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((c1_.id NOT IN (?))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')->addRange(new Range(2, 5))->end()
                ->getSearchCondition(),
                array(
                    '((((I.customer >= :invoice_customer_0 AND I.customer <= :invoice_customer_1))))',
                    '((((C.id >= :invoice_customer_0 AND C.id <= :invoice_customer_1))))'
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 5),
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((c1_.id >= ? AND c1_.id <= ?))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5))
                        ->addRange(new Range(10, 20))
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((I.customer >= :invoice_customer_0 AND I.customer <= :invoice_customer_1) OR (I.customer >= :invoice_customer_2 AND I.customer <= :invoice_customer_3))))',
                    '((((C.id >= :invoice_customer_0 AND C.id <= :invoice_customer_1) OR (C.id >= :invoice_customer_2 AND C.id <= :invoice_customer_3))))'
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 5),
                    'invoice_customer_2' => array('integer', 10),
                    'invoice_customer_3' => array('integer', 20),
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((c1_.id >= ? AND c1_.id <= ?) OR (c1_.id >= ? AND c1_.id <= ?))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addExcludedRange(new Range(2, 5))
                        ->addExcludedRange(new Range(10, 20))
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((I.customer <= :invoice_customer_0 OR I.customer >= :invoice_customer_1) AND (I.customer <= :invoice_customer_2 OR I.customer >= :invoice_customer_3))))',
                    '((((C.id <= :invoice_customer_0 OR C.id >= :invoice_customer_1) AND (C.id <= :invoice_customer_2 OR C.id >= :invoice_customer_3))))'
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 5),
                    'invoice_customer_2' => array('integer', 10),
                    'invoice_customer_3' => array('integer', 20),
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((c1_.id <= ? OR c1_.id >= ?) AND (c1_.id <= ? OR c1_.id >= ?))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5))
                        ->addExcludedRange(new Range(10, 20))
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((I.customer >= :invoice_customer_0 AND I.customer <= :invoice_customer_1)) AND ((I.customer <= :invoice_customer_2 OR I.customer >= :invoice_customer_3))))',
                    '((((C.id >= :invoice_customer_0 AND C.id <= :invoice_customer_1)) AND ((C.id <= :invoice_customer_2 OR C.id >= :invoice_customer_3))))'
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 5),
                    'invoice_customer_2' => array('integer', 10),
                    'invoice_customer_3' => array('integer', 20),
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((c1_.id >= ? AND c1_.id <= ?)) AND ((c1_.id <= ? OR c1_.id >= ?))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5, false))
                        ->addExcludedRange(new Range(10, 20, false))
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((I.customer > :invoice_customer_0 AND I.customer <= :invoice_customer_1)) AND ((I.customer < :invoice_customer_2 OR I.customer >= :invoice_customer_3))))',
                    '((((C.id > :invoice_customer_0 AND C.id <= :invoice_customer_1)) AND ((C.id < :invoice_customer_2 OR C.id >= :invoice_customer_3))))'
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 5),
                    'invoice_customer_2' => array('integer', 10),
                    'invoice_customer_3' => array('integer', 20),
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((c1_.id > ? AND c1_.id <= ?)) AND ((c1_.id < ? OR c1_.id >= ?))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5, true, false))
                        ->addExcludedRange(new Range(10, 20, true, false))
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((I.customer >= :invoice_customer_0 AND I.customer < :invoice_customer_1)) AND ((I.customer <= :invoice_customer_2 OR I.customer > :invoice_customer_3))))',
                    '((((C.id >= :invoice_customer_0 AND C.id < :invoice_customer_1)) AND ((C.id <= :invoice_customer_2 OR C.id > :invoice_customer_3))))'
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 5),
                    'invoice_customer_2' => array('integer', 10),
                    'invoice_customer_3' => array('integer', 20),
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((c1_.id >= ? AND c1_.id < ?)) AND ((c1_.id <= ? OR c1_.id > ?))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice_with_customer'))
                    ->field('invoice_label')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                        ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                        ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((I.label LIKE :invoice_label_0 ESCAPE '\\\\' OR I.label LIKE :invoice_label_1 ESCAPE '\\\\' OR RW_REGEXP(:invoice_label_2, I.label, '') = 0 OR RW_REGEXP(:invoice_label_3, I.label, 'ui') = 0) AND (LOWER(I.label) NOT LIKE LOWER(:invoice_label_4) ESCAPE '\\\\')))",
                    "(((RW_SEARCH_MATCH(I.label, :invoice_label_0, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(I.label, :invoice_label_1, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(I.label, :invoice_label_2, 'regex', false) = 1 OR RW_SEARCH_MATCH(I.label, :invoice_label_3, 'regex', true) = 1) AND (RW_SEARCH_MATCH(I.label, :invoice_label_4, 'ends_with', true) <> 1)))"
                ),
                array(
                    'invoice_label_0' => array('string', 'foo'),
                    'invoice_label_1' => array('string', 'fo\\\'o'),
                    'invoice_label_2' => array('string', '(foo|bar)'),
                    'invoice_label_3' => array('string', '(doctor|who)'),
                    'invoice_label_4' => array('string', 'bar'),
                ),
                "SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((CASE WHEN i0_.label LIKE 'foo' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN i0_.label LIKE 'fo\\'o' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(foo|bar)', i0_.label, '') = 0 THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', i0_.label, 'ui') = 0 THEN 1 ELSE 0 END) = 1) AND ((CASE WHEN LOWER(i0_.label) LIKE LOWER('bar') ESCAPE '\\\\' THEN 1 ELSE 0 END) <> 1)))",
                false,
                array(
                    'query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ',
                    'dbal_mapping' => array(
                        'invoice_label' => array('label', 'string', 'I')
                    )
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->group()
                        ->field('invoice_customer')->addSingleValue(new SingleValue(2))->end()
                    ->end()
                    ->group()
                        ->field('invoice_customer')->addSingleValue(new SingleValue(3))->end()
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((I.customer IN(:invoice_customer_0)))) OR (((I.customer IN(:invoice_customer_1)))))',
                    '((((C.id IN(:invoice_customer_0)))) OR (((C.id IN(:invoice_customer_1)))))'
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 3),
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((c1_.id IN (?)))) OR (((c1_.id IN (?)))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')->addSingleValue(new SingleValue(5))->end()
                    ->group()
                        ->field('invoice_customer')->addSingleValue(new SingleValue(2))->end()
                    ->end()
                    ->group()
                        ->field('invoice_customer')->addSingleValue(new SingleValue(3))->end()
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((I.customer IN(:invoice_customer_0)))) AND ((((I.customer IN(:invoice_customer_1)))) OR (((I.customer IN(:invoice_customer_2))))))',
                    '((((C.id IN(:invoice_customer_0)))) AND ((((C.id IN(:invoice_customer_1)))) OR (((C.id IN(:invoice_customer_2))))))'
                ),
                array(
                    'invoice_customer_0' => array('integer', 5),
                    'invoice_customer_1' => array('integer', 2),
                    'invoice_customer_2' => array('integer', 3),
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((c1_.id IN (?)))) AND ((((c1_.id IN (?)))) OR (((c1_.id IN (?))))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->group()
                        ->group()
                            ->field('invoice_customer')->addSingleValue(new SingleValue(2))->end()
                        ->end()
                        ->group()
                            ->field('invoice_customer')->addSingleValue(new SingleValue(3))->end()
                        ->end()
                    ->end()
                ->getSearchCondition(),
                array(
                    '(((((I.customer IN(:invoice_customer_0)))) OR (((I.customer IN(:invoice_customer_1))))))',
                    '(((((C.id IN(:invoice_customer_0)))) OR (((C.id IN(:invoice_customer_1))))))',
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 3),
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((((c1_.id IN (?)))) OR (((c1_.id IN (?))))))',
                false,
                array('query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ')
            ),
        );
    }

    public static function provideValueConversionTests()
    {
        return array(
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')->addSingleValue(new SingleValue(new CustomerId(2)))->end()
                ->getSearchCondition(),
                '(((C.id IN(:customer_id_0))))',
                array(
                    'customer_id_0' => array('integer', 2),
                ),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE (((c0_.id IN (?))))',
                false,
                array('query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')->addSingleValue(new SingleValue(new CustomerId(2)))->end()
                ->getSearchCondition(),
                array('(((C.id IN(2))))', '(((C.id IN(:customer_id_0))))'),
                array(
                    'customer_id_0' => array('integer', 2),
                ),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE (((c0_.id IN (?))))',
                true,
                array('query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ')
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')
                        ->addSingleValue(new SingleValue(new CustomerId(2)))
                    ->end()
                    ->field('customer_name')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                        ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                        ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((C.id IN(2))) AND ((C.name LIKE 'foo' ESCAPE '\\\\' OR C.name LIKE 'fo\'o' ESCAPE '\\\\' OR RW_REGEXP('(foo|bar)', C.name, '') = 0 OR RW_REGEXP('(doctor|who)', C.name, 'ui') = 0) AND (LOWER(C.name) NOT LIKE LOWER('bar') ESCAPE '\\\\')))",
                    "(((C.id IN(:customer_id_0))) AND ((RW_SEARCH_MATCH(C.name, :customer_name_0, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(C.name, :customer_name_1, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(C.name, :customer_name_2, 'regex', false) = 1 OR RW_SEARCH_MATCH(C.name, :customer_name_3, 'regex', true) = 1) AND (RW_SEARCH_MATCH(C.name, :customer_name_4, 'ends_with', true) <> 1)))"
                ),
                array(
                    'customer_id_0' => array('integer', 2),
                ),
                "SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE (((c0_.id IN (?))) AND (((CASE WHEN c0_.name LIKE 'foo' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN c0_.name LIKE 'fo\'o' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(foo|bar)', c0_.name, '') = 0 THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', c0_.name, 'ui') = 0 THEN 1 ELSE 0 END) = 1) AND ((CASE WHEN LOWER(c0_.name) LIKE LOWER('bar') ESCAPE '\\\\' THEN 1 ELSE 0 END) <> 1)))",
                true,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                    'dbal_mapping' => array(
                        'customer_id' => array('id', 'integer', 'C'),
                        'customer_name' => array('name', 'string', 'C'),
                    )
                )
            ),
        );
    }

    public static function provideFieldConversionTests()
    {
        $tests = array(
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((CAST(I.customer AS customer_type) IN(:invoice_customer_0))))",
                    "(((RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null) IN(:invoice_customer_0))))"
                ),
                array(
                    'invoice_customer_0' => array('integer', 2)
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((CAST(c1_.id AS customer_type) IN (?))))',
                false,
                array(
                    'query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ',
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_label')
                        ->addSingleValue(new SingleValue('F2012-4242'))
                    ->end()
                ->getSearchCondition(),
                array(
                    '(((I.label IN(:invoice_label_0))))',
                    '(((I.label IN(:invoice_label_0))))'
                ),
                array('invoice_label_0' => array('string', 'F2012-4242')),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE (((i0_.label IN (?))))',
                false,
                array(
                    'query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ',
                    'dbal_mapping' => array(
                        'invoice_customer' => array('id', 'integer', 'I'),
                        'invoice_label' => array('label', 'string', 'I'),
                    )
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addRange(new Range(2, 5))
                    ->end()
                ->getSearchCondition(),
                array(
                    "((((CAST(I.customer AS customer_type) >= :invoice_customer_0 AND CAST(I.customer AS customer_type) <= :invoice_customer_1))))",
                    "((((RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null) >= :invoice_customer_0 AND RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null) <= :invoice_customer_1))))"
                ),
                array(
                    'invoice_customer_0' => array('integer', 2),
                    'invoice_customer_1' => array('integer', 5)
                ),
                'SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((CAST(c1_.id AS customer_type) >= ? AND CAST(c1_.id AS customer_type) <= ?))))',
                false,
                array(
                    'query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ',
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                        ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                        ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((CAST(I.customer AS customer_type) LIKE :invoice_customer_0 ESCAPE '\\\\' OR CAST(I.customer AS customer_type) LIKE :invoice_customer_1 ESCAPE '\\\\' OR RW_REGEXP(:invoice_customer_2, CAST(I.customer AS customer_type), '') = 0 OR RW_REGEXP(:invoice_customer_3, CAST(I.customer AS customer_type), 'ui') = 0) AND (LOWER(CAST(I.customer AS customer_type)) NOT LIKE LOWER(:invoice_customer_4) ESCAPE '\\\\')))",
                    "(((RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_0, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_1, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_2, 'regex', false) = 1 OR RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_3, 'regex', true) = 1) AND (RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_4, 'ends_with', true) <> 1)))"
                ),
                array(
                    'invoice_customer_0' => array('string', 'foo'),
                    'invoice_customer_1' => array('string', 'fo\\\'o'),
                    'invoice_customer_2' => array('string', '(foo|bar)'),
                    'invoice_customer_3' => array('string', '(doctor|who)'),
                    'invoice_customer_4' => array('string', 'bar'),
                ),
                "SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((CASE WHEN CAST(c1_.id AS customer_type) LIKE 'foo' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN CAST(c1_.id AS customer_type) LIKE 'fo\\'o' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(foo|bar)', CAST(c1_.id AS customer_type), '') = 0 THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', CAST(c1_.id AS customer_type), 'ui') = 0 THEN 1 ELSE 0 END) = 1) AND ((CASE WHEN LOWER(CAST(c1_.id AS customer_type)) LIKE LOWER('bar') ESCAPE '\\\\' THEN 1 ELSE 0 END) <> 1)))",
                false,
                array(
                    'query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ',
                    'dbal_mapping' => array(
                        'invoice_customer' => array('customer', 'string', 'I'),
                    )
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('invoice'))
                    ->field('invoice_customer')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                        ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                        ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((CAST(I.customer AS customer_type) LIKE 'foo' ESCAPE '\\\\' OR CAST(I.customer AS customer_type) LIKE 'fo\'o' ESCAPE '\\\\' OR RW_REGEXP('(foo|bar)', CAST(I.customer AS customer_type), '') = 0 OR RW_REGEXP('(doctor|who)', CAST(I.customer AS customer_type), 'ui') = 0) AND (LOWER(CAST(I.customer AS customer_type)) NOT LIKE LOWER('bar') ESCAPE '\\\\')))",
                    "(((RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_0, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_1, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_2, 'regex', false) = 1 OR RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_3, 'regex', true) = 1) AND (RW_SEARCH_MATCH(RW_SEARCH_FIELD_CONVERSION('invoice_customer', C.id, null), :invoice_customer_4, 'ends_with', true) <> 1)))"
                ),
                array(
                    'invoice_customer_0' => array('string', 'foo'),
                    'invoice_customer_1' => array('string', 'fo\\\'o'),
                    'invoice_customer_2' => array('string', '(foo|bar)'),
                    'invoice_customer_3' => array('string', '(doctor|who)'),
                    'invoice_customer_4' => array('string', 'bar'),
                ),
                "SELECT i0_.id AS id0, i0_.label AS label1, i0_.pubdate AS pubdate2, i0_.status AS status3, i0_.customer AS customer4 FROM invoices i0_ INNER JOIN customers c1_ ON i0_.customer = c1_.id WHERE ((((CASE WHEN CAST(c1_.id AS customer_type) LIKE 'foo' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN CAST(c1_.id AS customer_type) LIKE 'fo\\'o' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(foo|bar)', CAST(c1_.id AS customer_type), '') = 0 THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', CAST(c1_.id AS customer_type), 'ui') = 0 THEN 1 ELSE 0 END) = 1) AND ((CASE WHEN LOWER(CAST(c1_.id AS customer_type)) LIKE LOWER('bar') ESCAPE '\\\\' THEN 1 ELSE 0 END) <> 1)))",
                true,
                array(
                    'query' => 'SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ',
                    'dbal_mapping' => array(
                        'invoice_customer' => array('customer', 'string', 'I'),
                    )
                )
            ),
        );

        return $tests;
    }

    public static function provideSqlValueConversionTests()
    {
        return array(
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((C.id = get_customer_type(:customer_id_0))))",
                    "(((C.id = RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false))))",
                ),
                array(
                    'customer_id_0' => array('integer', 2)
                ),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE (((c0_.id = get_customer_type(?))))',
                false,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((C.id = get_customer_type(2))))",
                    "(((C.id = RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, true))))"
                ),
                array(
                    'customer_id_0' => array('integer', 2)
                ),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE (((c0_.id = get_customer_type(2))))',
                false,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                    'ignore_parameters_dbal' => array('customer_id_0'),
                    'value_embedding' => true,
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((C.id = get_customer_type(2))))",
                    "(((C.id = RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false))))"
                ),
                array(
                    'customer_id_0' => array('integer', 2)
                ),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE (((c0_.id = get_customer_type(?))))',
                true,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer', array('foo' => 'bar')))
                    ->field('customer_id')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((C.id = get_customer_type(:customer_id_0, '{\"foo\":\"bar\"}'))))",
                    "(((C.id = RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false))))",
                ),
                array(
                    'customer_id_0' => array('integer', 2)
                ),
                "SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE (((c0_.id = get_customer_type(?, '{\"foo\":\"bar\"}'))))",
                false,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                    'options_for_customer' => array('foo' => 'bar'),
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')
                        ->addExcludedValue(new SingleValue(2))
                    ->end()
                ->getSearchCondition(),
                array(
                    '(((C.id <> get_customer_type(:customer_id_0))))',
                    "(((C.id <> RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false))))"
                ),
                array('customer_id_0' => array('integer', 2)),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE (((c0_.id <> get_customer_type(?))))',
                false,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')
                        ->addRange(new Range(2, 5))
                    ->end()
                ->getSearchCondition(),
                array(
                    "((((C.id >= get_customer_type(:customer_id_0) AND C.id <= get_customer_type(:customer_id_1)))))",
                    "((((C.id >= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false) AND C.id <= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_1, null, false)))))",
                ),
                array(
                    'customer_id_0' => array('integer', 2),
                    'customer_id_1' => array('integer', 5)
                ),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE ((((c0_.id >= get_customer_type(?) AND c0_.id <= get_customer_type(?)))))',
                false,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')
                        ->addExcludedRange(new Range(2, 5))
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((C.id <= get_customer_type(:customer_id_0) OR C.id >= get_customer_type(:customer_id_1)))))',
                    "((((C.id <= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false) OR C.id >= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_1, null, false)))))",
                ),
                array(
                    'customer_id_0' => array('integer', 2),
                    'customer_id_1' => array('integer', 5)
                ),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE ((((c0_.id <= get_customer_type(?) OR c0_.id >= get_customer_type(?)))))',
                false,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')
                        ->addExcludedRange(new Range(2, 5))
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((C.id <= get_customer_type(2) OR C.id >= get_customer_type(5)))))',
                    "((((C.id <= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, true) OR C.id >= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_1, null, true)))))",
                ),
                array(
                    'customer_id_0' => array('integer', 2),
                    'customer_id_1' => array('integer', 5)
                ),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE ((((c0_.id <= get_customer_type(2) OR c0_.id >= get_customer_type(5)))))',
                false,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                    'ignore_parameters_dbal' => array('customer_id_0', 'customer_id_1'),
                    'value_embedding' => true,
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_id')
                        ->addExcludedRange(new Range(2, 5))
                    ->end()
                ->getSearchCondition(),
                array(
                    '((((C.id <= get_customer_type(2) OR C.id >= get_customer_type(5)))))',
                    "((((C.id <= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_0, null, false) OR C.id >= RW_SEARCH_VALUE_CONVERSION('customer_id', C.id, :customer_id_1, null, false)))))",
                ),
                array(
                    'customer_id_0' => array('integer', 2),
                    'customer_id_1' => array('integer', 5)
                ),
                'SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE ((((c0_.id <= get_customer_type(?) OR c0_.id >= get_customer_type(?)))))',
                true,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                )
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_name')
                        ->addPatternMatch(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                        ->addPatternMatch(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                        ->addPatternMatch(new PatternMatch('(foo|bar)', PatternMatch::PATTERN_REGEX))
                        ->addPatternMatch(new PatternMatch('(doctor|who)', PatternMatch::PATTERN_REGEX, true))
                    ->end()
                ->getSearchCondition(),
                // This should not contain any SQL-value conversions
                array(
                    "(((C.name LIKE :customer_name_0 ESCAPE '\\\\' OR C.name LIKE :customer_name_1 ESCAPE '\\\\' OR RW_REGEXP(:customer_name_2, C.name, '') = 0 OR RW_REGEXP(:customer_name_3, C.name, 'ui') = 0) AND (LOWER(C.name) NOT LIKE LOWER(:customer_name_4) ESCAPE '\\\\')))",
                    "(((RW_SEARCH_MATCH(C.name, :customer_name_0, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(C.name, :customer_name_1, 'starts_with', false) = 1 OR RW_SEARCH_MATCH(C.name, :customer_name_2, 'regex', false) = 1 OR RW_SEARCH_MATCH(C.name, :customer_name_3, 'regex', true) = 1) AND (RW_SEARCH_MATCH(C.name, :customer_name_4, 'ends_with', true) <> 1)))",
                ),
                array(
                    'customer_name_0' => array('string', 'foo'),
                    'customer_name_1' => array('string', 'fo\\\'o'),
                    'customer_name_2' => array('string', '(foo|bar)'),
                    'customer_name_3' => array('string', '(doctor|who)'),
                    'customer_name_4' => array('string', 'bar'),
                ),
                "SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE ((((CASE WHEN c0_.name LIKE 'foo' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN c0_.name LIKE 'fo\'o' ESCAPE '\\\\' THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(foo|bar)', c0_.name, '') = 0 THEN 1 ELSE 0 END) = 1 OR (CASE WHEN RW_REGEXP('(doctor|who)', c0_.name, 'ui') = 0 THEN 1 ELSE 0 END) = 1) AND ((CASE WHEN LOWER(c0_.name) LIKE LOWER('bar') ESCAPE '\\\\' THEN 1 ELSE 0 END) <> 1)))",
                false,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                    'ignore_parameters_dbal' => array('customer_name_0', 'customer_name_1', 'customer_name_2', 'customer_name_3', 'customer_name_4'),
                    'convert_field' => 'customer_name',
                    'dbal_mapping' => array(
                        'customer_name' => array('name', 'string', 'C'),
                    ),
                    'negative' => true,
                ),
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('customer'))
                    ->field('customer_name')
                        ->addSingleValue(new SingleValue('foo'))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((C.name = get_customer_type(:customer_name_0))))",
                    "(((C.name = RW_SEARCH_VALUE_CONVERSION('customer_name', C.name, :customer_name_0, null, false))))",
                ),
                array(
                    'customer_name_0' => array('string', 'foo'),
                ),
                "SELECT c0_.id AS id0, c0_.name AS name1 FROM customers c0_ WHERE (((c0_.name = get_customer_type(?))))",
                false,
                array(
                    'query' => 'SELECT C FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer C WHERE ',
                    'convert_field' => 'customer_name',
                    'dbal_mapping' => array(
                        'customer_name' => array('name', 'string', 'C'),
                    ),
                ),
            ),
        );
    }

    public static function provideValueConversionStrategyTests()
    {
        return array(
            array(
                SearchConditionBuilder::create(static::getFieldSet('user'))
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((u.birthday = :user_birthday_0)))",
                    "(((u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_0, 1, false))))",
                ),
                array(
                    'user_birthday_0' => array('integer', 2)
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((c0_.birthday = ?)))",
                false,
                array(
                    'query' => 'SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ',
                ),
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('user'))
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(new \DateTime('1990-05-30')))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((u.birthday = CAST(:user_birthday_0 AS DATE))))",
                    "(((u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_0, 2, false))))",
                ),
                array(
                    'user_birthday_0' => array('integer', new \DateTime('1990-05-30'))
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((c0_.birthday = CAST(? AS DATE))))",
                false,
                array(
                    'query' => 'SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ',
                ),
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('user'))
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(2))
                        ->addSingleValue(new SingleValue(new \DateTime('1990-05-30')))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((u.birthday = :user_birthday_0 OR u.birthday = CAST(:user_birthday_1 AS DATE))))",
                    "(((u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_0, 1, false) OR u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_1, 2, false))))",
                ),
                array(
                    'user_birthday_0' => array('integer', 2),
                    'user_birthday_1' => array('integer', new \DateTime('1990-05-30')),
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((c0_.birthday = ? OR c0_.birthday = CAST(? AS DATE))))",
                false,
                array(
                    'query' => 'SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ',
                ),
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('user'))
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(2))
                        ->addSingleValue(new SingleValue(new \DateTime('1990-05-30')))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((u.birthday = 2 OR u.birthday = CAST('1990-05-30' AS DATE))))",
                    "(((u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_0, 1, false) OR u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_1, 2, false))))",
                ),
                array(
                    'user_birthday_0' => array('integer', 2),
                    'user_birthday_1' => array('integer', new \DateTime('1990-05-30')),
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((c0_.birthday = ? OR c0_.birthday = CAST(? AS DATE))))",
                true,
                array(
                    'query' => 'SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ',
                ),
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('user'))
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(2))
                        ->addSingleValue(new SingleValue(new \DateTime('1990-05-30')))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((u.birthday = 2 OR u.birthday = CAST('1990-05-30' AS DATE))))",
                    "(((u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_0, 1, true) OR u.birthday = RW_SEARCH_VALUE_CONVERSION('user_birthday', u.birthday, :user_birthday_1, 2, true))))",
                ),
                array(
                    'user_birthday_0' => array('integer', 2),
                    'user_birthday_1' => array('integer', '1990-05-30'),
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((c0_.birthday = 2 OR c0_.birthday = CAST('1990-05-30' AS DATE))))",
                false,
                array(
                    'query' => 'SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ',
                    'value_embedding' => true,
                    'ignore_parameters_dbal' => array('user_birthday_0', 'user_birthday_1')
                ),
            ),
        );
    }

    public static function provideFieldConversionStrategyTests()
    {
        return array(
            array(
                SearchConditionBuilder::create(static::getFieldSet('user'))
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(2))
                    ->end()
                ->getSearchCondition(),
                array(
                    '(((search_conversion_age(u.birthday) = :user_birthday_0)))',
                    "(((RW_SEARCH_FIELD_CONVERSION('user_birthday', u.birthday, 2) = :user_birthday_0)))",
                ),
                array(
                    'user_birthday_0' => array('integer', 2)
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((search_conversion_age(c0_.birthday) = ?)))",
                false,
                array(
                    'query' => 'SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ',
                ),
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('user'))
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue('1990-05-30'))
                    ->end()
                ->getSearchCondition(),
                array(
                    '(((u.birthday = :user_birthday_0)))',
                    "(((RW_SEARCH_FIELD_CONVERSION('user_birthday', u.birthday, 1) = :user_birthday_0)))"
                ),
                array(
                    'user_birthday_0' => array('integer', '1990-05-30')
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((c0_.birthday = ?)))",
                false,
                array(
                    'query' => 'SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ',
                ),
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('user'))
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(2))
                        ->addSingleValue(new SingleValue('1990-05-30'))
                    ->end()
                ->getSearchCondition(),
                array(
                    '(((search_conversion_age(u.birthday) = :user_birthday_0 OR u.birthday = :user_birthday_1)))',
                    "(((RW_SEARCH_FIELD_CONVERSION('user_birthday', u.birthday, 2) = :user_birthday_0 OR RW_SEARCH_FIELD_CONVERSION('user_birthday', u.birthday, 1) = :user_birthday_1)))",
                ),
                array(
                    'user_birthday_0' => array('integer', 2),
                    'user_birthday_1' => array('integer', '1990-05-30'),
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((search_conversion_age(c0_.birthday) = ? OR c0_.birthday = ?)))",
                false,
                array(
                    'query' => 'SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ',
                ),
            ),
            array(
                SearchConditionBuilder::create(static::getFieldSet('user'))
                    ->field('user_birthday')
                        ->addSingleValue(new SingleValue(2))
                        ->addSingleValue(new SingleValue('1990-05-30'))
                    ->end()
                ->getSearchCondition(),
                array(
                    "(((search_conversion_age(u.birthday) = 2 OR u.birthday = '1990-05-30')))",
                    "(((RW_SEARCH_FIELD_CONVERSION('user_birthday', u.birthday, 2) = :user_birthday_0 OR RW_SEARCH_FIELD_CONVERSION('user_birthday', u.birthday, 1) = :user_birthday_1)))",
                ),
                array(
                    'user_birthday_0' => array('integer', 2),
                    'user_birthday_1' => array('integer', '1990-05-30'),
                ),
                "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (((search_conversion_age(c0_.birthday) = ? OR c0_.birthday = ?)))",
                true,
                array(
                    'query' => 'SELECT u FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\User u WHERE ',
                ),
            ),
        );
    }

    protected static function getFieldSet($name = 'invoice', $optionsForCustomer = array())
    {
        if ('invoice' == $name) {
            $fieldSet = new FieldSet('invoice');

            $fieldSet->set('invoice_label', FieldConfigMock::create('invoice_label', null, $optionsForCustomer)
                ->setModelRef('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice', 'label')
                ->setAcceptCompares()
                ->setAcceptRange());

            $fieldSet->set('invoice_customer', FieldConfigMock::create('invoice_customer', null, $optionsForCustomer)
                ->setModelRef('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice', 'customer')
                ->setAcceptCompares()
                ->setAcceptRange());

            $fieldSet->set('invoice_status', FieldConfigMock::create('invoice_status', null, $optionsForCustomer)
                ->setModelRef('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice', 'status')
                ->setAcceptCompares()
                ->setAcceptRange());

            return $fieldSet;
        }

        if ('customer' == $name) {
            $fieldSet = new FieldSet('customer');

            $fieldSet->set('customer_id', FieldConfigMock::create('customer_id', null, $optionsForCustomer)
                ->setModelRef('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer', 'id')
                ->setAcceptCompares()
                ->setAcceptRange());

            $fieldSet->set('customer_name', FieldConfigMock::create('customer_name', null, $optionsForCustomer)
                ->setModelRef('Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceCustomer', 'name')
                ->setAcceptPatternMatch());

            return $fieldSet;
        }

        if ('user' == $name) {
            $fieldSet = new FieldSet('user');

            $fieldSet->set('user_id', FieldConfigMock::create('user_id')
                ->setModelRef('Rollerworks\\Component\\Search\\Tests\\Fixtures\\Entity\\User', 'id')
                ->setAcceptCompares()
                ->setAcceptRange());

            $fieldSet->set('user_birthday', FieldConfigMock::create('user_birthday')
                ->setModelRef('Rollerworks\\Component\\Search\\Tests\\Fixtures\\Entity\\User', 'birthday')
                ->setAcceptCompares()
                ->setAcceptRange());

            return $fieldSet;
        }

        if ('invoice_with_customer' == $name) {
            $fieldSet = new FieldSet('invoice_with_customer');

            $fieldSet->set('invoice_label', FieldConfigMock::create('invoice_label')
                ->setModelRef('Rollerworks\\Component\\Search\\Tests\\Fixtures\\Entity\\ECommerceInvoice', 'label')
                ->setAcceptCompares()
                ->setAcceptRange());

            $fieldSet->set('invoice_customer', FieldConfigMock::create('invoice_customer')
                ->setModelRef('Rollerworks\\Component\\Search\\Tests\\Fixtures\\Entity\\ECommerceInvoice', 'customer')
                ->setAcceptCompares()
                ->setAcceptRange());

            $fieldSet->set('invoice_status', FieldConfigMock::create('invoice_status')
                ->setModelRef('Rollerworks\\Component\\Search\\Tests\\Fixtures\\Entity\\ECommerceInvoice', 'status')
                ->setAcceptCompares()
                ->setAcceptRange());

            $fieldSet->set('customer_id', FieldConfigMock::create('customer_id')
                ->setModelRef('Rollerworks\\Component\\Search\\Tests\\Fixtures\\Entity\\ECommerceCustomer', 'id')
                ->setAcceptCompares()
                ->setAcceptRange());

            return $fieldSet;
        }

        throw new \InvalidArgumentException(sprintf('Unknown FieldSet "%s"', $name));
    }

    /**
     * @param array        $expected
     * @param WhereBuilder $whereBuilder
     * @param array        $ignoreFields
     */
    protected function asserParamsEquals(array $expected, $whereBuilder, array $ignoreFields = array())
    {
        foreach ($expected as $name => $param) {
            if (in_array($name, $ignoreFields)) {
                continue;
            }

            list($type, $value)=$param;

            $this->assertInstanceOf('Doctrine\DBAL\Types\Type', $whereBuilder->getParametersType($name));
            $this->assertEquals($type, $whereBuilder->getParametersType($name)->getName());
            $this->assertEquals($value, $whereBuilder->getParameter($name));
        }
    }

    /**
     * @return ConnectionMock
     */
    protected function getConnectionMock()
    {
        return new ConnectionMock(array(), new PDOSqlite());
    }
}
