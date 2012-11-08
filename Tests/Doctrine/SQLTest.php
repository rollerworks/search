<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Doctrine;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\CacheFormatter;
use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\CacheWhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\Loader\AnnotationDriver;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\Doctrine\SqlConversion\StrategyConversion1;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerCustomSqlConversion;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Common\Cache\ArrayCache;
use Metadata\MetadataFactory;

class SQLTest extends OrmTestCase
{
    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testBasics($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery);
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideBasicsWithAliasTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testBasicsWithAlias($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery);
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter, array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I')));
        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideBasicsWithMultiAliasTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testBasicsWithMultiAlias($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery, 'invoice_with_customer');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter, array(
            'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice'  => 'I',
            'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'
        )));

        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideWithQueryObjTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     * @param array  $params
     */
    public function testBasicsWithQueryObj($filterQuery, $expectedSql, array $params)
    {
        $input = $this->newInput($filterQuery);
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I');

        $query = $this->em->createNativeQuery("SELECT I.* FROM invoices AS I", $rsm);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter, array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'), $query));
        $this->assertEquals($expectedSql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
    }

    public function testEmptyResult()
    {
        $input = $this->newInput('no_field=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertNull($whereCase);
    }

    public function testAppendWithQuery()
    {
        $input = $this->newInput('invoice_customer=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I');

        $query = $this->em->createNativeQuery("SELECT I.* FROM invoices AS I", $rsm);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter, array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'), $query, ' WHERE '));
        $this->assertEquals('(I.customer IN(:invoice_customer_0))', $whereCase);
        $this->assertQueryParamsEquals(array('invoice_customer_0' => 2), $query);
        $this->assertEquals('SELECT I.* FROM invoices AS I WHERE (I.customer IN(:invoice_customer_0))', $this->cleanSql($query->getSQL()));
    }

    public function testAppend2NoResWithQuery()
    {
        $input = $this->newInput('no_field=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I');

        $query = $this->em->createNativeQuery("SELECT I.* FROM invoices AS I", $rsm);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter, array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'), $query, ' WHERE '));
        $this->assertNull($whereCase);
        $this->assertEquals('SELECT I.* FROM invoices AS I', $this->cleanSql($query->getSQL()));
    }

    /**
     * @dataProvider provideValueConversionTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testValueConversion($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery, 'customer');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testFieldConversion($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery, 'invoice');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setFieldConversion('invoice_customer', $container->get('customer_conversion'));

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideFieldConversionWithQueryObjTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     * @param array  $params
     */
    public function testFieldConversionWithQueryObj($filterQuery, $expectedSql, $params)
    {
        $input = $this->newInput($filterQuery, 'invoice');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setFieldConversion('invoice_customer', $container->get('customer_conversion'));

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I');

        $query = $this->em->createNativeQuery("SELECT I.* FROM invoices AS I", $rsm);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'),
            $query
        ));

        $this->assertEquals($expectedSql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
    }

    /**
     * @dataProvider provideCustomSqlValueConversionTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     * @param array  $conversionParams
     */
    public function testCustomSqlValueConversion($filterQuery, $expectedSql, $conversionParams = array())
    {
        $input = $this->newInput($filterQuery, 'customer');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setValueConversion('customer_id', new CustomerCustomSqlConversion(), $conversionParams);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C')
        ));

        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideCustomSqlValueConversionWithQueryObjTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     * @param array  $queryParams
     * @param array  $conversionParams
     */
    public function testCustomSqlValueConversionWithQueryObj($filterQuery, $expectedSql, array $queryParams, $conversionParams = array())
    {
        $input = $this->newInput($filterQuery, 'customer');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setValueConversion('customer_id', new CustomerCustomSqlConversion(), $conversionParams);

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer', 'C');
        $query = $this->em->createNativeQuery("SELECT I.* FROM customers AS C", $rsm);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedSql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
    }

    /**
     * @dataProvider provideConversionStrategyTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testConversionStrategy($filterQuery, $expectedSql)
    {
        $input = $this->newInput($filterQuery, 'user');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setFieldConversion('birthday', new StrategyConversion1());
        $whereBuilder->setValueConversion('birthday', new StrategyConversion1());

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     */
    public function testCache($filterQuery, $expectedDql)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder
        ));

        $this->assertEquals($expectedDql, $whereCase);
    }

    /**
     * @dataProvider provideWithQueryObjTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     * @param array  $params
     */
    public function testCacheWithQueryObj($filterQuery, $expectedSql, array $params)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I');

        $query = $this->em->createNativeQuery("SELECT I.* FROM invoices AS I", $rsm);

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter, array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'), $query));
        $this->assertEquals($expectedSql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'),
            $query
        ));

        $this->assertEquals($expectedSql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
    }

    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     */
    public function testCached($filterQuery, $expectedSql)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder
        ));

        $this->assertEquals($expectedSql, $whereCase);

        $whereBuilder = $this->getMock('Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder', array(), array(), '', false);
        $whereBuilder->expects($this->never())->method('getWhereClause');

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder
        ));

        $this->assertEquals($expectedSql, $whereCase);
    }

    /**
     * @dataProvider provideWithQueryObjTests
     *
     * @param string $filterQuery
     * @param string $expectedSql
     * @param array  $params
     */
    public function testCachedWithQuery($filterQuery, $expectedSql, array $params)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I');
        $query = $this->em->createNativeQuery("SELECT I.* FROM invoices AS I", $rsm);

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'),
            $query
        ));

        $this->assertEquals($expectedSql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);

        $whereBuilder = $this->getMock('Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder', array(), array(), '', false);
        $whereBuilder->expects($this->never())->method('getWhereClause');

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I');
        $query = $this->em->createNativeQuery("SELECT I.* FROM invoices AS I", $rsm);

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'),
            $query
        ));

        $this->assertEquals($expectedSql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
    }

    public static function provideBasicsTests()
    {
        return array(
            array('invoice_customer=2;', '(customer IN(2))'),
            array('invoice_label=F2012-4242;', '(label IN(\'F2012-4242\'))'),
            array('invoice_customer=2, 5;', '(customer IN(2, 5))'),
            array('invoice_customer=2-5;', '((customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, 8;', '(customer IN(8) AND (customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5,!8-10;', '((customer BETWEEN 2 AND 5) AND (customer NOT BETWEEN 8 AND 10))'),
            array('invoice_customer=2-5, !8;', '(customer NOT IN(8) AND (customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, >8;', '((customer BETWEEN 2 AND 5) AND customer > 8)'),

            array('(invoice_customer=2;),(invoice_customer=3;)', '(customer IN(2)) OR (customer IN(3))'),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', '(customer IN(2, 3)) OR (customer IN(3, 5))'),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', '(customer IN(2, 3) AND status IN(1)) OR (customer IN(3, 5))'),
            array('invoice_date=06/13/2012;', '(pubdate IN(\'2012-06-13\'))'),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', ''),
        );
    }

    public static function provideBasicsWithAliasTests()
    {
        return array(
            array('invoice_customer=2;', '(I.customer IN(2))'),
            array('invoice_label=F2012-4242;', '(I.label IN(\'F2012-4242\'))'),
            array('invoice_customer=2, 5;', '(I.customer IN(2, 5))'),
            array('invoice_customer=2-5;', '((I.customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, 8;', '(I.customer IN(8) AND (I.customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5,!8-10;', '((I.customer BETWEEN 2 AND 5) AND (I.customer NOT BETWEEN 8 AND 10))'),
            array('invoice_customer=2-5, !8;', '(I.customer NOT IN(8) AND (I.customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, >8;', '((I.customer BETWEEN 2 AND 5) AND I.customer > 8)'),

            array('(invoice_customer=2;),(invoice_customer=3;)', '(I.customer IN(2)) OR (I.customer IN(3))'),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', '(I.customer IN(2, 3)) OR (I.customer IN(3, 5))'),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', '(I.customer IN(2, 3) AND I.status IN(1)) OR (I.customer IN(3, 5))'),
            array('invoice_date=06/13/2012;', '(I.pubdate IN(\'2012-06-13\'))'),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', ''),
        );
    }

    public static function provideBasicsWithMultiAliasTests()
    {
        return array(
            array('invoice_customer=2;', '(I.customer IN(2))'),
            array('invoice_label=F2012-4242;', '(I.label IN(\'F2012-4242\'))'),
            array('invoice_customer=2, 5;', '(I.customer IN(2, 5))'),
            array('invoice_customer=2-5;', '((I.customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, 8;', '(I.customer IN(8) AND (I.customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5,!8-10;', '((I.customer BETWEEN 2 AND 5) AND (I.customer NOT BETWEEN 8 AND 10))'),
            array('invoice_customer=2-5, !8;', '(I.customer NOT IN(8) AND (I.customer BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, >8;', '((I.customer BETWEEN 2 AND 5) AND I.customer > 8)'),

            array('(invoice_customer=2;),(invoice_customer=3;)', '(I.customer IN(2)) OR (I.customer IN(3))'),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', '(I.customer IN(2, 3)) OR (I.customer IN(3, 5))'),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', '(I.customer IN(2, 3) AND I.status IN(1)) OR (I.customer IN(3, 5))'),
            array('invoice_date=06/13/2012;', '(I.pubdate IN(\'2012-06-13\'))'),

            array('customer_id=2;', "(C.id IN(2))"),
            array('customer_id=2;invoice_label=F2012-4242;', "(C.id IN(2) AND I.label IN('F2012-4242'))"),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', ''),
        );
    }

    public static function provideWithQueryObjTests()
    {
        return array(
            array('invoice_customer=2;', '(I.customer IN(:invoice_customer_0))', array('invoice_customer_0' => 2)),
            array('invoice_label=F2012-4242;', '(I.label IN(:invoice_label_0))', array('invoice_label_0' => 'F2012-4242')),
            array('invoice_customer=2, 5;', '(I.customer IN(:invoice_customer_0, :invoice_customer_1))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5;', '((I.customer BETWEEN :invoice_customer_0 AND :invoice_customer_1))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5, 8;', '(I.customer IN(:invoice_customer_0) AND (I.customer BETWEEN :invoice_customer_1 AND :invoice_customer_2))', array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' =>5)),
            array('invoice_customer=2-5,!8-10;', '((I.customer BETWEEN :invoice_customer_0 AND :invoice_customer_1) AND (I.customer NOT BETWEEN :invoice_customer_2 AND :invoice_customer_3))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8, 'invoice_customer_3' => 10)),
            array('invoice_customer=2-5, !8;', '(I.customer NOT IN(:invoice_customer_0) AND (I.customer BETWEEN :invoice_customer_1 AND :invoice_customer_2))', array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' => 5)),
            array('invoice_customer=2-5, >8;', '((I.customer BETWEEN :invoice_customer_0 AND :invoice_customer_1) AND I.customer > :invoice_customer_2)', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8)),

            array('(invoice_customer=2;),(invoice_customer=3;)', '(I.customer IN(:invoice_customer_0)) OR (I.customer IN(:invoice_customer_1))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 3)),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', '(I.customer IN(:invoice_customer_0, :invoice_customer_1)) OR (I.customer IN(:invoice_customer_2, :invoice_customer_3))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5)),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', '(I.customer IN(:invoice_customer_0, :invoice_customer_1) AND I.status IN(:invoice_status_0)) OR (I.customer IN(:invoice_customer_2, :invoice_customer_3))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5, 'invoice_status_0' => 1)),
            array('invoice_date=06/13/2012;', '(I.date IN(:invoice_date_0))', array('invoice_date_0' => new DateTimeExtended('2012-06-13'))),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', '', array()),
        );
    }

    public static function provideFieldConversionTests()
    {
        return array(
            array('invoice_customer=2;', '(CAST(customer AS customer_type) IN(2))'),
            array('invoice_label=F2012-4242;', '(label IN(\'F2012-4242\'))'),
            array('invoice_customer=2;invoice_label=F2012-4242;', "(CAST(customer AS customer_type) IN(2) AND label IN('F2012-4242'))"),
            array('invoice_customer=2, 5;', '(CAST(customer AS customer_type) IN(2, 5))'),
            array('invoice_customer=2-5;', '((CAST(customer AS customer_type) BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, 8;', '(CAST(customer AS customer_type) IN(8) AND (CAST(customer AS customer_type) BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5,!8-10;', '((CAST(customer AS customer_type) BETWEEN 2 AND 5) AND (CAST(customer AS customer_type) NOT BETWEEN 8 AND 10))'),
            array('invoice_customer=2-5, !8;', '(CAST(customer AS customer_type) NOT IN(8) AND (CAST(customer AS customer_type) BETWEEN 2 AND 5))'),
            array('invoice_customer=2-5, >8;', '((CAST(customer AS customer_type) BETWEEN 2 AND 5) AND CAST(customer AS customer_type) > 8)'),

            array('(invoice_customer=2;),(invoice_customer=3;)', '(CAST(customer AS customer_type) IN(2)) OR (CAST(customer AS customer_type) IN(3))'),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', '(CAST(customer AS customer_type) IN(2, 3)) OR (CAST(customer AS customer_type) IN(3, 5))'),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', '(CAST(customer AS customer_type) IN(2, 3) AND status IN(1)) OR (CAST(customer AS customer_type) IN(3, 5))'),
            array('invoice_date=06/13/2012;', '(pubdate IN(\'2012-06-13\'))'),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', ''),
        );
    }

    public static function provideFieldConversionWithQueryObjTests()
    {
        return array(
            array('invoice_customer=2;', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0))", array('invoice_customer_0' => 2)),
            array('invoice_customer=2;invoice_label=F2012-4242;', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0) AND I.label IN(:invoice_label_0))", array('invoice_label_0' => 'F2012-4242', 'invoice_customer_0' => '2')),
            array('invoice_label=F2012-4242;', "(I.label IN(:invoice_label_0))", array('invoice_label_0' => 'F2012-4242')),
            array('invoice_customer=2-5,!8-10;', "((CAST(I.customer AS customer_type) BETWEEN :invoice_customer_0 AND :invoice_customer_1) AND (CAST(I.customer AS customer_type) NOT BETWEEN :invoice_customer_2 AND :invoice_customer_3))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8, 'invoice_customer_3' => 10)),
            array('invoice_customer=2-5, !8;', "(CAST(I.customer AS customer_type) NOT IN(:invoice_customer_0) AND (CAST(I.customer AS customer_type) BETWEEN :invoice_customer_1 AND :invoice_customer_2))", array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' => 5)),
            array('invoice_customer=2, 5;', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0, :invoice_customer_1))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5;', "((CAST(I.customer AS customer_type) BETWEEN :invoice_customer_0 AND :invoice_customer_1))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5, 8;', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0) AND (CAST(I.customer AS customer_type) BETWEEN :invoice_customer_1 AND :invoice_customer_2))", array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' =>5)),
            array('invoice_customer=2-5, >8;', "((CAST(I.customer AS customer_type) BETWEEN :invoice_customer_0 AND :invoice_customer_1) AND CAST(I.customer AS customer_type) > :invoice_customer_2)", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8)),

            array('(invoice_customer=2;),(invoice_customer=3;)', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0)) OR (CAST(I.customer AS customer_type) IN(:invoice_customer_1))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 3)),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0, :invoice_customer_1)) OR (CAST(I.customer AS customer_type) IN(:invoice_customer_2, :invoice_customer_3))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5)),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', "(CAST(I.customer AS customer_type) IN(:invoice_customer_0, :invoice_customer_1) AND I.status IN(:invoice_status_0)) OR (CAST(I.customer AS customer_type) IN(:invoice_customer_2, :invoice_customer_3))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5, 'invoice_status_0' => 1)),
            array('invoice_date=06/13/2012;', "(I.date IN(:invoice_date_0))", array('invoice_date_0' => new DateTimeExtended('2012-06-13'))),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', '', array()),
        );
    }

    public static function provideValueConversionTests()
    {
        return array(
            array('customer_id=2;', '(id IN(2))'),
        );
    }

    public static function provideConversionStrategyTests()
    {
        return array(
            array('birthday=2;', "(to_char('YYYY', age(birthday)) IN('2'))"),
            array('birthday=!2;', "(to_char('YYYY', age(birthday)) NOT IN('2'))"),
            array('birthday=>2;', "(to_char('YYYY', age(birthday)) > '2')"),
            array('birthday=<2;', "(to_char('YYYY', age(birthday)) < '2')"),
            array('birthday=<=2;', "(to_char('YYYY', age(birthday)) <= '2')"),
            array('birthday=>=2;', "(to_char('YYYY', age(birthday)) >= '2')"),
            array('birthday=>=2;', "(to_char('YYYY', age(birthday)) >= '2')"),
            array('birthday=2-5;', "((to_char('YYYY', age(birthday)) BETWEEN '2' AND '5'))"),
            array('birthday=!2-5;', "((to_char('YYYY', age(birthday)) NOT BETWEEN '2' AND '5'))"),

            // This actually wrong, but there is birthday type yet
            array('birthday="1990-05-30";', "(birthday IN('1990-05-30'))"),
            array('birthday=!"1990-05-30";', "(birthday NOT IN('1990-05-30'))"),
            array('birthday=>"1990-05-30";', "(birthday > '1990-05-30')"),
            array('birthday=<"1990-05-30";', "(birthday < '1990-05-30')"),
            array('birthday=<="1990-05-30";', "(birthday <= '1990-05-30')"),
            array('birthday=>="1990-05-30";', "(birthday >= '1990-05-30')"),
            array('birthday="1990-05-30"-"1990-08-30";', "((birthday BETWEEN '1990-05-30' AND '1990-08-30'))"),
            array('birthday=!"1990-05-30"-"1990-08-30";', "((birthday NOT BETWEEN '1990-05-30' AND '1990-08-30'))"),

            array('birthday=2; birthday="1990-05-30";', "(to_char('YYYY', age(birthday)) IN('2') AND birthday IN('1990-05-30'))"),
            array('birthday=2; birthday="1990-05-30",5;', "(to_char('YYYY', age(birthday)) IN('2', '5') AND birthday IN('1990-05-30'))"),
            array('birthday=!2; birthday=!"1990-05-30";', "(to_char('YYYY', age(birthday)) NOT IN('2') AND birthday NOT IN('1990-05-30'))"),
            array('birthday=>2; birthday=>"1990-05-30";', "(to_char('YYYY', age(birthday)) > '2' AND birthday > '1990-05-30')"),
            array('birthday=<2; birthday=<"1990-05-30";', "(to_char('YYYY', age(birthday)) < '2' AND birthday < '1990-05-30')"),
            array('birthday=<=2; birthday=<="1990-05-30";', "(to_char('YYYY', age(birthday)) <= '2' AND birthday <= '1990-05-30')"),
            array('birthday=>=2; birthday=>="1990-05-30";', "(to_char('YYYY', age(birthday)) >= '2' AND birthday >= '1990-05-30')"),
            array('birthday=2-5; birthday="1990-05-30"-"1990-08-30";', "((to_char('YYYY', age(birthday)) BETWEEN '2' AND '5') AND (birthday BETWEEN '1990-05-30' AND '1990-08-30'))"),
            array('birthday=!2-5; birthday=!"1990-05-30"-"1990-08-30";', "((to_char('YYYY', age(birthday)) NOT BETWEEN '2' AND '5') AND (birthday NOT BETWEEN '1990-05-30' AND '1990-08-30'))"),
        );
    }

    public static function provideCustomSqlValueConversionTests()
    {
        return array(
            array('customer_id=2;', "(C.id IN(get_customer_type(2)))"),
            array('customer_id=!2;', "(C.id NOT IN(get_customer_type(2)))"),
            array('customer_id=>2;', "(C.id > get_customer_type(2))"),
            array('customer_id=<2;', "(C.id < get_customer_type(2))"),
            array('customer_id=<=2;', "(C.id <= get_customer_type(2))"),
            array('customer_id=>=2;', "(C.id >= get_customer_type(2))"),
            array('customer_id=>=2;', "(C.id >= get_customer_type(2))"),

            array('customer_id=2-5;', "((C.id BETWEEN get_customer_type(2) AND get_customer_type(5)))"),
            array('customer_id=!2-5;', "((C.id NOT BETWEEN get_customer_type(2) AND get_customer_type(5)))"),
        );
    }

    public static function provideCustomSqlValueConversionWithQueryObjTests()
    {
        return array(
            array('customer_id=2;', "(C.id IN(get_customer_type(:customer_id_0)))", array('customer_id_0' => 2)),
            array('customer_id=!2;', "(C.id NOT IN(get_customer_type(:customer_id_0)))", array('customer_id_0' => 2)),
            array('customer_id=>2;', "(C.id > get_customer_type(:customer_id_0))", array('customer_id_0' => 2)),
            array('customer_id=<2;', "(C.id < get_customer_type(:customer_id_0))", array('customer_id_0' => 2)),
            array('customer_id=<=2;', "(C.id <= get_customer_type(:customer_id_0))", array('customer_id_0' => 2)),
            array('customer_id=>=2;', "(C.id >= get_customer_type(:customer_id_0))", array('customer_id_0' => 2)),
            array('customer_id=>=2;', "(C.id >= get_customer_type(:customer_id_0))", array('customer_id_0' => 2)),

            array('customer_id=2-5;', "((C.id BETWEEN get_customer_type(:customer_id_0) AND get_customer_type(:customer_id_1)))", array('customer_id_0' => 2, 'customer_id_1' => 5)),
            array('customer_id=!2-5;', "((C.id NOT BETWEEN get_customer_type(:customer_id_0) AND get_customer_type(:customer_id_1)))", array('customer_id_0' => 2, 'customer_id_1' => 5)),
        );
    }
}
