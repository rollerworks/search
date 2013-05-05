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

use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\CacheFormatter;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\QueryWhereBuilderHelper;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\CacheWhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\Loader\AnnotationDriver;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion;
use Metadata\MetadataFactory;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;

class QueryWhereBuilderHelperTest extends OrmTestCase
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
        $queryBuilderHelpers = new QueryWhereBuilderHelper();

        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I')
            ->join('I.customer', 'C')
        ;

        $queryBuilderHelpers->addWhereToQueryBuilder(
            $this->formatter,
            $whereBuilder,
            $query,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C')
        );

        $this->assertEquals("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I INNER JOIN I.customer C" . ($expectedDql ? ' WHERE ' . $expectedDql : ''), $this->cleanSql($query->getQuery()->getDQL()));
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query);
    }

    public function testAutoMapping()
    {
        $input = $this->newInput('invoice_label=F2012-4242;');
        $this->assertTrue($this->formatter->formatInput($input));

        $container       = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $queryBuilderHelpers = new QueryWhereBuilderHelper();

        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I')
        ;

        $queryBuilderHelpers->addWhereToQueryBuilder(
            $this->formatter,
            $whereBuilder,
            $query
        );

        $this->assertEquals("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I WHERE (I.label IN(:invoice_label_0))", $this->cleanSql($query->getQuery()->getDQL()));
        $this->assertQueryParamsEquals(array('invoice_label_0' => 'F2012-4242'), $query);
        $this->assertDqlSuccessCompile($query);
    }

    public function testEmptyResult()
    {
        $input = $this->newInput('no_field=2;');
        $this->assertTrue($this->formatter->formatInput($input));

        $container = $this->createContainer();
        $metadataFactory = new MetadataFactory(new AnnotationDriver($this->newAnnotationsReader()));
        $whereBuilder    = new WhereBuilder($metadataFactory, $container, $this->em);
        $queryBuilderHelpers = new QueryWhereBuilderHelper();

        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I')
        ;

        $queryBuilderHelpers->addWhereToQueryBuilder(
            $this->formatter,
            $whereBuilder,
            $query,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C')
        );

        $this->assertEquals("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I", $query->getDQL());
        $this->assertCount(0, $query->getParameters());
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
        $queryBuilderHelpers = new QueryWhereBuilderHelper();

        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I')
            ->join('I.customer', 'C')
        ;

        $queryBuilderHelpers->addWhereToQueryBuilder(
            $this->formatter,
            $whereBuilder,
            $query,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C')
        );

        $this->assertEquals("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I INNER JOIN I.customer C" . ($expectedDql ? ' WHERE ' . $expectedDql : ''), $this->cleanSql($query->getQuery()->getDQL()));
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query);
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
        $queryBuilderHelpers = new QueryWhereBuilderHelper();

        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I')
            ->join('I.customer', 'C')
        ;

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->setWhereBuilder($whereBuilder);

        $queryBuilderHelpers->addWhereToQueryBuilder(
            $cacheFormatter,
            $cacheWhereBuilder,
            $query,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C')
        );

        $this->assertEquals("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I INNER JOIN I.customer C" . ($expectedDql ? ' WHERE ' . $expectedDql : ''), $this->cleanSql($query->getQuery()->getDQL()));
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query);
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
        $queryBuilderHelpers = new QueryWhereBuilderHelper();

        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I')
            ->join('I.customer', 'C')
        ;

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->setWhereBuilder($whereBuilder);

        $queryBuilderHelpers->addWhereToQueryBuilder(
            $cacheFormatter,
            $cacheWhereBuilder,
            $query,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C')
        );

        $this->assertEquals("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I INNER JOIN I.customer C" . ($expectedDql ? ' WHERE ' . $expectedDql : ''), $this->cleanSql($query->getQuery()->getDQL()));
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query);

        $whereBuilder = $this->getMock('Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder', array(), array(), '', false);
        $whereBuilder->expects($this->never())->method('getWhereClause');

        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I')
            ->join('I.customer', 'C')
        ;

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->setWhereBuilder($whereBuilder);

        $queryBuilderHelpers->addWhereToQueryBuilder(
            $cacheFormatter,
            $cacheWhereBuilder,
            $query,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C')
        );

        $this->assertEquals("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I INNER JOIN I.customer C" . ($expectedDql ? ' WHERE ' . $expectedDql : ''), $this->cleanSql($query->getQuery()->getDQL()));
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query);
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
        $queryBuilderHelpers = new QueryWhereBuilderHelper();

        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I')
            ->join('I.customer', 'C')
        ;

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->setWhereBuilder($whereBuilder);

        $queryBuilderHelpers->addWhereToQueryBuilder(
            $cacheFormatter,
            $cacheWhereBuilder,
            $query,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C')
        );

        $this->assertEquals("SELECT I FROM Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice I INNER JOIN I.customer C" . ($expectedDql ? ' WHERE ' . $expectedDql : ''), $this->cleanSql($query->getQuery()->getDQL()));
        $this->assertQueryParamsEquals($params, $query);
        $this->assertDqlSuccessCompile($query);

        $input = $this->newInput(preg_match('/(\w)=/', '$1 = ', $filterQuery));
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($this->formatter);
        $this->assertTrue($cacheFormatter->formatInput($input));

        $whereBuilder = $this->getMock('Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder', array(), array(), '', false);
        $whereBuilder->expects($this->once())->method('getWhereClause');

        $query = $this->em->createQueryBuilder();
        $query
            ->select('I')
            ->from('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'I')
            ->join('I.customer', 'C')
        ;

        $cacheWhereBuilder = new CacheWhereBuilder($cacheDriver);
        $cacheWhereBuilder->setWhereBuilder($whereBuilder);

        $queryBuilderHelpers->addWhereToQueryBuilder(
            $cacheFormatter,
            $cacheWhereBuilder,
            $query,
            array('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice' => 'I', 'Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer' => 'C')
        );
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

    public static function provideValueConversionTests()
    {
        return array(
            array('customer_id=2;', '(C.id IN(:customer_id_0))', array('customer_id_0' => 2)),
        );
    }

    /**
     * @param QueryBuilder $query
     */
    protected function assertDqlSuccessCompile(QueryBuilder $query)
    {
        try {
            $query->getQuery()->getSQL();
        } catch (QueryException $e) {
            $this->fail('compile error:' . $e->getMessage() . ' with Query: ' . $query->getDQL());
        }
    }
}
