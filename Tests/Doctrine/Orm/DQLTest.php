<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Doctrine\Orm;

use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\CacheFormatter;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\CacheWhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\Loader\AnnotationDriver;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerCustomSqlConversion;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\Doctrine\SqlConversion\StrategyConversion1;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\Doctrine\SqlConversion\StrategyConversion2;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion;
use Doctrine\Common\Cache\ArrayCache;
use Metadata\MetadataFactory;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query;

class DQLTest extends OrmTestCase
{
    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testBasics($filterQuery, $expectedDql, $params)
    {
        $input = $this->newInput($filterQuery);
        $this->assertTrue($this->formatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE ");

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase);
    }

    public function testEmptyResult()
    {
        $input = $this->newInput('no_field=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I");

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause($this->formatter));
        $this->assertNull($whereCase);
        $this->assertCount(0, $query->getParameters());
    }

    public function testAppendWithQuery()
    {
        $input = $this->newInput('invoice_customer=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C");

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query,
            ' WHERE '
        ));

        $this->assertEquals('(C.id IN(:invoice_customer_0))', $whereCase);
        $this->assertQueryParamsEquals(array('invoice_customer_0' => 2), $query);
        $this->assertEquals('SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE (C.id IN(:invoice_customer_0))', $this->cleanSql($query->getDQL()));
    }

    public function testAppend2NoResWithQuery()
    {
        $input = $this->newInput('no_field=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C ");

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query,
            ' WHERE '
        ));

        $this->assertNull($whereCase);
        $this->assertEquals('SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C ', $this->cleanSql($query->getDQL()));
    }

    /**
     * @dataProvider provideValueConversionTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testValueConversion($filterQuery, $expectedDql, $params)
    {
        $input = $this->newInput($filterQuery, 'customer');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $query = $this->em->createQuery("SELECT C FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer C WHERE ");

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase);
    }

    /**
     * @dataProvider provideFieldConversionTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testFieldConversion($filterQuery, $expectedDql, $params)
    {
        $input = $this->newInput($filterQuery, 'invoice');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setFieldConversion('invoice_customer', $container->get('customer_conversion'));

        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE ");

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase);
    }

    /**
     * @dataProvider provideCustomSqlValueConversionTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $queryParams
     * @param string $expectSql
     * @param array  $conversionParams
     */
    public function testCustomSqlValueConversion($filterQuery, $expectedDql, array $queryParams, $expectSql, $conversionParams = array())
    {
        $input = $this->newInput($filterQuery, 'customer');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setValueConversion('customer_id', new CustomerCustomSqlConversion(), $conversionParams);

        $query = $this->em->createQuery("SELECT C FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer C WHERE ");

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlSuccessCompile($query, $whereCase, (!empty($expectSql))));
    }

    /**
     * @dataProvider provideConversionStrategyTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $queryParams
     * @param string $expectSql
     */
    public function testConversionStrategy($filterQuery, $expectedDql, array $queryParams, $expectSql)
    {
        $input = $this->newInput($filterQuery, 'user');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setFieldConversion('birthday', new StrategyConversion1());
        $whereBuilder->setValueConversion('birthday', new StrategyConversion1());
        $whereBuilder->setValueConversion('user_id', new StrategyConversion2());

        $query = $this->em->createQuery("SELECT C FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer3 C WHERE ");

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer3' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($queryParams, $query);
        $this->assertEquals($expectSql, $this->assertDqlSuccessCompile($query, $whereCase, true));
    }

    public function testConversionStrategy0()
    {
        $input = $this->newInput('user_id=2; user_id=6;', 'user');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $container->set('customer_conversion', new CustomerConversion());

        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $whereBuilder->setFieldConversion('birthday', new StrategyConversion1());
        $whereBuilder->setValueConversion('birthday', new StrategyConversion1());
        $whereBuilder->setValueConversion('user_id', new StrategyConversion2());

        $query = $this->em->createQuery("SELECT C FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer3 C");

        $this->setExpectedException('UnexpectedValueException', 'Value conversion strategy "0" is not supported for the Doctrine Query Language');

        $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer3' => 'C'),
            $query, ' WHERE '
        ));
    }

    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testCache($filterQuery, $expectedDql, $params)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE ");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase);
    }

    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testCached($filterQuery, $expectedDql, $params)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE ");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase);

        $whereBuilder = $this->getMock('Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder', array(), array(), '', false);
        $whereBuilder->expects($this->never())->method('getWhereClause');
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE ");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase);
    }

    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testNoneCached($filterQuery, $expectedDql, $params)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE ");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase);

        $input = $this->newInput(preg_match('/(\w)=/', '$1 = ', $filterQuery));
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $whereBuilder = $this->getMock('Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder', array(), array(), '', false);
        $whereBuilder->expects($this->once())->method('getWhereClause');
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE ");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I'),
            $query
        );
    }

    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testCachedEntityMapping($filterQuery, $expectedDql, $params)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE ");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase);

        $whereBuilder = $this->getMock('Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder', array(), array(), '', false);
        $whereBuilder->expects($this->once())->method('getWhereClause');
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C WHERE ");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'A', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        );
    }

    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testCachedAppendQuerySame($filterQuery, $expectedDql, $params)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query,
            ' WHERE'
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase, false, false);

        $whereBuilder = $this->getMock('Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder', array(), array(), '', false);
        $whereBuilder->expects($this->once())->method('getWhereClause');
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'A', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query,
            ' WHERE'
        );
    }

    /**
     * @dataProvider provideBasicsTests
     *
     * @param string $filterQuery
     * @param string $expectedDql
     * @param array  $params
     */
    public function testCachedAppendQueryNotSame($filterQuery, $expectedDql, $params)
    {
        $cacheDriver = new ArrayCache();

        $input = $this->newInput($filterQuery);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $query = $this->em->createQuery("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $whereCase = $this->cleanSql($cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query,
            ' WHERE'
        ));

        $this->assertEquals($expectedDql, $whereCase);
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query, $whereCase, false, false);

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query,
            ' WHERE'
        );

        $whereBuilder = $this->getMock('Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder', array(), array(), '', false);
        $whereBuilder->expects($this->once())->method('getWhereClause');
        $query = $this->em->createQuery("SELECT I, 'foo' AS bar FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I JOIN I.customer C");

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->getWhereClause(
            $cacheFormatter,
            $whereBuilder,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'A', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query,
            ' WHERE'
        );
    }

    public function testDoctrineAlias()
    {
        $input = $this->newInput('invoice_customer=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $config = $this->em->getConfiguration();
        $config->addEntityNamespace('BaseBundle', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce');

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);

        $query = $this->em->createQuery("SELECT I FROM BaseBundle:ECommerceInvoice I JOIN I.customer C WHERE ");

        $whereCase = $this->cleanSql($whereBuilder->getWhereClause(
            $this->formatter,
            array('BaseBundle:ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C'),
            $query
        ));

        $this->assertEquals('(C.id IN(:invoice_customer_0))', $whereCase);
        $this->assertQueryParamsEquals(array('invoice_customer_0' => 2), $query);
        $this->assertDqlSuccessCompile($query, $whereCase);
    }

    public static function provideBasicsTests()
    {
        return array(
            array('invoice_customer=2;', '(C.id IN(:invoice_customer_0))', array('invoice_customer_0' => 2)),
            array('invoice_customer=>2;', '(C.id > :invoice_customer_0)', array('invoice_customer_0' => 2)),
            array('invoice_customer=<2;', '(C.id < :invoice_customer_0)', array('invoice_customer_0' => 2)),
            array('invoice_customer=!2;', '(C.id NOT IN(:invoice_customer_0))', array('invoice_customer_0' => 2)),
            array('invoice_customer=>=2;', '(C.id >= :invoice_customer_0)', array('invoice_customer_0' => 2)),
            array('invoice_customer=<=2;', '(C.id <= :invoice_customer_0)', array('invoice_customer_0' => 2)),

            array('invoice_label=F2012-4242;', '(I.label IN(:invoice_label_0))', array('invoice_label_0' => 'F2012-4242')),
            array('invoice_customer=2, 5;', '(C.id IN(:invoice_customer_0, :invoice_customer_1))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5;', '((C.id BETWEEN :invoice_customer_0 AND :invoice_customer_1))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5, 8;', '(C.id IN(:invoice_customer_0) OR (C.id BETWEEN :invoice_customer_1 AND :invoice_customer_2))', array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' =>5)),
            array('invoice_customer=2-5,!8-10;', '(((C.id BETWEEN :invoice_customer_0 AND :invoice_customer_1)) AND (C.id NOT BETWEEN :invoice_customer_2 AND :invoice_customer_3))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8, 'invoice_customer_3' => 10)),
            array('invoice_customer=2-5, !8;', '(((C.id BETWEEN :invoice_customer_0 AND :invoice_customer_1)) AND C.id NOT IN(:invoice_customer_2))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8)),
            array('invoice_customer=2-5, >8;', '((C.id BETWEEN :invoice_customer_0 AND :invoice_customer_1) OR C.id > :invoice_customer_2)', array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8)),

            array('(invoice_customer=2;),(invoice_customer=3;)', '(C.id IN(:invoice_customer_0)) OR (C.id IN(:invoice_customer_1))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 3)),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', '(C.id IN(:invoice_customer_0, :invoice_customer_1)) OR (C.id IN(:invoice_customer_2, :invoice_customer_3))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5)),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', '(C.id IN(:invoice_customer_0, :invoice_customer_1) AND I.status IN(:invoice_status_0)) OR (C.id IN(:invoice_customer_2, :invoice_customer_3))', array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5, 'invoice_status_0' => 1)),
            array('invoice_date=06/13/2012;', '(I.date IN(:invoice_date_0))', array('invoice_date_0' => new DateTimeExtended('2012-06-13'))),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', '', array()),
        );
    }

    public static function provideFieldConversionTests()
    {
        $tests = array(
            array('invoice_customer=2;', "(RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_0))", array('invoice_customer_0' => 2)),
            array('invoice_customer=2;invoice_label=F2012-4242;', "(RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_0) AND I.label IN(:invoice_label_0))", array('invoice_label_0' => 'F2012-4242', 'invoice_customer_0' => '2')),
            array('invoice_label=F2012-4242;', "(I.label IN(:invoice_label_0))", array('invoice_label_0' => 'F2012-4242')),
            array('invoice_customer=2, 5;', "(RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_0, :invoice_customer_1))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5;', "((RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) BETWEEN :invoice_customer_0 AND :invoice_customer_1))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5)),
            array('invoice_customer=2-5, 8;', "(RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_0) OR (RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) BETWEEN :invoice_customer_1 AND :invoice_customer_2))", array('invoice_customer_0' => 8, 'invoice_customer_1' => 2, 'invoice_customer_2' =>5)),
            array('invoice_customer=2-5, >8;', "((RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) BETWEEN :invoice_customer_0 AND :invoice_customer_1) OR RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) > :invoice_customer_2)", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8)),

            array('(invoice_customer=2;),(invoice_customer=3;)', "(RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_0)) OR (RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_1))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 3)),
            array('(invoice_customer=2,3;),(invoice_customer=3,5;)', "(RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_0, :invoice_customer_1)) OR (RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_2, :invoice_customer_3))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5)),
            array('(invoice_customer=2,3; invoice_status=Active;),(invoice_customer=3,5;)', "(RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_0, :invoice_customer_1) AND I.status IN(:invoice_status_0)) OR (RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) IN(:invoice_customer_2, :invoice_customer_3))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 3, 'invoice_customer_2' => 3, 'invoice_customer_3' => 5, 'invoice_status_0' => 1)),
            array('invoice_date=06/13/2012;', "(I.date IN(:invoice_date_0))", array('invoice_date_0' => new DateTimeExtended('2012-06-13'))),

            // Expects empty as there is no field with that name
            array('(user=2;),(user=2;)', '', array()),
        );

        // Temporarily disabled for older versions due to a bug, once 2.2.4-DEV is fixed this can changed to >=2.2.4
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.2.4', '>')) {
            $tests[] = array('invoice_customer=2-5,!8-10;', "(((RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) BETWEEN :invoice_customer_0 AND :invoice_customer_1)) AND (RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) NOT BETWEEN :invoice_customer_2 AND :invoice_customer_3))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8, 'invoice_customer_3' => 10));
            $tests[] = array('invoice_customer=2-5, !8;', "(((RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) BETWEEN :invoice_customer_0 AND :invoice_customer_1)) AND RECORD_FILTER_FIELD_CONVERSION('invoice_customer', C.id) NOT IN(:invoice_customer_2))", array('invoice_customer_0' => 2, 'invoice_customer_1' => 5, 'invoice_customer_2' => 8));
        }

        return $tests;
    }

    public static function provideValueConversionTests()
    {
        return array(
            array('customer_id=2;', '(C.id IN(:customer_id_0))', array('customer_id_0' => 2)),
        );
    }

    public static function provideConversionStrategyTests()
    {
        return array(
            array('birthday=2;', "(RECORD_FILTER_FIELD_CONVERSION('birthday', C.birthday, 1) IN(:birthday_0))", array('birthday_0' => 2), "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (to_char('YYYY', age(c0_.birthday)) IN (?))"),
            array('birthday="1990-05-30";', "(RECORD_FILTER_FIELD_CONVERSION('birthday', C.birthday, 2) IN(:birthday_0))", array('birthday_0' => '1990-05-30'), "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (c0_.birthday IN (?))"),
            array('birthday=2; birthday="1990-05-30";', "(RECORD_FILTER_FIELD_CONVERSION('birthday', C.birthday, 1) IN(:birthday_0) OR RECORD_FILTER_FIELD_CONVERSION('birthday', C.birthday, 2) IN(:birthday_1))", array('birthday_0' => 2, 'birthday_1' => '1990-05-30'), "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (to_char('YYYY', age(c0_.birthday)) IN (?) OR c0_.birthday IN (?))"),

            // This is not supported see https://github.com/rollerworks/RollerworksRecordFilterBundle/issues/26
            //array('user_id=2; user_id=6;', "(C.id = RECORD_FILTER_VALUE_CONVERSION('user_id', :user_id_0, 1) OR RECORD_FILTER_VALUE_CONVERSION('user_id', :user_id_1, 0))", array('user_id_0' => 2, 'user_id_1' => 6), "SELECT c0_.id AS id0, c0_.birthday AS birthday1 FROM customers c0_ WHERE (to_char('YYYY', age(c0_.birthday)) IN (?) OR c0_.birthday IN (?))"),
        );
    }

    public static function provideCustomSqlValueConversionTests()
    {
        return array(
            array('customer_id=2;', "(C.id = RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_0))", array('customer_id_0' => 2), ''),
            array('customer_id=!2;', "(C.id <> RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_0))", array('customer_id_0' => 2), ''),
            array('customer_id=>2;', "(C.id > RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_0))", array('customer_id_0' => 2), ''),
            array('customer_id=<2;', "(C.id < RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_0))", array('customer_id_0' => 2), ''),
            array('customer_id=<=2;', "(C.id <= RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_0))", array('customer_id_0' => 2), ''),
            array('customer_id=>=2;', "(C.id >= RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_0))", array('customer_id_0' => 2), ''),
            array('customer_id=>=2;', "(C.id >= RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_0))", array('customer_id_0' => 2), ''),

            array('customer_id=2-5;', "((C.id BETWEEN RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_0) AND RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_1)))", array('customer_id_0' => 2, 'customer_id_1' => 5), ''),
            array('customer_id=!2-5;', "((C.id NOT BETWEEN RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_0) AND RECORD_FILTER_VALUE_CONVERSION('customer_id', :customer_id_1)))", array('customer_id_0' => 2, 'customer_id_1' => 5), ''),
        );
    }

    /**
     * @param Query   $query
     * @param string  $whereCase
     * @param boolean $return
     * @param boolean $append
     *
     * @return string
     */
    protected function assertDqlSuccessCompile(Query $query, $whereCase, $return = false, $append = true)
    {
        if (null !== $whereCase) {
            if ($append) {
                $dql = $query->getDQL() . $whereCase;
                $query->setDQL($dql);
            }

            try {
                if ($return) {
                    return $query->getSQL();
                }

                $query->getSQL();
            } catch (QueryException $e) {
                $this->fail('compile error:' . $e->getMessage() . ' with Query: ' . $query->getDQL());
            }
        }

        return '';
    }
}
